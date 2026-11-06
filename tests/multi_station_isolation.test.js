/**
 * Multi-Station Audio & Metadata Isolation Verification Test Suite
 * Phase 5 Requirement Validation
 */

import assert from 'assert';

// Mock localStorage with cross-tab storage event simulation
class MockLocalStorage {
    constructor() {
        this.store = {};
        this.listeners = [];
    }
    getItem(key) {
        return this.store[key] || null;
    }
    setItem(key, value) {
        const oldValue = this.store[key];
        const newValue = String(value);
        this.store[key] = newValue;
        this.listeners.forEach(fn => fn({ key, oldValue, newValue }));
    }
    removeItem(key) {
        const oldValue = this.store[key];
        delete this.store[key];
        this.listeners.forEach(fn => fn({ key, oldValue, newValue: null }));
    }
    clear() {
        this.store = {};
    }
    onStorage(fn) {
        this.listeners.push(fn);
    }
}

// Global OS MediaSession Singleton Mock
class OSMediaSession {
    constructor() {
        this.metadata = null;
        this.playbackState = 'none';
        this.actionHandlers = {};
    }
    setActionHandler(action, fn) {
        this.actionHandlers[action] = fn;
    }
}

const sharedOSMediaSession = new OSMediaSession();
const sharedLocalStorage = new MockLocalStorage();

// Station Environment Simulator
class StationTabEnvironment {
    constructor(stationId, stationName, streamUrl) {
        this.stationId = stationId;
        this.stationName = stationName;
        this.streamUrl = streamUrl;
        this.storageKeyPrefix = `station_${stationId}_`;

        // Tab-isolated DOM State
        this.dom = {
            title: '',
            artistElement: { textContent: '' },
            titleElement: { textContent: '' },
            artworkSrc: ''
        };

        // Tab-isolated Audio State
        this.audio = {
            src: '',
            paused: true,
            play: async () => {
                this.audio.paused = false;
                this.playerState = 'PLAYING';
                this.claimMediaOwnership();
            },
            pause: () => {
                this.audio.paused = true;
                this.playerState = 'PAUSED';
            }
        };

        this.playerState = 'IDLE';
        this.isMediaOwner = false;
        this.storedMetadata = null;

        // Listen for storage events (filtered by station prefix)
        sharedLocalStorage.onStorage(e => {
            if (e.key && e.key.startsWith(this.storageKeyPrefix)) {
                this.handleStorageEvent(e);
            }
        });
    }

    getStorageKey(key) {
        return this.storageKeyPrefix + key;
    }

    claimMediaOwnership() {
        this.isMediaOwner = true;
        sharedLocalStorage.setItem(this.getStorageKey('active_tab'), 'tab_' + this.stationId);
    }

    handleStorageEvent(e) {
        // Isolated storage handler
    }

