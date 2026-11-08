<script>
    // Global error handler
    window.onerror = function (message, source, lineno, colno, error) {
        console.error('Global error:', {
            message,
            source,
            lineno,
            colno,
            error
        });
        return true; // Prevent default error handling
    };

    @php
        $resolvedSlogan = \App\Models\SiteSetting::get('station_slogan', env('STATION_SLOGAN'));
        if (empty($resolvedSlogan)) {
            $legacySloganPath = public_path(env('SLOGAN_FILE_PATH', 'config/slogan.txt'));
            if (file_exists($legacySloganPath)) {
                $resolvedSlogan = trim(file_get_contents($legacySloganPath));
            }
        }
    @endphp
    // Stream Slogan (SiteSetting -> .env -> legacy slogan.txt)
    let stationSlogan = @json($resolvedSlogan ?? '');
    const BASE_URL = window.location.origin;

    // â”€â”€ Dynamic album title: "AppName - Slogan" â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    const _appName = @json(config('app.name'));
    function getAlbumTitle() {
        return stationSlogan ? (_appName + ' - ' + stationSlogan) : _appName;
    }

    // â”€â”€ Readable duration formatter: "3 min 35 sec" â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function formatDurationReadable(seconds) {
        if (typeof seconds !== 'number' || !isFinite(seconds) || seconds <= 0) return '';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ' min ' + secs + ' sec';
    }

    // â”€â”€ Song timing state (SSE-provided, interpolated via timeupdate) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window._currentSongDuration = 0;
    window._currentSongElapsed = 0;
    window._songTimingTimestamp = 0;  // Date.now() when SSE last sent timing

    const _defaultSrc = '{{ asset(\App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg'))) }}';
    const _defaultAbsoluteSrc = _defaultSrc.startsWith('http') ?
        _defaultSrc :
        window.location.origin + _defaultSrc;



    // Private / Incognito browsing safe storage polyfill (in-memory fallback)
    if (!window.safeLocalStorage) {
        var _memStorageMap = {};
        window.safeLocalStorage = {
            getItem: function(key) {
                try { return window.localStorage.getItem(key); } catch(e) { return _memStorageMap[key] || null; }
            },
            setItem: function(key, val) {
                try { window.localStorage.setItem(key, val); } catch(e) { _memStorageMap[key] = String(val); }
            },
            removeItem: function(key) {
                try { window.localStorage.removeItem(key); } catch(e) { delete _memStorageMap[key]; }
            },
            clear: function() {
                try { window.localStorage.clear(); } catch(e) { _memStorageMap = {}; }
            }
        };
    }
    if (!window.safeSessionStorage) {
        var _memSessionMap = {};
        window.safeSessionStorage = {
            getItem: function(key) {
                try { return window.sessionStorage.getItem(key); } catch(e) { return _memSessionMap[key] || null; }
            },
            setItem: function(key, val) {
                try { window.sessionStorage.setItem(key, val); } catch(e) { _memSessionMap[key] = String(val); }
            },
            removeItem: function(key) {
                try { window.sessionStorage.removeItem(key); } catch(e) { delete _memSessionMap[key]; }
            },
            clear: function() {
                try { window.sessionStorage.clear(); } catch(e) { _memSessionMap = {}; }
            }
        };
    }

    const _tabSessionId = (function () {
        const key = window.getStationStorageKey ? window.getStationStorageKey('tab_id') : ('st_tab_id_' + (window.CURRENT_STATION_ID || 'default'));
        let id = null;
        try {
            id = window.safeSessionStorage.getItem(key);
        } catch(e) {}
        if (!id) {
            id = 'tab_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
            try {
                window.safeSessionStorage.setItem(key, id);
            } catch(e) {}
        }
        return id;
    })();

    // Track whether this tab is the "active" media owner for THIS station
    let _isActiveMediaTab = !document.hidden;

    // BroadcastChannel: tabs tell each other within the SAME station when they become media owner
    let _metaChannel = null;
    try {
        const channelName = 'media_owner_' + (window.CURRENT_STATION_ID || 'default');
        _metaChannel = new BroadcastChannel(channelName);
        _metaChannel.onmessage = function (e) {
            if (e.data && e.data.type === 'claim_owner' && e.data.tabId !== _tabSessionId) {
                const _audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                if (_audio && !_audio.paused) {
                    _claimMediaOwnership(); // Re-claim immediately
                    return;
                }

                _isActiveMediaTab = false;
            }
        };
    } catch (e) {
        console.warn('BroadcastChannel not supported â€” multi-tab sync disabled');
    }

    function _claimMediaOwnership() {
        _isActiveMediaTab = true;
        try {
            const key = window.getStationStorageKey ? window.getStationStorageKey('active_tab') : 'station_active_tab';
            window.safeLocalStorage.setItem(key, _tabSessionId);
            if (_metaChannel) {
                _metaChannel.postMessage({ type: 'claim_owner', tabId: _tabSessionId });
            }
        } catch (e) {}
    }

    if (!document.hidden) { _claimMediaOwnership(); }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    function buildDefaultArtwork() {
        const base = _defaultAbsoluteSrc;
        return [
            { src: base, sizes: '96x96', type: 'image/jpeg' },
            { src: base, sizes: '128x128', type: 'image/jpeg' },
            { src: base, sizes: '192x192', type: 'image/jpeg' },
            { src: base, sizes: '256x256', type: 'image/jpeg' },
            { src: base, sizes: '384x384', type: 'image/jpeg' },
            { src: base, sizes: '512x512', type: 'image/jpeg' },
            { src: base, sizes: '1024x1024', type: 'image/jpeg' },
            { src: base, sizes: '2048x2048', type: 'image/jpeg' }
        ];
    }

    // Pre-build IMMEDIATELY â€” available to every code path without waiting for DOM
    window._defaultArtwork = buildDefaultArtwork();

    // Synchronize mediaSession playbackState directly from genuine audio playback
    function _syncMediaSessionPlaybackState() {
        if (!('mediaSession' in navigator)) return;
        try {
            const _a = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
            if (!_a) {
                navigator.mediaSession.playbackState = 'none';
                return;
            }

            const isRealPlaying = (typeof window.isRealAudioPlaying === 'function') 
                ? window.isRealAudioPlaying() 
                : (!_a.paused && _a.readyState >= 2 && !_a.error);

            if (isRealPlaying) {
                navigator.mediaSession.playbackState = 'playing';
            } else {
                navigator.mediaSession.playbackState = 'paused';
            }
        } catch (e) {
            console.warn('Could not sync mediaSession playbackState:', e);
        }
    }

    // Top-level reassertion of MediaSession metadata (for Bluetooth AVRCP / CarPlay / Lockscreen)
    window.reassertMediaSessionMetadata = function (force) {
        if (!('mediaSession' in navigator)) return;
        try {
            let meta = window._storedMediaMetadata || window._pendingMediaMetadata;
            if (!meta) {
                const artistEl = document.getElementById('current-artist');
                const titleEl = document.getElementById('current-title');
                const currentArtist = artistEl ? artistEl.textContent.trim() : '';
                const currentTitle = titleEl ? titleEl.textContent.trim() : '';
                if (currentTitle && currentTitle !== 'Loading metadata...' && currentTitle !== 'Loading...') {
                    meta = {
                        title: currentTitle,
                        artist: (currentArtist && currentArtist !== 'Now Playing' && currentArtist !== 'Loading...') ? currentArtist : _appName,
                        album: getAlbumTitle(),
                        artwork: window._defaultArtwork || buildDefaultArtwork()
                    };
                    window._storedMediaMetadata = meta;
                }
            }

            if (meta) {
                try {
                    navigator.mediaSession.metadata = new MediaMetadata(meta);
                } catch (e) {
                    if (navigator.mediaSession.metadata) {
                        navigator.mediaSession.metadata.title = meta.title;
                        navigator.mediaSession.metadata.artist = meta.artist;
                        navigator.mediaSession.metadata.album = meta.album;
                        navigator.mediaSession.metadata.artwork = meta.artwork;
                    }
                }
            }
            _syncMediaSessionPlaybackState();
        } catch (e) {
            console.warn('reassertMediaSessionMetadata error:', e);
        }
    };

    // â”€â”€ SINGLE-POINT-OF-TRUTH: Debounced MediaSession metadata commit â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    window._pendingMediaMetadata = null;
    window._mediaMetadataTimer = null;
    window._mediaMetadataDebounceMs = 350; // enough to coalesce immediate+async

    /**
     * Queue a MediaSession metadata update. If called multiple times within
     * the debounce window, only the LAST call's data is committed.
     *
     * @param {Object} metadataObj  - {title, artist, album, artwork}
     * @param {boolean} immediate   - skip debounce (use for final iTunes result)
     */
    window._commitMediaSession = function (metadataObj, immediate) {
        if (!('mediaSession' in navigator)) return;
        if (!metadataObj) return;

        // Always capture the latest data
        window._pendingMediaMetadata = metadataObj;

        // Sync playback state accurately based on actual audio
        _syncMediaSessionPlaybackState();

        if (immediate) {
            if (window._mediaMetadataTimer) {
                clearTimeout(window._mediaMetadataTimer);
                window._mediaMetadataTimer = null;
            }
            _flushMediaSession();
            _syncMediaSessionPlaybackState();
            return;
        }

        // Debounce: wait for rapid successive calls to settle
        if (window._mediaMetadataTimer) {
            clearTimeout(window._mediaMetadataTimer);
        }
        window._mediaMetadataTimer = setTimeout(_flushMediaSession, window._mediaMetadataDebounceMs);
    };

    function _flushMediaSession() {
        window._mediaMetadataTimer = null;
        const obj = window._pendingMediaMetadata;
        if (!obj) return;

        try {
            // ALWAYS construct a new MediaMetadata instance to guarantee
            // that iOS WebKit (MPNowPlayingInfoCenter) and Android Chrome
            // broadcast the AVRCP track change event to Bluetooth car stereos.
            navigator.mediaSession.metadata = new MediaMetadata({
                title: obj.title || 'Live Stream',
                artist: obj.artist || '',
                album: obj.album || '',
                artwork: obj.artwork || []
            });
        } catch (e) {
            console.warn('new MediaMetadata assignment error:', e);
            if (navigator.mediaSession.metadata) {
                try {
                    navigator.mediaSession.metadata.title = obj.title;
                    navigator.mediaSession.metadata.artist = obj.artist;
                    navigator.mediaSession.metadata.album = obj.album;
                    navigator.mediaSession.metadata.artwork = obj.artwork;
                } catch (err) {}
            }
        }
        
        window._storedMediaMetadata = obj;
        window._storedArtworkSrc = (obj.artwork && obj.artwork[0]) ? obj.artwork[0].src : null;
        
        _syncMediaSessionPlaybackState();
    }

    // â”€â”€ iOS MediaSession Artwork Handling â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Store the resized 1024x1024 data URL in localStorage for iOS lock screen.
    // IMPORTANT: This function ONLY caches artwork to localStorage.
    // It does NOT touch navigator.mediaSession â€” that is handled exclusively
    // by _commitMediaSession to prevent duplicate updates that cause car display flash.
    window.storeArtworkForIOS = function (src, isDefault = false) {
        return new Promise((resolve) => {
            if (!src) return resolve(null);

            // Always store the original HTTPS URL â€” iOS MediaSession requires real URLs, not data: URIs
            if (!isDefault && src.startsWith('http')) {
                try {
                    window.safeLocalStorage.setItem(window.getStationStorageKey('ios_artwork_src'), src);
                } catch(e) {}
            }

            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = 1024;
                canvas.height = 1024;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, 1024, 1024);

                try {
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                    if (isDefault) {
                        window.safeLocalStorage.setItem(window.getStationStorageKey('ios_default_artwork'), dataUrl);
                    } else {
                        window.safeLocalStorage.setItem(window.getStationStorageKey('ios_current_artwork'), dataUrl);
                    }
                    resolve(dataUrl);
                } catch (e) {
                    console.warn('Could not store iOS artwork to localStorage', e);
                    resolve(null);
                }
            };
            img.onerror = function () {
                console.warn('Failed to load image for iOS artwork resizing:', src);
                resolve(null);
            };
            img.src = src;
        });
    };

    // Immediately store default artwork for iOS
    if (_defaultAbsoluteSrc) {
        window.storeArtworkForIOS(_defaultAbsoluteSrc, true);
    }
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€




    // updateArtwork â€” NO LONGER writes to MediaSession directly.
    // Instead it routes through _commitMediaSession so that all artwork
    // updates are debounced with the rest of the metadata, preventing
    // the artwork flash on Bluetooth/car displays.
    function updateArtwork(artworkArray) {
        if (!('mediaSession' in navigator)) {
            console.warn('updateArtwork: MediaSession API not supported');
            return;
        }

        let artwork = [];
        if (artworkArray && artworkArray.length > 0) {
            artwork = artworkArray.filter(a => !a.src.startsWith('data:'));
        } else {
            if (window._defaultArtwork && window._defaultArtwork.length > 0) {
                artwork.push(...window._defaultArtwork);
            }
        }

        if (artwork.length === 0) return;

        // Preserve existing title / artist / album â€” only update artwork
        const existing = window._storedMediaMetadata || {};
        const stName = _appName || "";

        const metadataObj = {
            title: existing.title || 'Live Stream',
            artist: existing.artist || stName,
            album: getAlbumTitle(),
            artwork: artwork
        };

        // Route through debounced commit â€” NOT direct MediaSession write
        window._commitMediaSession(metadataObj, false);
    }
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    function fetchSlogan() {
        const sloganPath = '{{ env('SLOGAN_FILE_PATH', 'config/slogan.txt') }}';
        if (!sloganPath) return;
        fetch(`${BASE_URL}/${sloganPath}?t=${new Date().getTime()}`)
            .then(response => {
                if (!response.ok) return null;
                return response.text();
            })
            .then(text => {
                if (text && text.trim().length > 0) {
                    stationSlogan = text.trim();
                }
            })
            .catch(() => {
                // Keep the server-rendered SiteSetting/env slogan on fetch error
            });
    }

    // Fetch slogan immediately on load
    fetchSlogan();

    function setupIOSAudio() {
        const audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : document.getElementById('live-audio');
        if (!audio) return;

        if (audio._iosMediaSessionBound) return;
        audio._iosMediaSessionBound = true;

        const reassertHandler = function (evt) {
            _syncMediaSessionPlaybackState();
            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata(true);
            }
        };

        const pauseHandler = function () {
            _syncMediaSessionPlaybackState();
        };

        audio.addEventListener('play', reassertHandler);
        audio.addEventListener('playing', reassertHandler);
        audio.addEventListener('loadedmetadata', reassertHandler);
        audio.addEventListener('canplay', reassertHandler);
        audio.addEventListener('pause', pauseHandler);

        // If audio is already active, reassert immediately
        if (!audio.paused && audio.readyState >= 2) {
            reassertHandler({ type: 'init-playing' });
        }
    }
    window.setupIOSAudio = setupIOSAudio;

    // Run setupIOSAudio immediately in case audio is already present
    setupIOSAudio();

    // Refresh slogan every 60 seconds

    // Metadata persistence system
    let currentSongMetadata = {
        artist: 'Now Playing',
        title: 'Live Stream',
        timestamp: 0
    };

    // Function to persist metadata only if it's valid and newer
    function persistMetadata(artist, title) {
        let now = Date.now();

        // Only update if we have valid metadata
        if (artist && title &&
            artist !== 'Now Playing' &&
            title !== 'Live Stream' &&
            artist !== 'Loading...' &&
            title !== 'Loading metadata...' &&
            artist !== 'Error loading stream' &&
            title !== 'Error loading metadata') {

            // Update the persisted metadata in localStorage for cross-page persistence
            const configuredBrand = `{{ \App\Models\SiteSetting::get('app_name', config('app.name')) }}`;
            if (artist.toLowerCase() === configuredBrand.toLowerCase()) {
                artist = configuredBrand;
            }
            const metadata = {
                artist: artist,
                title: title,
                timestamp: now
            };

            try {
                window.safeLocalStorage.setItem(window.getStationStorageKey('current_song_metadata'), JSON.stringify(metadata));
                return true;
            } catch (e) {
                console.error('Failed to persist metadata to localStorage:', e);
                return false;
            }
        }

        return false;
    }

    // Function to get the current persisted metadata
    function getPersistedMetadata() {
        try {
            const stored = window.safeLocalStorage.getItem(window.getStationStorageKey('current_song_metadata'));
            if (stored) {
                const metadata = JSON.parse(stored);
                // Check if metadata is recent (within last 10 minutes)
                const now = Date.now();
                const age = now - metadata.timestamp;
                if (age < 600000) { // 10 minutes
                    return metadata;
                } else {
                    window.safeLocalStorage.removeItem(window.getStationStorageKey('current_song_metadata'));
                }
            }
        } catch (e) {
            console.error('Failed to get persisted metadata from localStorage:', e);
        }

        // Return empty metadata if nothing valid found
        return {
            artist: '',
            title: '',
            timestamp: 0
        };
    }

    // Function to restore persisted metadata to display
    function restorePersistedMetadata() {
        const metadata = getPersistedMetadata();
        if (metadata.artist && metadata.title && metadata.artist !== '' && metadata.title !== '') {
            const artistElement = document.getElementById('current-artist');
            const titleElement = document.getElementById('current-title');

            if (artistElement && titleElement) {
                // Clean up persisted metadata to remove time patterns
                let cleanArtist = metadata.artist;
                let cleanTitle = metadata.title;

                // Remove time patterns from title (like 02:35, 2.57, etc.)
                cleanTitle = cleanTitle.replace(/\s*[0-9]{1,2}[:.][0-9]{2}\s*$/, '').trim();
                cleanTitle = cleanTitle.replace(/\s*[0-9]{1,2}[:.][0-9]{2}\s*[0-9]{1,2}[:.][0-9]{2}\s*$/, '')
                    .trim(); // For duration like 02:35:45

                // Remove trailing dashes and separators from title
                cleanTitle = cleanTitle.replace(/[\s\-\â€“\â€”\|â€¢:]+$/, '').trim();

                // Add prefixes to the display
                artistElement.textContent = cleanArtist;
                titleElement.textContent = cleanTitle;

                // Also update document title with proper format
                try {
                    const appName = @json(config('app.name'));
                    const stationName = appName || '';
                    if (cleanTitle && cleanArtist && cleanTitle !== 'Live Stream' && cleanTitle !== 'Unknown Song') {
                        document.title = `${cleanTitle} - ${cleanArtist} | ${stationName}`;
                    } else {
                        document.title = stationName;
                    }
                } catch (e) {
                    console.warn('Could not update document title:', e);
                }

                return {
                    artist: cleanArtist,
                    title: cleanTitle
                };
            }
        }
        return null;
    }


    document.addEventListener('DOMContentLoaded', function () {
        if (window._metadataInitialized) {
            return;
        }
        window._metadataInitialized = true;

        // Clear tab-closed flag from any previous navigation away from the page so
        // BT reconnect auto-resume works correctly on this page load.
        try {
            window.safeLocalStorage.removeItem(window.getStationStorageKey('tab_closed_at'));
        } catch(e) {}

        if (typeof setupIOSAudio === 'function') {
            setupIOSAudio();
        }

        try {
            // Make sure sticky player is visible on page load
            const stickyPlayer = document.getElementById('sticky-player');
            const playBtn = document.getElementById('play-btn');
            const playIcon = document.getElementById('play-icon');
            if (stickyPlayer) {
                stickyPlayer.style.display = 'flex';
            }

            const audio = document.getElementById("live-audio");
            // Ensure stream URL is properly escaped for JavaScript
            const streamUrl =
                "{{ addslashes(\App\Models\SiteSetting::get('stream_url', env('STREAM_URL', 'https://streams.radiomast.io/rc101'))) }}";

            // â”€â”€ Playback-intent state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // userPaused  = true only when the USER explicitly pressed Pause.
            let userPaused = false; let systemPaused = false; let systemPaused = false; window._adConfirmCounter = 0;

            // List of CORS proxies to try
            const corsProxies = [
                'https://api.allorigins.win/raw?url=',
                'https://corsproxy.io/?' + encodeURIComponent,
                '' // Try direct as last resort
            ];

            // Common metadata endpoints to try
            const metadataEndpoints = [
                '/status-json.xsl',
                '/status-json',
                '/status.xsl',
                '/status',
                '/7.html'
            ];

            let currentProxyIndex = 0;
            let currentEndpointIndex = 0;
            let metadataInterval;
            let retryCount = 0;
            const maxRetries = 3;
            const miniPlayIcon = document.getElementById('mini-play-icon');
            let lastMetadata = '';

            // Fallback metadata for when we can't fetch from the stream
            const fallbackMetadata = [{
                artist: '{{ config('app.name') }}',
                title: 'Playing the best music'
            },
            {
                artist: 'Live Radio',
                title: 'Streaming now'
            },
            {
                artist: '{{ config('app.name') }}',
                title: 'Tune in for great music'
            }
            ];
            let fallbackIndex = 0;

            // â”€â”€ Song-position helper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Forwards elapsed/duration from the stream metadata to the Media Session API
            // so iPhone lock screen widgets and car head units can display the progress bar.
            window.updatePositionState = function (elapsed, duration) {
                if (!('mediaSession' in navigator)) return;
                if (typeof duration !== 'number' || duration <= 0) return;

                // Store for timeupdate interpolation
                window._currentSongDuration = duration;
                window._currentSongElapsed = (typeof elapsed === 'number' && elapsed >= 0) ? elapsed : 0;
                window._songTimingTimestamp = Date.now();

                try {
                    const pos = Math.min(window._currentSongElapsed, duration);
                    const audio = RadioAudio.get();
                    const rate = audio ? (audio.playbackRate || 1) : 1;
                    navigator.mediaSession.setPositionState({
                        duration: duration,
                        playbackRate: rate,
                        position: pos
                    });

                    // Update readable duration display if element exists
                    const durationEl = document.getElementById('song-duration-display');
                    if (durationEl) {
                        durationEl.textContent = formatDurationReadable(duration);
                    }
                    const remainingEl = document.getElementById('song-remaining-display');
                    if (remainingEl) {
                        const remaining = Math.max(0, duration - pos);
                        remainingEl.textContent = '-' + formatDurationReadable(remaining);
                    }
                } catch (e) {
                    // setPositionState not supported on older browsers â€” silently ignore
                }
            };

            // â”€â”€ Continuous position updates via timeupdate â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            function getLiveMediaPosition() {
                const duration = Number(window._currentSongDuration);
                if (!Number.isFinite(duration) || duration <= 0) return null;

                const lastElapsed = Math.max(0, Number(window._currentSongElapsed) || 0);
                const lastTimestamp = Number(window._songTimingTimestamp) || Date.now();
                const elapsedSinceUpdate = Math.max(0, (Date.now() - lastTimestamp) / 1000);
                const position = Math.min(lastElapsed + elapsedSinceUpdate, Math.max(0, duration - 0.25));
                const audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;

                return {
                    duration: duration,
                    playbackRate: audio ? (audio.playbackRate || 1) : 1,
                    position: position
                };
            }

            window.reassertLiveMediaPosition = function (reason = 'live-media') {
                if (!('mediaSession' in navigator)) return;

                const state = getLiveMediaPosition();
                if (!state) return;

                try {
                    navigator.mediaSession.setPositionState(state);
                } catch (e) {
                    // setPositionState is not available on every browser.
                }
            };

            window.guardLiveStreamSeek = function (reason = 'unknown', opts = {}) {
                window.reassertLiveMediaPosition(reason);

                if (opts.resetLiveEdge && window._smartPlayer && typeof window._smartPlayer.jumpToLiveEdge === 'function') {
                    window._smartPlayer.jumpToLiveEdge(reason);
                }
            };

            window.configureLiveMediaSessionActions = function () {
                if (!('mediaSession' in navigator)) return;

                try {
                    navigator.mediaSession.setActionHandler('seekto', () => {
                        window.guardLiveStreamSeek('mediaSession:seekto');
                    });
                } catch (e) { }

                ['seekbackward', 'seekforward', 'previoustrack', 'nexttrack'].forEach((action) => {
                    try {
                        navigator.mediaSession.setActionHandler(action, null);
                    } catch (e) {
                        try {
                            navigator.mediaSession.setActionHandler(action, () => {
                                window.guardLiveStreamSeek(`mediaSession:${action}`);
                            });
                        } catch (ignore) { }
                    }
                });

                window.reassertLiveMediaPosition('configure-actions');
            };
            window.configureLiveMediaSessionActions();

            // The audio element fires 'timeupdate' ~4x/sec, which we use to interpolate
            // the SSE-provided elapsed time and keep external devices (car, lock screen)
            // showing a smoothly advancing progress bar in real time.
            (function _setupTimeupdateSync() {
                function _bindTimeupdate() {
                    const audio = RadioAudio.get();
                    if (!audio) {
                        // Audio element not ready yet â€” retry shortly
                        setTimeout(_bindTimeupdate, 500);
                        return;
                    }
                    if (audio._timeupdateSyncBound) return; // already bound
                    audio._timeupdateSyncBound = true;

                    audio.addEventListener('timeupdate', function () {
                        if (!('mediaSession' in navigator)) return;
                        const duration = window._currentSongDuration;
                        if (!duration || duration <= 0) return;

                        // Interpolate elapsed from last SSE timestamp
                        const now = Date.now();
                        const elapsed = window._currentSongElapsed + ((now - window._songTimingTimestamp) / 1000);
                        const currentTime = Math.min(Math.max(0, elapsed), duration);
                        const playbackRate = audio.playbackRate || 1;

                        try {
                            navigator.mediaSession.setPositionState({
                                duration: duration,
                                playbackRate: playbackRate,
                                position: currentTime
                            });
                        } catch (e) { /* silently ignore */ }

                        // Update on-screen time displays
                        const durationEl = document.getElementById('song-duration-display');
                        if (durationEl) durationEl.textContent = formatDurationReadable(duration);

                        const remainingEl = document.getElementById('song-remaining-display');
                        if (remainingEl) {
                            const remaining = Math.max(0, duration - currentTime);
                            remainingEl.textContent = '-' + formatDurationReadable(remaining);
                        }
                    });
                }
                // Bind immediately if possible, otherwise wait for DOM
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', _bindTimeupdate);
                } else {
                    _bindTimeupdate();
                }
            })();

            // Function to update the player with song information
            window.updateSongInfo = function (info) {
                if (!info) {
                    console.warn('No song info provided to updateSongInfo');
                    return;
                }

                const artistElement = document.getElementById('current-artist');
                const titleElement = document.getElementById('current-title');

                if (!artistElement || !titleElement) {
                    console.error('Could not find artist or title elements in the DOM');
                    return;
                }

                // Default values
                let artist = 'Now Playing';
                let title = 'Live Stream';

                // Check if info is an object with artist and title properties
                if (typeof info === 'object' && info !== null) {
                    if (info.artist || info.song) {
                        artist = info.artist || 'Unknown Artist';
                        title = info.song || info.title || 'Unknown Song';
                    } else if (info.title) {
                        // If only title is present, try to split it
                        const titleStr = info.title;
                        const separators = [' - ', ' â€“ ', ' â€” ', ' â€¢ ', ' | ', ':'];
                        for (const sep of separators) {
                            if (titleStr.includes(sep)) {
                                const parts = titleStr.split(sep).map(part => part.trim());
                                if (parts.length >= 2) {
                                    artist = parts[0];
                                    title = parts.slice(1).join(sep);
                                    break;
                                }
                            }
                        }
                        if (title === 'Live Stream') {
                            title = titleStr;
                        }
                    }
                } else {
                    // Handle string input (legacy format)
                    const infoStr = String(info).trim();
                    if (!infoStr) {
                        console.warn('Empty song info received');
                    } else {
                        const separators = [' - ', ' â€“ ', ' â€” ', ' â€¢ ', ' | ', ':'];
                        let separatorUsed = false;

                        for (const sep of separators) {
                            if (infoStr.includes(sep)) {
                                const parts = infoStr.split(sep).map(part => part.trim());
                                if (parts.length >= 2) {
                                    artist = parts[0];
                                    title = parts.slice(1).join(sep);
                                    separatorUsed = true;
                                    break;
                                }
                            }
                        }

                        if (!separatorUsed) {
                            title = infoStr;
                        }
                    }
                }
                if (!artist || !title) return;

                // Check for blacklisted phrases (server status text)
                const lowerArtist = artist.toLowerCase();
                const lowerTitle = title.toLowerCase();
                const blacklist = [
                    'mount point',
                    'stream title',
                    'stream description',
                    'content type',
                ];



                // Helper function to strip HTML tags
                const stripHtml = (html) => {
                    if (!html) return '';
                    const tmp = document.createElement("DIV");
                    tmp.innerHTML = html;
                    return tmp.textContent || tmp.innerText || "";
                };

                // Clean up the extracted values
                artist = stripHtml(artist);
                title = stripHtml(title);

                // Further clean up special characters and whitespace
                artist = artist.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').trim();
                title = title.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').trim();

                artist = artist.replace(/^[^\w\s]*|[^\w\s]*$/g, '').trim() || 'Unknown Artist';
                title = title.replace(/^[^\w\s]*|[^\w\s]*$/g, '').trim() || 'Unknown Song';

                // Remove time patterns from title (like 02:35, 2.57, etc.)
                title = title.replace(/\s*[0-9]{1,2}[:.][0-9]{2}\s*$/, '').trim();
                title = title.replace(/\s*[0-9]{1,2}[:.][0-9]{2}\s*[0-9]{1,2}[:.][0-9]{2}\s*$/, '')
                    .trim(); // For duration like 02:35:45

                // Remove trailing dashes and separators from title
                title = title.replace(/[\s\-\â€“\â€”\|â€¢:]+$/, '').trim();

                // If the title is empty but we have an artist, swap them
                if (!title && artist && artist !== 'Now Playing') {
                    title = artist;
                    artist = 'Now Playing';
                }

                // Fallback if we still don't have a valid title
                if (!title) {
                    title = 'Live Stream';
                }

                // Try to persist the metadata if it's valid
                const wasPersisted = persistMetadata(artist, title);

                // If this is invalid metadata (loading, error, etc.), try to restore persisted metadata
                if (!wasPersisted) {
                    const restored = restorePersistedMetadata();
                    if (restored) {
                        // Override the current local artist/title with restored metadata so fetch proceeds
                        artist = restored.artist;
                        title = restored.title;
                        // Intentionally NOT returning here so the rest of the function runs
                    }
                }

                // Check if current display has valid metadata that shouldn't be overridden
                const currentArtist = artistElement.textContent;
                const currentTitle = titleElement.textContent;

                // Detect if the song has actually changed or if this is just a redundant polling event
                const songHasChanged = (currentArtist !== (artist || 'Now Playing')) || (currentTitle !==
                    title);

                // Don't override valid metadata with generic data
                const appBrandLower = `{{ strtolower(\App\Models\SiteSetting::get('app_name', config('app.name'))) }}`;
                const isGeneric = artist.toLowerCase() === appBrandLower ||
                    title.toLowerCase() === appBrandLower ||
                    artist === 'Live Stream' ||
                    title === 'Live Stream' ||
                    artist === 'Loading...' ||
                    title === 'Loading metadata...' ||
                    artist === 'Player not available' ||
                    title === 'Player not available' ||
                    (artist === 'Now Playing' && title === 'Live Stream') ||
                    artist === 'Unknown Artist' ||
                    title === 'Unknown Song';

                // Check if current display has valid metadata (not loading states)
                const hasValidMetadata = currentArtist !== 'Loading...' &&
                    currentTitle !== 'Loading metadata...' &&
                    currentArtist !== 'Now Playing' &&
                    currentTitle !== 'Live Stream' &&
                    currentArtist !== 'Unknown Artist' &&
                    currentTitle !== 'Unknown Song' &&
                    currentArtist.trim() !== '' &&
                    currentTitle.trim() !== '';

                // If we have valid current metadata and incoming is generic, skip update
                const isAdBreak = /commercial|advertisement|sponsor|station id|your rockin|rc 101|live stream/i.test(artist + ' ' + title);
if (isAdBreak && hasValidMetadata) {
    window._adConfirmCounter = (window._adConfirmCounter || 0) + 1;
    if (window._adConfirmCounter < 3) {
        return; // Ignore brief 1-2 poll transition glitch on Play or between songs!
    }
    // Confirmed real Advertisement / Station Break on air -> Show clean station branding & logo
    artistElement.textContent = stationSlogan || 'Live Broadcast';
    titleElement.textContent = _appName || 'RC 101';
    const domArts = document.querySelectorAll('#sticky-player-art, .player-cover-art, #player-cover-art');
    domArts.forEach(img => { if (img) { img.style.display = 'block'; img.src = _defaultSrc; } });
    return;
}
window._adConfirmCounter = 0;
if (isGeneric && hasValidMetadata &&
                    currentArtist.toLowerCase() !== appBrandLower &&
                    currentTitle !== 'Live Stream' &&
                    currentArtist !== 'Loading...' &&
                    currentTitle !== 'Loading metadata...' &&
                    currentArtist !== 'Now Playing' &&
                    currentTitle !== 'Live Stream' &&
                    currentArtist !== 'Unknown Artist' &&
                    currentTitle !== 'Unknown Song') {
                    return;
                }

                // Only update the DOM if we didn't restore metadata (i.e., we have valid new metadata)
                try {
                    // Add prefixes to the display
                    artistElement.textContent = (artist || 'Now Playing');
                    titleElement.textContent = title;
                } catch (e) {
                    console.error('Error updating DOM elements:', e);
                    return;
                }

                const _stored = window._storedMediaMetadata;
                const _mediaSessionIsStale = !_stored ||
                    _stored.title !== (title || 'Live Stream') ||
                    _stored.artist !== (artist || '');

                // CarPlay Fix: If we are hidden but playing, we MUST NOT skip this update.
                const _audioForState = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                const _isActuallyPlaying = _audioForState && !_audioForState.paused;
                const _forceUpdate = _isActuallyPlaying && document.hidden;

                if (!songHasChanged && !_mediaSessionIsStale && !_forceUpdate) {
                    return; // Silently skip â€” both DOM and MediaSession already correct
                }

                // â”€â”€ Tab ownership â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                // Only the active/visible tab (or a hidden tab that is currently PLAYING) drives the MediaSession.
                const _audioOwnership = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                const _playingNow = _audioOwnership && !_audioOwnership.paused;

                if (!_isActiveMediaTab && !_playingNow) {
                    return;
                }
                // Claim ownership when we are the one pushing an update
                _claimMediaOwnership();
                // Update the Media Session
                try {

                    if ('mediaSession' in navigator) {
                        // Helper to generate artwork array with all required sizes
                        const getArtworkArray = (src, imageType = 'image/jpeg') => {
                            return [
                                // 1024x1024 is the primary stored size for lock screen display
                                {
                                    src: src,
                                    sizes: '1024x1024',
                                    type: imageType
                                }
                            ];
                        };

                        // Default station logo branding â€” defined globally at script-load time
                        // (see buildDefaultArtwork / window._defaultArtwork above)

                        // updateSessionMetadata â€” routes through _commitMediaSession
                        // instead of writing to navigator.mediaSession.metadata directly.
                        // The `immediate` flag controls whether the debouncer flushes now
                        // (used for the final iTunes result) or coalesces (used for interim updates).
                        //
                        // IMPORTANT: document.title is updated HERE (not earlier) so that
                        // text and artwork change atomically on the car/BT display.
                        const updateSessionMetadata = (artwork, immediate = false) => {
                            const processedArtwork = [];

                            if (Array.isArray(artwork)) {
                                for (const item of artwork) {
                                    if (!item.src) continue;
                                    // Force JPEG for OS compatibility
                                    const jpegSrc = item.src
                                        .replace(/\.webp(\?.*)?$/, '.jpeg$1')
                                        .replace(/\.png(\?.*)?$/, '.jpeg$1');
                                    processedArtwork.push({
                                        src: jpegSrc,
                                        sizes: item.sizes || '512x512',
                                        type: 'image/jpeg'
                                    });
                                }
                            }

                            // If no artwork was passed in (iTunes search found nothing or failed),
                            // always use the station default â€” NEVER re-use a stale previous song art.
                            if (processedArtwork.length === 0) {
                                if (window._defaultArtwork && window._defaultArtwork.length > 0) {
                                    processedArtwork.push(...window._defaultArtwork);

                                    // Inject base64 default art to prevent OS download delay
                                    try {
                                        const cachedDefault = window.safeLocalStorage.getItem(window.getStationStorageKey('ios_default_artwork'));
                                        if (cachedDefault) {
                                            processedArtwork.unshift({
                                                src: cachedDefault,
                                                sizes: '1024x1024',
                                                type: 'image/jpeg'
                                            });
                                        }
                                    } catch (e) { }
                                }
                            }

                            const metadataObj = {
                                title: title || 'Live Stream',
                                artist: artist || stationName,
                                album: getAlbumTitle(),
                                artwork: processedArtwork.length > 0 ? processedArtwork : (artwork || [])
                            };

                            // Update document.title atomically with MediaSession commit
                            // so car/BT displays see text + artwork change together.
                            try {
                                const _stName = @json(config('app.name')) || '';
                                const _t = metadataObj.title;
                                const _a = metadataObj.artist;
                                const _isGenericTitle = !_t || _t === 'Live Stream' || _t === 'Unknown Song' || _t === 'Loading metadata...' || _t === 'Loading...';
                                if (_t && !_isGenericTitle) {
                                    document.title = `${_t} - ${_a} | ${_stName}`;
                                } else {
                                    document.title = _stName;
                                }
                            } catch (e) {
                                console.warn('Could not update document title:', e);
                            }

                            // Update DOM player artwork elements (website player & sticky player)
                            try {
                                const artSrcToUse = (processedArtwork && processedArtwork.length > 0 && processedArtwork[0].src) 
                                    ? processedArtwork[0].src 
                                    : _defaultSrc;
                                const domArtElements = document.querySelectorAll('#sticky-player-art, .player-cover-art, #player-cover-art');
                                domArtElements.forEach(img => {
                                    if (img) {
                                        if (artSrcToUse && artSrcToUse !== _defaultSrc && img.src !== artSrcToUse) {
    const preImg = new Image();
    preImg.onload = function() { img.style.display = 'block'; img.style.display = 'block'; img.src = artSrcToUse; };
    preImg.onerror = function() { img.style.display = 'block'; img.src = _defaultSrc; };
    preImg.src = artSrcToUse;
} else {
    img.style.display = 'block';
    img.style.display = 'block'; img.src = artSrcToUse;
}
                                        img.onerror = function() {
                                            this.onerror = null;
                                            this.src = _defaultSrc;
                                        };
                                    }
                                });
                            } catch (e) {}

                            // Route through debounced single-point-of-truth
                            window._commitMediaSession(metadataObj, immediate);
                        };

                        // 1. Try to use provided artwork first
                        if (typeof info === 'object' && info !== null && (info.artwork || info.cover)) {
                            let artUrl = info.artwork || info.cover;
                            // Check if it's not the default one
                            if (!String(artUrl).includes(
                                '{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}'.split('/')
                                    .pop())) {
                                let finalArtwork = [];
                                if (Array.isArray(artUrl)) {
                                    finalArtwork = artUrl;
                                } else if (typeof artUrl === 'string' && artUrl.startsWith('http')) {
                                    finalArtwork = [{
                                        src: artUrl,
                                        sizes: '512x512',
                                        type: 'image/jpeg'
                                    }];
                                }

                                if (finalArtwork.length > 0) {
                                    // Clear old artwork and store new
                                    window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                                    window.storeArtworkForIOS(finalArtwork[0].src);

                                    // Single update with artwork â€” immediate flush since we have final art
                                    updateSessionMetadata(finalArtwork, true);
                                    // NOTE: Do NOT also call updateArtwork â€” that would be a duplicate
                                    return; // Done
                                }
                            }
                        }

                        // â”€â”€ Immediate MediaSession write with default artwork â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                        // Push title/artist/album to MediaSession instantly so lock screen
                        // and car displays update without waiting for the iTunes artwork
                        // search.  When iTunes resolves, artwork will be updated seamlessly
                        // via MediaMetadata mutation (no flicker on Android/iOS).
                        if (songHasChanged) {
                            // Persist title+artist to safeLocalStorage for cross-tab/page sync
                            try {
                                window.safeLocalStorage.setItem(window.getStationStorageKey('current_title'), title || '');
                                window.safeLocalStorage.setItem(window.getStationStorageKey('current_artist'), artist || '');
                                window.safeLocalStorage.setItem(window.getStationStorageKey('active_tab'), _tabSessionId);
                            } catch(e) {}
                            if (!window._storedArtworkSrc) { if (!window._storedArtworkSrc) { updateSessionMetadata([], true); } }
                        }

                        // â”€â”€ Clear stale artwork cache for new song â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                        if (songHasChanged || _mediaSessionIsStale) {
                            window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                            window._storedArtworkSrc = null;
                        }

                        // â”€â”€ Document title update is DEFERRED â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                        // document.title is now updated inside updateSessionMetadata()
                        // so it changes atomically with the MediaSession metadata commit.
                        // This prevents car/BT displays from picking up a title change
                        // (showing new text) before artwork has been resolved.

                        // â”€â”€ iTunes artwork search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                        // NOTE: We do NOT wipe existing artwork here.
                        // The existing artwork (from ios_current_artwork or previous song)

                        // stays visible while the async iTunes search runs.
                        // Default station artwork is ONLY applied at the two end-paths below:
                        //   â€¢ All 5 strategies exhausted with no match
                        //   â€¢ Unexpected fetch error
                        // This prevents the default logo from flashing on every song change.


                        if (artist && title && artist !== 'Now Playing' && title !== 'Live Stream') {


                            (async () => {

                                const cleanSearchTerm = (str) => {
                                    if (!str) return '';
                                    return str
                                        .replace(/\s*(?:feat\.?|ft\.?|f\.\/|featuring|with|x|&)\s+.*$/gi, '')
                                        .replace(/[\(\[\{].*?[\)\]\}]/g, '')
                                        .replace(/\s+-.*$/, '')
                                        .replace(/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/g, '')
                                        .replace(/\s{2,}/g, ' ')
                                        .trim();
                                };

                                function getHighResArtwork(result) {
                                    const url = result.artworkUrl100 || result.artworkUrl60 || result.artworkUrl30;
                                    if (!url) return null;
                                    const makeUrl = (size) => url.replace(/\b\d{2,4}x\d{2,4}bb\b/, `${size}x${size}bb`);
                                    return {
                                        jpg2048: makeUrl(2048),
                                        jpg1024: makeUrl(1024),
                                        jpg512: makeUrl(512),
                                        jpg384: makeUrl(384),
                                        jpg256: makeUrl(256),
                                        jpg192: makeUrl(192),
                                        jpg128: makeUrl(128),
                                        jpg96: makeUrl(96)
                                    };
                                }

                                const normalize = (str) => {
                                    if (!str) return '';
                                    const noDiacritics = (str.normalize) ?
                                        str.normalize('NFD').replace(/\p{Diacritic}/gu, '') : str;
                                    return noDiacritics
                                        .toLowerCase()
                                        .replace(/[''`]/g, '')
                                        .replace(/[^a-z0-9\s]/g, ' ')
                                        .replace(/\s+/g, ' ')
                                        .trim();
                                };

                                const _artistStopwords = new Set([
                                    'the', 'a', 'an', 'of', 'in', 'on', 'at', 'to', 'by', 'and', 'or', 'ft', 'feat'
                                ]);

                                const artistWords = (str) => new Set(
                                    normalize(str).split(/\s+/).filter(w => w.length > 1 && !_artistStopwords.has(w))
                                );

                                const wordOverlap = (a, b) => {
                                    const wa = new Set(a.split(/\s+/).filter(w => w.length > 1));
                                    const wb = new Set(b.split(/\s+/).filter(w => w.length > 1));
                                    if (wa.size === 0 || wb.size === 0) return 0;
                                    let matches = 0;
                                    wa.forEach(w => { if (wb.has(w)) matches++; });
                                    return matches / Math.max(wa.size, wb.size);
                                };

                                const artistMatches = (resultArtistName, knownArtist) => {
                                    const knownWords = artistWords(knownArtist);
                                    if (knownWords.size === 0) {
                                        return normalize(resultArtistName).includes(normalize(knownArtist));
                                    }
                                    const resultNorm = normalize(resultArtistName);
                                    if (knownWords.size === 1) {
                                        const knownWord = [...knownWords][0];
                                        if (resultNorm.includes(knownWord)) return true;
                                    }
                                    const resultWords = artistWords(resultArtistName);
                                    let matched = 0;
                                    knownWords.forEach(w => { if (resultWords.has(w)) matched++; });
                                    return matched > 0;
                                };

                                function pickBestResult(results, searchTitle, searchArtist) {
                                    if (!results || results.length === 0) return null;

                                    const normTitle = normalize(searchTitle);
                                    const normArtist = normalize(searchArtist);

                                    const validResults = results.filter(r =>
                                        r.trackName && (r.artworkUrl100 || r.artworkUrl60)
                                    );
                                    if (validResults.length === 0) return null;

                                    // Level 1: Exact title + exact artist
                                    const exactBoth = validResults.find(r =>
                                        normalize(r.trackName) === normTitle &&
                                        normalize(r.artistName) === normArtist
                                    );
                                    if (exactBoth) return exactBoth;

                                    // Level 2: Exact title + partial artist
                                    const exactTitlePartialArtist = validResults.find(r =>
                                        normalize(r.trackName) === normTitle &&
                                        normArtist.length > 1 &&
                                        (normalize(r.artistName).includes(normArtist) ||
                                            normArtist.includes(normalize(r.artistName)))
                                    );
                                    if (exactTitlePartialArtist) return exactTitlePartialArtist;

                                    // Level 3: High word-overlap on BOTH title AND artist
                                    const highOverlapBoth = validResults.find(r =>
                                        normArtist.length > 1 &&
                                        wordOverlap(normalize(r.trackName), normTitle) >= 0.8 &&
                                        wordOverlap(normalize(r.artistName), normArtist) >= 0.6
                                    );
                                    if (highOverlapBoth) return highOverlapBoth; return validResults[0] || null; return validResults[0] || null;

                                    return null;
                                }

                                async function itunesSearch(query) {
                                    try {
                                        const url = `/proxy/itunes-search?term=${encodeURIComponent(query)}`;
                                        const response = await fetch(url);
                                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                                        return await response.json();
                                    } catch (e) {
                                        return null;
                                    }
                                }

                                try {
                                    let artworkUrl = null;

                                    const cleanTitle = cleanSearchTerm(title);
                                    const cleanArtist = cleanSearchTerm(artist);

                                    // â”€â”€ Strategy 1: Artist + Title â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                                    if (!artworkUrl && cleanArtist.length >= 2 && cleanTitle.length >= 2) {
                                        const query = `${cleanArtist} ${cleanTitle}`;
                                        const data = await itunesSearch(query);
                                        if (data?.results?.length > 0) {
                                            const best = pickBestResult(data.results, cleanTitle, cleanArtist);
                                            if (best) {
                                                artworkUrl = getHighResArtwork(best);
                                                if (best.trackTimeMillis) {
                                                    window._currentSongDuration = best.trackTimeMillis / 1000;
                                                    window.updatePositionState(window._currentSongElapsed, window._currentSongDuration);
                                                }
                                            }
                                        }
                                    }

                                    // â”€â”€ Strategy 2: Title + Artist (reversed) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                                    if (!artworkUrl && cleanTitle.length >= 2 && cleanArtist.length >= 2) {
                                        const query = `${cleanTitle} ${cleanArtist}`;
                                        const data = await itunesSearch(query);
                                        if (data?.results?.length > 0) {
                                            const best = pickBestResult(data.results, cleanTitle, cleanArtist);
                                            if (best) {
                                                artworkUrl = getHighResArtwork(best);
                                                if (best.trackTimeMillis) {
                                                    window._currentSongDuration = best.trackTimeMillis / 1000;
                                                    window.updatePositionState(window._currentSongElapsed, window._currentSongDuration);
                                                }
                                            }
                                        }
                                    }

                                    if (artworkUrl) {
                                        let finalArtworkArray = [];
                                        const sizesMap = [
                                            { key: 'jpg2048', size: '2048x2048' },
                                            { key: 'jpg1024', size: '1024x1024' },
                                            { key: 'jpg512',  size: '512x512' },
                                            { key: 'jpg384',  size: '384x384' },
                                            { key: 'jpg256',  size: '256x256' },
                                            { key: 'jpg192',  size: '192x192' },
                                            { key: 'jpg128',  size: '128x128' },
                                            { key: 'jpg96',   size: '96x96' }
                                        ];

                                        sizesMap.forEach(item => {
                                            if (artworkUrl[item.key]) {
                                                finalArtworkArray.push({ src: artworkUrl[item.key], sizes: item.size, type: 'image/jpeg' });
                                            }
                                        });

                                        if (artworkUrl.jpg1024) {
                                            window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                                            const base64Art = await window.storeArtworkForIOS(artworkUrl.jpg1024);
                                            if (base64Art) {
                                                finalArtworkArray.unshift({ src: base64Art, sizes: '1024x1024', type: 'image/jpeg' });
                                            }
                                        }

                                        // IMMEDIATE flush â€” authoritative iTunes artwork
                                        updateSessionMetadata(finalArtworkArray, true);
                                        return;
                                    }

                                } catch (e) {
                                    window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                                    window._storedArtworkSrc = null;
                                    if (!window._storedArtworkSrc) { if (!window._storedArtworkSrc) { updateSessionMetadata([], true); } }
                                }

                                // Both strategies exhausted â€” show default station artwork (immediate)
                                window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                                window._storedArtworkSrc = null;
                                if (!window._storedArtworkSrc) { if (!window._storedArtworkSrc) { updateSessionMetadata([], true); } }

                            })();
                        } else {
                            // No valid artist/title to search iTunes â€” apply default immediately
                            window.safeLocalStorage.removeItem(window.getStationStorageKey('ios_current_artwork'));
                            window._storedArtworkSrc = null;
                            if (!window._storedArtworkSrc) { if (!window._storedArtworkSrc) { updateSessionMetadata([], true); } }
                        }
                    } else {
                        console.warn('Media Session API not supported in this browser');
                    }
                } catch (e) {
                    console.error('Error updating document title or Media Session:', e);
                }

                // Make sure the player is visible
                if (stickyPlayer) {
                    stickyPlayer.style.display = 'flex';
                }
            }

            // Expose as global so window.updateMediaSessionWithArtwork can delegate here
            window.updateSongInfo = updateSongInfo;

            // Function to get the next metadata URL to try
            function getNextMetadataUrl() {
                currentEndpointIndex++;
                if (currentEndpointIndex >= metadataEndpoints.length) {
                    currentEndpointIndex = 0;
                    currentProxyIndex = (currentProxyIndex + 1) % corsProxies.length;
                }

                const baseUrl = streamUrl.replace(/\/$/, ''); // Remove trailing slash if exists
                const endpoint = metadataEndpoints[currentEndpointIndex];
                const proxy = corsProxies[currentProxyIndex];

                // Special handling for corsproxy.io which needs the URL as a query parameter
                if (proxy.includes('corsproxy.io')) {
                    return proxy + encodeURIComponent(baseUrl + endpoint);
                }

                return proxy + baseUrl + endpoint;
            }

            // â”€â”€ Global artwork + MediaSession update function â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Called from: .old-style code, SSE metadata, restorePersistedMetadata, etc.
            // Delegates iTuens search to updateSongInfo() which has the full 4-strategy logic.
            window.updateMediaSessionWithArtwork = function (artist, title, providedArtwork = null) {
                try {
                    const appName = @json(config('app.name'));
                    const stationName = appName;

                    // document.title is now deferred to updateSessionMetadata() so it
                    // changes atomically with artwork on car/BT displays.

                    if (!('mediaSession' in navigator)) return;

                    // Default station artwork â€” always used as fallback
                    const baseUrl = window.location.origin;
                    const defaultArtwork = (typeof buildDefaultArtwork === 'function')
                        ? buildDefaultArtwork()
                        : [
                            { src: baseUrl + '/{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}', sizes: '512x512', type: 'image/jpeg' },
                            { src: baseUrl + '/{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}', sizes: '1024x1024', type: 'image/jpeg' },
                            { src: baseUrl + '/{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}', sizes: '2048x2048', type: 'image/jpeg' }
                        ];

                    // Inner helper: push artwork + metadata through debounced commit
                    const commitToMediaSession = (artwork, immediate = false) => {
                        const metadataObj = {
                            title: title || 'Live Stream',
                            artist: artist || stationName,
                            album: getAlbumTitle(),
                            artwork: artwork
                        };
                        window._commitMediaSession(metadataObj, immediate);
                    };

                    // 1. Use provided artwork if available (e.g. from stream metadata)
                    if (providedArtwork) {
                        let art = providedArtwork;
                        const isDefault = String(art).includes('alexaicon') || String(art).includes('alexaicontrans');
                        if (!isDefault) {
                            const artArr = Array.isArray(art) ? art
                                : typeof art === 'string' && art.startsWith('http')
                                    ? [{ src: art, sizes: '512x512', type: 'image/jpeg' }]
                                    : [];
                            if (artArr.length > 0) {
                                // Immediate flush â€” we have final artwork
                                commitToMediaSession(artArr, true);
                                // Also push through updateSongInfo for full DOM update
                                if (typeof window.updateSongInfo === 'function') {
                                    window.updateSongInfo({ artist, title, song: title, artwork: art });
                                }
                                return;
                            }
                        }
                    }

                    // 2. DO NOT set default artwork here â€” updateSongInfo will wait
                    //    for iTunes to resolve before making the ONLY MediaSession write.

                    // 3. Kick off the full iTunes artwork search via updateSongInfo.
                    //    updateSongInfo will NOT write to MediaSession until iTunes resolves:
                    //      â€¢ iTunes artwork found â†’ single write with real artwork
                    //      â€¢ iTunes failed â†’ single write with default artwork
                    if (artist && title && artist !== stationName &&
                        title !== 'Live Stream' && title !== 'Unknown Song') {
                        if (typeof window.updateSongInfo === 'function') {
                            window.updateSongInfo({ artist, title, song: title });
                        }
                    } else {
                        // No searchable metadata â€” commit default artwork now
                        commitToMediaSession(defaultArtwork, true);
                    }

                } catch (e) {
                    console.error('updateMediaSessionWithArtwork error:', e);
                }
            };

            // Initialize Media Session API â€” routes through _commitMediaSession
            function updateMediaSession(meta) {
                if (!('mediaSession' in navigator)) {
                    return;
                }

                const baseUrl = window.location.origin;
                const defaultArtwork = (typeof buildDefaultArtwork === 'function') ?
                    buildDefaultArtwork() : [{
                        src: baseUrl + '/' + '{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}',
                        sizes: '128x128',
                        type: 'image/jpeg'
                    }];

                let artwork = defaultArtwork;

                if (meta && (meta.artwork || meta.cover) && !String(meta.artwork || meta.cover).includes(
                    '{{ \App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')) }}'.split('/').pop())) {
                    if (Array.isArray(meta.artwork)) {
                        artwork = meta.artwork;
                    } else {
                        artwork = [{
                            src: meta.artwork || meta.cover,
                            sizes: '300x300',
                            type: 'image/jpeg'
                        },
                        {
                            src: meta.artwork || meta.cover,
                            sizes: '128x128',
                            type: 'image/jpeg'
                        },
                        {
                            src: meta.artwork || meta.cover,
                            sizes: '256x256',
                            type: 'image/jpeg'
                        },
                        ...defaultArtwork
                        ];
                    }
                }

                const stationName = '{{ config('app.name') }}';
                const songTitle = meta ? (meta.song || meta.title || 'Live Stream') : 'Live Stream';
                const artistName = meta ? (meta.artist || stationName) : stationName;

                const metadataObj = {
                    title: songTitle,
                    artist: artistName,
                    album: getAlbumTitle(),
                    artwork: artwork
                };

                // Route through debounced single-point-of-truth
                window._commitMediaSession(metadataObj, true);

                if (typeof window.configureLiveMediaSessionActions === 'function') {
                    window.configureLiveMediaSessionActions();
                }
            }

            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Thin play / pause helpers.
            // These simply delegate to the real implementation in
            // scripts-core-player.blade.php (toggleLiveAudio) so that all code
            // paths â€” play button, metadata script, BT resume â€” share exactly
            // one implementation and never get out of sync.
            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            window.doPlay = function (opts) {
                // If audio is already playing, nothing to do.
                var _a = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                if (_a && !_a.paused) {
                    return;
                }
                if (typeof window.toggleLiveAudio === 'function') {
                    window.toggleLiveAudio();
                } else {
                    // Fallback if toggleLiveAudio not yet available
                    if (_a) {
                        userPaused = false;
                        systemPaused = false;
                        _a.play().catch(function(e) { console.warn('[doPlay fallback]', e.message); });
                    }
                }
            };

            window.doPause = function (opts) {
                // If audio is already paused and user-initiated, nothing to do.
                var _a = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                if (_a && _a.paused) {
                    return;
                }
                if (typeof window.toggleLiveAudio === 'function') {
                    window.toggleLiveAudio();
                } else {
                    // Fallback
                    if (_a && !_a.paused && _a.pause) {
                        userPaused = true;
                        try { _a.pause(); } catch(e) {}
                    }
                }
            };
            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

            // Call immediately on load - restore persisted metadata artwork if available
            // WITH THIS:
            (function () {
                if (!('mediaSession' in navigator)) return;

                // Check for persisted metadata to trigger immediate iTunes search
                const stored = window.safeLocalStorage.getItem(window.getStationStorageKey('current_song_metadata'));
                if (stored) {
                    try {
                        const m = JSON.parse(stored);
                        if (m.artist && m.title && Date.now() - m.timestamp < 600000) {
                            // If updateSongInfo is available globally, use it to start the fetch
                            if (typeof window.updateSongInfo === 'function') {
                                window.updateSongInfo({
                                    artist: m.artist,
                                    title: m.title,
                                    song: m.title
                                });
                            } else {
                                // Fallback if not initialized yet
                                setTimeout(() => {
                                    if (typeof window.updateSongInfo === 'function') {
                                        window.updateSongInfo({
                                            artist: m.artist,
                                            title: m.title,
                                            song: m.title
                                        });
                                    }
                                }, 500);
                            }
                            return;
                        }
                    } catch (e) { }
                }
            })();
            // Function set up Radiomast SSE metadata streaming
            function setupRadiomastMetadata() {
                try {
                    // Close any existing connection first
                    if (window.radiomastEventSource) {
                        window.radiomastEventSource.close();
                        window.radiomastEventSource = null;
                    }

                    const metadataUrl = streamUrl + '/metadata';
                    const eventSource = new EventSource(metadataUrl);
                    let reconnectAttempts = 0;
                    const maxReconnectAttempts = 3;

                    eventSource.onopen = function () {
                        reconnectAttempts = 0; // Reset on successful connection
                    };

                    eventSource.onmessage = function (event) {
                        try {
                            const metadata = JSON.parse(event.data);
                            const artistTitle = metadata['metadata'];

                            // â”€â”€ Extract song timing from SSE payload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                            // Radiomast sends elapsed/duration in seconds alongside the
                            // metadata string. We read them here so we can forward them to
                            // navigator.mediaSession.setPositionState() for widget/car display.
                            const sseElapsed = (typeof metadata['elapsed'] === 'number') ? metadata['elapsed'] :
                                (typeof metadata['elapsed'] === 'string') ? parseFloat(metadata['elapsed']) : null;
                            let sseDurationRaw = (typeof metadata['duration'] === 'number') ? metadata['duration'] :
                                (typeof metadata['duration'] === 'string') ? parseFloat(metadata['duration']) :
                                    (typeof metadata['song_length'] === 'number') ? metadata['song_length'] :
                                        (typeof metadata['song_length'] === 'string') ? parseFloat(metadata['song_length']) : null;

                            let sseDuration = sseDurationRaw;
                            if (sseDuration > 1000000000) {
                                // It's a timestamp, not a duration. Ignore it so iTunes can fill it.
                                sseDuration = null;
                            } else if (sseDuration > 36000) {
                                // Larger than 10 hours? Likely milliseconds, convert to seconds
                                sseDuration = sseDuration / 1000;
                            }

                            if (artistTitle) {
                                let artist = '{{ config('app.name') }}';
                                let title = artistTitle;

                                // Parse the metadata string
                                if (artistTitle.includes('-')) {
                                    const parts = artistTitle.split('-');
                                    if (parts.length >= 2) {
                                        title = parts[0].trim(); // First part is TITLE
                                        artist = parts[1].trim(); // Second part is ARTIST
                                    }
                                }

                                // Check if this is generic metadata
                                const stationLower = '{{ strtolower(config('app.name')) }}';
                                const isGeneric = title.toLowerCase() === stationLower ||
                                    artist === 'Live Stream' ||
                                    title === 'Live Stream' ||
                                    artist.toLowerCase() === stationLower;

                                if (isGeneric) {
                                    if (restorePersistedMetadata()) {
                                        // Still update position state even on generic metadata
                                        if (sseDuration > 0) {
                                            window.updatePositionState(sseElapsed, sseDuration);
                                        }
                                        return;
                                    }
                                }

                                // â”€â”€ Always update metadata on song change â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                                // Update MediaSession, document.title, DOM, and trigger
                                // iTunes artwork search regardless of play/pause state.
                                // _commitMediaSession preserves playbackState='paused'
                                // after each write, preventing unwanted auto-resume.
                                updateSongInfo({
                                    title: title,
                                    artist: artist,
                                    song: title,
                                    artwork: metadata.artwork || metadata.cover || null
                                });

                                // â”€â”€ Forward song timing to Media Session â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                                if (sseDuration > 0) {
                                    window._currentSongDuration = sseDuration;
                                    window._currentSongElapsed = sseElapsed >= 0 ? sseElapsed : 0;
                                    window._songTimingTimestamp = Date.now();
                                    window.updatePositionState(window._currentSongElapsed, window._currentSongDuration);
                                } else {
                                    window._currentSongDuration = 0;
                                }
                            }
                        } catch (error) {
                            console.error('Error parsing metadata:', error);
                        }
                    };

                    eventSource.onerror = function (error) {
                        console.error('EventSource error:', error);

                        // Close the connection
                        eventSource.close();

                        reconnectAttempts++;

                        if (reconnectAttempts < maxReconnectAttempts) {
                            if (window.radiomastReconnectTimer) {
                                clearTimeout(window.radiomastReconnectTimer);
                            }
                            window.radiomastReconnectTimer = setTimeout(setupRadiomastMetadata, 5000 * reconnectAttempts);
                        } else {
                            console.warn(
                                'Max reconnection attempts reached. Switching to fallback method.');
                            // Switch to the old polling method
                            startFallbackPolling();
                        }
                    };

                    // Store the eventSource for cleanup
                    window.radiomastEventSource = eventSource;

                } catch (error) {
                    console.error('Failed to setup Radiomast metadata:', error);
                    // Immediately fallback to old method
                    startFallbackPolling();
                }
            }

            // New function for fallback polling
            function startFallbackPolling() {
                // Clear any existing interval
                if (window.fallbackMetadataInterval) {
                    clearInterval(window.fallbackMetadataInterval);
                }

                // Initial fetch
                fetchMetadata().catch(console.error);

                // Poll every 30 seconds
                window.fallbackMetadataInterval = setInterval(() => {
                    fetchMetadata().catch(console.error);
                }, 3000);
            }

            // Function to fetch metadata from the stream (fallback method)
            async function fetchMetadata() {
                if (!streamUrl) {
                    console.error('Stream URL is empty or undefined');
                    updateSongInfo('Stream URL not configured');
                    return;
                }

                // If using a blob URL, try to get the original stream URL
                let metadataUrl = streamUrl;
                if (streamUrl.startsWith('blob:')) {
                    // Try to get the original stream URL from the audio element
                    if (audio && audio.src && !audio.src.startsWith('blob:')) {
                        metadataUrl = audio.src;
                    } else {
                        metadataUrl = "{{ \App\Models\SiteSetting::get('stream_url', env('STREAM_URL', '')) }}/metadata";
                    }
                }

                // Try to restore persisted metadata instead of showing loading state
                if (!restorePersistedMetadata()) {
                    // no loading flash
                }

                try {
                    // 1. First try: Server-side endpoint with direct stream URL
                    try {
                        const response = await fetch(
                            `/api/stream-metadata?streamUrl=${encodeURIComponent(metadataUrl)}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Cache-Control': 'no-cache',
                                'Pragma': 'no-cache',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            cache: 'no-store',
                            credentials: 'same-origin'
                        });

                        if (response.ok) {
                            const data = await response.json();

                            if (data && (data.title || data.artist || data.song)) {
                                // Pass the entire data object to handle structured metadata

                                const _ssAudio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                                const _ssUserPaused = window._smartPlayer ? window._smartPlayer.userPaused : false;
                                const _ssSystemPaused = (typeof systemPaused !== 'undefined') ? systemPaused : false;
                                const _ssPaused = false;

                                if (_ssPaused) {
                                    const _artEl = document.getElementById('current-artist');
                                    const _titEl = document.getElementById('current-title');
                                    if (_artEl) _artEl.textContent = data.artist || '';
                                    if (_titEl) _titEl.textContent = data.title || data.song || '';
                                    if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
                                } else {
                                    updateSongInfo({
                                        artist: data.artist,
                                        song: data.song,
                                        title: data.title
                                    });
                                }
                                return true;
                            }
                        }
                    } catch (serverError) {
                        // Server metadata fallback failed
                    }


                    // 3. Third try: Direct stream metadata endpoints (Icecast/Shoutcast)
                    const endpoints = [
                        '/status-json.xsl',
                        '/status-json',
                        '/status.xsl',
                        '/status',
                        '/7.html',
                        '/currentsong',
                        '/now_playing',
                        '/streaminfo',
                        '/nowplaying',
                        '/api/nowplaying'
                    ];

                    for (const endpoint of endpoints) {
                        try {
                            const url = new URL(streamUrl);
                            const metadataUrl = `${url.origin}${endpoint}`;

                            const response = await fetch(metadataUrl, {
                                method: 'GET',
                                cache: 'no-store',
                                headers: {
                                    'Accept': 'application/json, text/plain, */*',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            if (!response.ok) {
                                continue;
                            }

                            const contentType = response.headers.get('content-type') || '';
                            let data;

                            try {
                                if (contentType.includes('application/json')) {
                                    data = await response.json();
                                } else {
                                    const text = await response.text();
                                    // Try to parse as JSON even if content-type doesn't match
                                    try {
                                        data = JSON.parse(text);
                                    } catch {
                                        data = text;
                                    }
                                }

                                // Try to extract title from common formats
                                let title = '';

                                // Check for common JSON formats
                                if (data && typeof data === 'object') {
                                    const source = data.icestats?.source ||
                                        data.shoutcast?.source ||
                                        data.source ||
                                        (data.icestats?.sources && data.icestats.sources[0]) ||
                                        (data.shoutcast?.sources && data.shoutcast.sources[0]);

                                    title = source?.title ||
                                        data.now_playing?.song?.text ||
                                        data.now_playing?.song?.title ||
                                        data.songtitle ||
                                        data.title ||
                                        data.now_playing?.title ||
                                        data.stream_title;
                                }
                                // Check for SHOUTcast 7.html format (CSV)
                                else if (typeof data === 'string' && data.includes(',')) {
                                    const parts = data.split(',');
                                    if (parts.length >= 7) {
                                        title = parts[6].trim();
                                    }
                                }
                                // Check for plain text or HTML response
                                else if (typeof data === 'string') {
                                    const rawText = data.trim();

                                    // Check if it's HTML
                                    if (rawText.includes('<') && rawText.includes('>')) {
                                        // Try to extract song from Icecast status page pattern
                                        // Look for: <td class="streamdata">Song Name</td>
                                        // or: Current Song:</td><td class="streamdata">Song Name</td>
                                        const songMatch = rawText.match(
                                            /Current Song:.*?<td[^>]*>(.*?)<\/td>/i) ||
                                            rawText.match(/<td[^>]*class="?streamdata"?[^>]*>(.*?)<\/td>/i);

                                        if (songMatch && songMatch[1]) {
                                            let extracted = songMatch[1].replace(/<[^>]+>/g, '').trim();
                                            if (extracted && extracted !== 'Content Type' && extracted !==
                                                'Mount Point') {
                                                title = extracted;
                                            }
                                        }
                                    }
                                    // If not HTML, check if it's a simple text string
                                    else if (rawText.length > 0) {
                                        // Aggressive cleaning for concatenated text like "Song TitleMount Point..."
                                        let cleanedTitle = rawText;

                                        // Split at common server labels that immediately follow the song title
                                        const delimiters = [
                                            'Mount Point',
                                            'Stream Title',
                                            'Stream Description',
                                            'Content Type',
                                            'Bitrate',
                                            'Current Listeners'
                                        ];

                                        for (const delimiter of delimiters) {
                                            if (cleanedTitle.includes(delimiter)) {
                                                cleanedTitle = cleanedTitle.split(delimiter)[0].trim();
                                            }
                                        }

                                        // Clean up "Current Song:" prefix if present
                                        cleanedTitle = cleanedTitle.replace(/^Current Song:\s*/i, '');

                                        // Final validation
                                        if (cleanedTitle && cleanedTitle.length < 100 &&
                                            !delimiters.some(d => cleanedTitle.toLowerCase().includes(d
                                                .toLowerCase()))) {
                                            title = cleanedTitle;
                                        }
                                    }
                                }

                                if (title) {
                                    const _ssAudio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                                    const _ssUserPaused = window._smartPlayer ? window._smartPlayer.userPaused : false;
                                    const _ssSystemPaused = (typeof systemPaused !== 'undefined') ? systemPaused : false;
                                    const _ssPaused = false;

                                    if (_ssPaused) {
                                        const _artEl = document.getElementById('current-artist');
                                        const _titEl = document.getElementById('current-title');
                                        if (_titEl) _titEl.textContent = title;
                                        if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
                                    } else {
                                        updateSongInfo(title);
                                    }
                                    return true;
                                }

                            } catch (parseError) {
                                // Skip unparseable fallback endpoint
                            }

                        } catch (endpointError) {
                            // Skip unreachable fallback endpoint
                        }
                    }

                    // 3. Final fallback: Audio element metadata
                    const success = updateSongInfoFromAudio();
                    if (success) {
                        return true;
                    }

                    // CRITICAL FIX: Do NOT reset to "Live Stream" if we have valid metadata displayed.
                    // Only show fallback if we are currently showing a loading state or nothing.
                    const currentTitle = document.querySelector('.player-song')?.textContent;
                    const isGeneric = !currentTitle ||
                        currentTitle === 'Loading metadata...' ||
                        currentTitle === 'Live Stream' ||
                        currentTitle === 'Radio Station';

                    if (isGeneric) {
                        // Try to restore persisted metadata instead of showing fallback
                        if (!restorePersistedMetadata()) {
                            // no generic overwrite
                        }
                    }

                    return false;

                } catch (error) {
                    console.error('Critical error in fetchMetadata:', error);
                    // Try to restore persisted metadata instead of showing error
                    if (!restorePersistedMetadata()) {
                        updateSongInfo('Error loading metadata');
                    }
                    return false;
                }
            }

            // Function to update song info from audio element metadata
            function updateSongInfoFromAudio() {
                try {
                    const audio = document.getElementById('mini-player-audio');
                    if (!audio) {
                        console.warn('Audio element not found');
                        return false;
                    }

                    // Try to get metadata from audio element
                    const title = audio.getAttribute('data-title') || audio.title || '';
                    const artist = audio.getAttribute('data-artist') || '';

                    if (title || artist) {
                        const metadata = {
                            title: title || 'Unknown Song',
                            artist: artist || 'Unknown Artist'
                        };
                        updateSongInfo(metadata);
                        return true;
                    }

                    // Try to extract from audio src if available
                    const src = audio.src || '';
                    if (src && src.includes('title=')) {
                        try {
                            const url = new URL(src);
                            const title = url.searchParams.get('title');
                            const artist = url.searchParams.get('artist');

                            if (title || artist) {
                                const metadata = {
                                    title: title || 'Unknown Song',
                                    artist: artist || 'Unknown Artist'
                                };
                                updateSongInfo(metadata);
                                return true;
                            }
                        } catch (e) {
                            console.warn('Could not parse URL for metadata:', e);
                        }
                    }

                    return false;

                } catch (error) {
                    console.error('Error in updateSongInfoFromAudio:', error);
                    return false;
                }
            }

            // Helper function to fetch with credentials and handle CORS
            async function fetchWithFallback(url, useProxy = false) {
                try {
                    const init = {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store',
                        credentials: useProxy ? 'omit' : 'include',
                        mode: useProxy ? 'cors' : 'no-cors'
                    };

                    return await fetch(url, init);
                } catch (error) {
                    console.error('Fetch error:', error);
                    throw error;
                }
            }

            // Fallback to rotating metadata when we can't fetch from the stream
            function useFallbackMetadata() {
                const fallback = fallbackMetadata[fallbackIndex];
                // Use updateSongInfo to ensure persistence system is respected
                updateSongInfo({
                    artist: fallback.artist,
                    title: fallback.title,
                    song: fallback.title
                });
                fallbackIndex = (fallbackIndex + 1) % fallbackMetadata.length;
            }

            // Fallback method using audio element (slower)
            function fetchMetadataFallback() {
                if (retryCount >= maxRetries) return;

                const audioMeta = new Audio(streamUrl);
                // Do NOT set crossOrigin = 'anonymous' because RadioMast blocks CORS Origin requests with 403 Forbidden
                audioMeta.preload = 'metadata';

                const cleanup = () => {
                    try {
                        audioMeta.pause();
                        audioMeta.removeAttribute('src');
                        audioMeta.load();
                    } catch (e) {}
                };

                const timeout = setTimeout(() => {
                    cleanup();
                    retryCount++;
                }, 3000);

                audioMeta.onloadedmetadata = function () {
                    clearTimeout(timeout);
                    if (audioMeta.metadata && audioMeta.metadata.title) {
                        updateSongInfo(audioMeta.metadata.title);
                        retryCount = 0;
                    }
                    cleanup();
                };

                audioMeta.onerror = function () {
                    clearTimeout(timeout);
                    cleanup();
                    retryCount++;
                };

                try {
                    audioMeta.load();
                } catch (e) {
                    console.error('Error loading audio metadata:', e);
                    cleanup();
                }
            }

            // Set up the audio element for playback
            if (audio) {
                // Ensure no crossOrigin is set so browser plays direct stream without CORS block
                audio.removeAttribute('crossorigin');

                // Listen for play events - delegate directly to window.toggleLiveAudio
                document.querySelectorAll('.listen-btn').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (typeof window.toggleLiveAudio === 'function') {
                            window.toggleLiveAudio();
                        } else if (audio.paused) {
                            audio.play().catch(e => console.error('Play error:', e));
                        } else {
                            audio.pause();
                        }
                    });
                });

                // Set up play/pause button in the sticky player - delegate directly to window.toggleLiveAudio
                const playBtn = document.getElementById('play-btn');
                if (playBtn) {
                    playBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (typeof window.toggleLiveAudio === 'function') {
                            window.toggleLiveAudio();
                        } else if (audio.paused) {
                            audio.play().catch(e => console.error('Play error:', e));
                        } else {
                            audio.pause();
                        }
                    });
                }

                // Clean up on page unload
                window.addEventListener('beforeunload', function () {
                    if (metadataInterval) {
                        clearInterval(metadataInterval);
                    }
                });
            }

            // Function to check audio element for metadata
            function checkAudioMetadata() {
                if (!audio) return;

                try {
                    // Check for metadata events
                    audio.onloadedmetadata = function () {};

                    // Check for errors
                    audio.onerror = function () {
                        console.error('Audio element error:', audio.error);
                    };

                    // Check for metadata updates
                    if ('mediaSession' in navigator) {
                        navigator.mediaSession.setActionHandler('play', () => {
                            window.userPaused = false;
                            if (typeof window.setAudioInterrupted === 'function') window.setAudioInterrupted(false);
                            if (typeof savePlayingIntent === 'function') savePlayingIntent(true);
                            if (typeof window.recoverLiveStream === 'function') {
                                window.recoverLiveStream('mediasession-play');
                            } else if (typeof window.toggleLiveAudio === 'function') {
                                window.toggleLiveAudio(true);
                            } else if (audio && audio.play) {
                                audio.play().catch(e => {});
                            }
                        });
                        navigator.mediaSession.setActionHandler('pause', () => {
                            window.userPaused = true;
                            if (typeof window.setAudioInterrupted === 'function') window.setAudioInterrupted(false);
                            if (typeof savePlayingIntent === 'function') savePlayingIntent(false);
                            if (typeof RadioAudio !== 'undefined' && RadioAudio.pause) {
                                RadioAudio.pause();
                            } else if (audio && audio.pause) {
                                audio.pause();
                            }
                            if (typeof window.setPlayerState === 'function' && window.PLAYER_STATE) {
                                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                            }
                        });

                        if (typeof window.configureLiveMediaSessionActions === 'function') {
                            window.configureLiveMediaSessionActions();
                        }
                    }

                } catch (e) {
                    console.error('Error checking audio metadata:', e);
                }
            }

            // Function to initialize the player
            // Global variable to store the metadata update interval

            function initializePlayer() {
                // Show the player immediately
                if (stickyPlayer) {
                    stickyPlayer.style.display = 'flex';
                } else {
                    console.error('Sticky player element not found');
                }

                // Set initial metadata - try to restore persisted metadata first
                if (!restorePersistedMetadata()) {
                    // no loading flash
                }

                // Set up audio element if it exists
                if (audio) {
                    // Set the audio source
                    audio.src = streamUrl;
                    audio.preload = 'metadata';
                    audio.removeAttribute('crossorigin');

                    // Store the original stream URL for metadata fetching
                    audio.dataset.originalSrc = streamUrl;

                    // Set up event listeners
                    audio.onplay = function () {
                        if (miniPlayIcon) {
                            miniPlayIcon.className = 'fas fa-pause';
                        }
                        // Start updating metadata when playback starts
                        startMetadataUpdates();

                        // Try to get metadata from audio element
                        setTimeout(() => {
                            // Try to extract from audio attributes
                            const title = '';
                            const artist = '';

                            if (title || artist) {
                                updateSongInfo({
                                    title,
                                    artist
                                });
                            }
                        }, 2000); // Check after 2 seconds
                    };

                    // â”€â”€ System-pause detection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                    // When the OS/BT causes an unexpected pause (phone call, BT disconnect,
                    // audio focus loss), set systemPaused so the next Play press knows to
                    // reload the stream first on resume.
                    // NOTE: We do NOT call audio.load() here â€” that would make 'canplay' fire
                    // immediately (stream pre-buffering), before BT has actually reconnected,
                    // causing audio.play() to route to no output device.
                    audio.addEventListener('pause', function () {
                        if (!userPaused) {
                            systemPaused = true;
                        }
                    });

                    audio.onerror = function () {
                        console.error('Audio element error:', audio.error);
                        // Try to restore persisted metadata instead of showing error
                        if (!restorePersistedMetadata()) {
                            updateSongInfo('Error loading stream');
                        }
                    };

                    audio.onloadedmetadata = function () {
                        // Try to get metadata from the audio element
                        if (audio.mozHasAudioMetadata || audio.webkitAudioDecodedByteCount) {
                            updateSongInfoFromAudio();
                        } else {
                            // Try to restore persisted metadata instead of showing fallback
                            if (!restorePersistedMetadata()) {
                                // no generic overwrite
                            }
                        }
                    };

                    // Audio element ready; playback will start when user interacts
                } else {
                    console.error('Audio element not found');
                    // Try to restore persisted metadata instead of showing error
                    if (!restorePersistedMetadata()) {
                        updateSongInfo('Player not available');
                    }
                }

                // Set up play/pause button (initializePlayer path)
                // Routes through the unified doPlay/doPause dispatcher so flags,
                // _smartPlayer intent, MediaSession state, and button icon are always
                // updated atomically by a single code path.
                const playBtnInit = document.getElementById('play-btn');
                if (playBtnInit) {
                    playBtnInit.onclick = function () {
                        const isAudioPaused = audio ? audio.paused : true;
                        if (isAudioPaused) {
                            // User-initiated play
                            window.doPlay && window.doPlay({ force: systemPaused });
                        } else {
                            // User-initiated pause
                            window.doPause && window.doPause({ user: true });
                        }
                    };
                }
            }

            function startMetadataUpdates() {
                // First, restore persisted metadata
                setTimeout(() => {
                    restorePersistedMetadata();
                }, 100);

                // Clear any existing updates
                stopMetadataUpdates();

                // Try SSE first, but with a timeout
                startFallbackPolling(); return;

                // Set a timeout - if SSE doesn't connect in 10 seconds, use fallback
                if (window.radiomastTimeoutTimer) {
                    clearTimeout(window.radiomastTimeoutTimer);
                }
                window.radiomastTimeoutTimer = setTimeout(() => {
                    if (!window.radiomastEventSource || window.radiomastEventSource.readyState !==
                        EventSource.OPEN) {
                        startFallbackPolling();
                    }
                }, 10000);
            }

            function stopMetadataUpdates() {
                // Clear the main interval
                if (metadataInterval) {
                    clearInterval(metadataInterval);
                    metadataInterval = null;
                }

                // Clear fallback interval
                if (window.fallbackMetadataInterval) {
                    clearInterval(window.fallbackMetadataInterval);
                    window.fallbackMetadataInterval = null;
                }

                // Clear timeout timers
                if (window.radiomastTimeoutTimer) {
                    clearTimeout(window.radiomastTimeoutTimer);
                    window.radiomastTimeoutTimer = null;
                }

                if (window.radiomastReconnectTimer) {
                    clearTimeout(window.radiomastReconnectTimer);
                    window.radiomastReconnectTimer = null;
                }

                // Close EventSource connection
                if (window.radiomastEventSource) {
                    window.radiomastEventSource.close();
                    window.radiomastEventSource = null;
                }
            }

            // Clean up on page unload
            window.addEventListener('beforeunload', function () {
                if (window.metadataInterval) {
                    clearInterval(window.metadataInterval);
                }
                // Mark that the tab was explicitly navigated away from.
                // This prevents auto-resume from firing on a fresh page load
                // (which would be unexpected autoplay). BT reconnect while the
                // same page is in memory (BFCache / just screen-off) fires
                // visibilitychange WITHOUT beforeunload, so jammin_tab_closed_at
                // will NOT be set in that case â†’ BT auto-resume still works.
                try {
                    window.safeLocalStorage.setItem(window.getStationStorageKey('tab_closed_at'), Date.now());
                } catch(e) {}
            });

            // â”€â”€ Issue 4: Bluetooth Reconnect Auto-Resume â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // Three-layer approach to cover all reconnect scenarios:
            //
            //   Layer 1 â€“ navigator.mediaDevices 'devicechange': fires when ANY audio
            //             device connects or disconnects (the real BT API). Handles the
            //             case where the page is already visible when BT reconnects
            //             (visibilitychange/focus won't re-fire in that case).
            //
            //   Layer 2 â€“ visibilitychange / focus: fires when the page goes
            //             backgroundâ†’foreground (screen unlock, app switch).
            //
            //   Layer 3 â€“ Periodic poll every 3 s: safety net for the cases where
            //             neither of the above fires (e.g. niche Android WebViews).
            //
            // Auto-resume fires ONLY when:
            //   â€¢ The user was playing before (jammin_was_playing === '1')
            //   â€¢ The user did NOT intentionally pause (userPaused === false)
            //   â€¢ The tab was NOT closed and reopened (jammin_tab_closed_at not set)
            function handlePageResume(reason = 'unknown') { if (typeof window.isAudioInterrupted === 'function' && window.isAudioInterrupted()) { return; } if (typeof window.isAudioInterrupted === 'function' && window.isAudioInterrupted()) { return; }
                if (userPaused || window.userPaused) {
                    return;
                }

                const liveAudio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                const isActivelyPlaying = liveAudio && !liveAudio.paused && liveAudio.readyState >= 2 && !liveAudio.error;

                if (isActivelyPlaying) {
                    if (typeof window.reassertMediaSessionMetadata === 'function') {
                        window.reassertMediaSessionMetadata();
                    }
                    if (typeof window.reassertLiveMediaPosition === 'function') {
                        window.reassertLiveMediaPosition(`page-resume:${reason}`);
                    }
                    return;
                }

                const wasPlaying = (window.safeLocalStorage.getItem(window.getStationStorageKey('was_playing')) === '1') ||
                                   (window.safeLocalStorage.getItem(window.getStationStorageKey('playing_intent')) === '1');

                if (wasPlaying && !userPaused && !window.userPaused) {
                    if (typeof window.recoverLiveStream === 'function') {
                        window.recoverLiveStream('page-resume:' + reason);
                    } else if (window._smartPlayer && typeof window._smartPlayer.resume === 'function') {
                        window._smartPlayer.resume();
                    } else if (typeof window.toggleLiveAudio === 'function') {
                        window.toggleLiveAudio(true);
                    } else if (liveAudio) {
                        liveAudio.play().catch(e => console.warn('[Auto-resume] play() failed:', e.message));
                    }
                }
            }

            // Layer 1: Bluetooth devicechange detection
            if (navigator.mediaDevices && typeof navigator.mediaDevices.addEventListener === 'function') {
                navigator.mediaDevices.addEventListener('devicechange', function () {
                    // Small delay to allow OS to stabilize audio routing
                    // Bluetooth route handled by scripts-core-player
                });
            }

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    // Only claim and re-assert if we weren't already the owner
                    const wasAlreadyOwner = _isActiveMediaTab;
                    if (!wasAlreadyOwner) {
                        _claimMediaOwnership();
                    }

                    // Layer 2: Auto-resume on visibility
                    handlePageResume('visibilitychange');

                    if (window._storedMediaMetadata && navigator.mediaSession) {
                        const currentSessionTitle = navigator.mediaSession.metadata?.title;
                        if (currentSessionTitle !== window._storedMediaMetadata.title) {
                            // Route through debounced commit to avoid flash
                            window._commitMediaSession(window._storedMediaMetadata, true);
                        }
                    }
                } else {
                    const liveAudio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : null;
                    if (liveAudio && !liveAudio.paused) {
                        try {
                            window.safeLocalStorage.setItem(window.getStationStorageKey('was_playing'), '1');
                        } catch(e) {}
                    }
                    // CRITICAL: We no longer set _isActiveMediaTab = false here.
                    // A tab remains the owner even in the background until another tab claims it.
                    // This allows lock-screen metadata updates to work while paused/backgrounded.
                }
            });

            window.addEventListener('focus', function () {
                // Only claim and re-assert if we weren't already the owner
                const wasAlreadyOwner = _isActiveMediaTab;
                if (!wasAlreadyOwner) {
                    _claimMediaOwnership();
                }

                // Layer 2: Auto-resume on focus
                handlePageResume('focus');

                if (window._storedMediaMetadata && navigator.mediaSession) {
                    const currentSessionTitle = navigator.mediaSession.metadata?.title;
                    if (currentSessionTitle !== window._storedMediaMetadata.title) {
                        // Route through debounced commit to avoid flash
                        window._commitMediaSession(window._storedMediaMetadata, true);
                    }
                }
            });

            // Layer 3: Periodic safety-net poll for BT reconnect has been removed.

            // --- Unified Player Initialization ---
            try {
                // 1. Initialize Player UI and Audio
                initializePlayer();

                // 2. Restore Title/Artist IMMEDIATELY from cache (Prevents flickering to generic name)
                restorePersistedMetadata();

                // 3. Start live metadata updates (SSE/Polling)
                startMetadataUpdates();
            } catch (e) {
                console.error('[Initialization] Error during startup sequence:', e);
            }
        } catch (e) {
            console.error('[Initialization] Critical error in player initialization block:', e);
        }
    });
    window.resetArtworkToStationLogo = function() {
        try {
            window._storedArtworkSrc = null;
            const domArts = document.querySelectorAll('#sticky-player-art, .player-cover-art, #player-cover-art');
            domArts.forEach(img => { if (img) { img.style.display = 'block'; img.src = _defaultSrc; } });
        } catch(e) {}
    };
    window.triggerImmediateMetadataSync = function() {
        try { if (typeof fetchMetadata === 'function') fetchMetadata(); } catch(e) {}
    };
</script>