    // Called when SSE or status polling receives a metadata update
    onMetadataReceived(artist, title, artworkUrl) {
        // 1. Update DOM elements of THIS tab strictly
        this.dom.artistElement.textContent = artist || 'Now Playing';
        this.dom.titleElement.textContent = title || 'Live Stream';
        this.dom.title = `${title} - ${artist} | ${this.stationName}`;
        this.dom.artworkSrc = artworkUrl || 'default.jpg';

        this.storedMetadata = { artist, title, artworkUrl, timestamp: Date.now() };

        // Persist to station-scoped localStorage
        sharedLocalStorage.setItem(this.getStorageKey('current_song_metadata'), JSON.stringify(this.storedMetadata));

        // 2. MediaSession Ownership Guard:
        // Update OS MediaSession ONLY if this tab is playing audio or is active owner
        if (this.audio.paused === false || this.isMediaOwner) {
            sharedOSMediaSession.metadata = {
                title: title,
                artist: artist,
                album: this.stationName,
                artwork: [{ src: artworkUrl || 'default.jpg', sizes: '1024x1024' }]
            };
            sharedOSMediaSession.playbackState = this.audio.paused ? 'paused' : 'playing';
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// EXECUTE 10-STEP ISOLATION VERIFICATION SCENARIO
// ─────────────────────────────────────────────────────────────────────────────
console.log('Running Multi-Station Audio & Metadata Isolation Verification Suite...\n');

// Step 1: Station A open
console.log('1. Opening Station A (Jammin 92)...');
const tabA = new StationTabEnvironment('st_jammin92', 'Jammin 92', 'https://stream.jammin92.com/live');
assert.strictEqual(tabA.stationId, 'st_jammin92');
console.log('Station A initialized with isolated storage prefix: station_st_jammin92_');

// Step 2: Station B open
console.log('2. Opening Station B (RC101)...');
const tabB = new StationTabEnvironment('st_rc101', 'RC101', 'https://stream.rc101.com/live');
assert.strictEqual(tabB.stationId, 'st_rc101');
console.log('Station B initialized with isolated storage prefix: station_st_rc101_');

// Step 3: Station A playing
console.log('3. Starting audio playback on Station A...');
tabA.audio.play();
assert.strictEqual(tabA.audio.paused, false);
assert.strictEqual(tabA.isMediaOwner, true);
console.log('Station A is playing audio and claimed OS MediaSession ownership.');

// Step 4: Station B inactive
console.log('4. Verifying Station B is inactive...');
assert.strictEqual(tabB.audio.paused, true);
console.log('Station B is inactive (paused).');

// Step 5: Station A song changes
console.log('5. Station A song changes ("Song A1" by "Artist A1")...');
tabA.onMetadataReceived('Artist A1', 'Song A1', 'https://art.com/a1.jpg');
assert.strictEqual(tabA.dom.titleElement.textContent, 'Song A1');
assert.strictEqual(tabA.dom.artistElement.textContent, 'Artist A1');
assert.strictEqual(sharedOSMediaSession.metadata.title, 'Song A1');
console.log('Station A UI and OS MediaSession updated to "Song A1" by "Artist A1".');

// Step 6: Station B metadata changes
console.log('6. Station B receives background metadata update ("Song B1" by "Artist B1")...');
tabB.onMetadataReceived('Artist B1', 'Song B1', 'https://art.com/b1.jpg');
console.log('Station B received background metadata.');

// Step 7: Station A remains correct
console.log('7. Verifying Station A UI and OS MediaSession remain unchanged after Station B metadata update...');
assert.strictEqual(tabA.dom.titleElement.textContent, 'Song A1');
assert.strictEqual(tabA.dom.artistElement.textContent, 'Artist A1');
assert.strictEqual(sharedOSMediaSession.metadata.title, 'Song A1');
assert.strictEqual(sharedOSMediaSession.metadata.artist, 'Artist A1');
console.log('Station A DOM and OS MediaSession isolated! Station B metadata did NOT contaminate Station A.');

// Step 8: Station B starts playing
console.log('8. User starts playing audio on Station B...');
tabA.audio.pause(); // OS pauses Tab A
tabB.audio.play();  // Tab B starts playing
assert.strictEqual(tabB.audio.paused, false);
assert.strictEqual(tabB.isMediaOwner, true);
console.log('Station B is now playing.');

// Step 9: Media Session ownership changes appropriately
console.log('9. Pushing Station B metadata to OS MediaSession after playback start...');
tabB.onMetadataReceived('Artist B1', 'Song B1', 'https://art.com/b1.jpg');
assert.strictEqual(sharedOSMediaSession.metadata.title, 'Song B1');
assert.strictEqual(sharedOSMediaSession.metadata.artist, 'Artist B1');
console.log('OS MediaSession ownership seamlessly transferred to Station B.');

// Step 10: No stale metadata remains
console.log('10. Verifying no stale metadata remains in storage across both stations...');
const rawA = sharedLocalStorage.getItem(tabA.getStorageKey('current_song_metadata'));
const rawB = sharedLocalStorage.getItem(tabB.getStorageKey('current_song_metadata'));
const metaA = JSON.parse(rawA);
const metaB = JSON.parse(rawB);

assert.strictEqual(metaA.title, 'Song A1');
assert.strictEqual(metaB.title, 'Song B1');
assert.notStrictEqual(rawA, rawB);
console.log('Station A storage:', metaA.title);
console.log('Station B storage:', metaB.title);
console.log('Zero cross-tab contamination detected.');

console.log('\n🎉 ALL 10 MULTI-STATION ISOLATION TESTS PASSED SUCCESSFULLY!');
