<script>
    window.onerror = function (message, source, lineno, colno, error) {
        return true;
    };

    @php
        $resolvedSlogan = \App\Models\SiteSetting::get('station_slogan', env('STATION_SLOGAN'));
        if (empty($resolvedSlogan)) {
            $legacySloganPath = public_path(env('SLOGAN_FILE_PATH', 'config/slogan.txt'));
            if (file_exists($legacySloganPath)) {
                $resolvedSlogan = trim(file_get_contents($legacySloganPath));
            }
        }
        $resolvedAppName = config('app.name') ?: 'RC 101';
        $resolvedDefaultArt = asset(\App\Models\SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));
    @endphp

    let stationSlogan = @json($resolvedSlogan ?? '');
    const _appName = @json($resolvedAppName);
    const _defaultSrc = @json($resolvedDefaultArt);
    const _defaultAbsoluteSrc = _defaultSrc.startsWith('http') ? _defaultSrc : (window.location.origin + (_defaultSrc.startsWith('/') ? '' : '/') + _defaultSrc);

    window._defaultLogoUrl = _defaultSrc;
    window._currentTrackKey = '';
    window._currentArtworkUrl = _defaultAbsoluteSrc;
    window._currentTitleText = _appName;
    window._currentArtistText = stationSlogan || 'Live Country Radio';
    window._currentSongDuration = 0;
    window._songStartedAt = Date.now();
    window._metadataReady = false;
    window._metadataInFlight = false;
    window._adPollConfirmCount = 0;
    window._itunesCache = {};

    function getAlbumTitle() {
        return stationSlogan ? (_appName + ' - ' + stationSlogan) : _appName;
    }

    function formatTimeMMSS(seconds) {
        if (typeof seconds !== 'number' || !isFinite(seconds) || seconds < 0) return '0:00';
        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    // Build distinct resolution URLs so Android & iOS Lock Screen ALWAYS refreshes the thumbnail on every song change
    function buildArtworkObjects(srcUrl, trackKey) {
        var fullUrl = (srcUrl || _defaultAbsoluteSrc);
        if (!fullUrl.startsWith('http') && !fullUrl.startsWith('data:')) {
            fullUrl = window.location.origin + (fullUrl.startsWith('/') ? '' : '/') + fullUrl;
        }
        var sep = fullUrl.indexOf('?') === -1 ? '?' : '&';
        var tag = sep + 'v=' + encodeURIComponent(trackKey || 'default');

        if (fullUrl.indexOf('mzstatic.com') !== -1) {
            var makeSize = function(sz) {
                return fullUrl.replace(/\b\d{2,4}x\d{2,4}bb\b/, sz + 'x' + sz + 'bb') + tag;
            };
            return [
                { src: makeSize(96),  sizes: '96x96',   type: 'image/jpeg' },
                { src: makeSize(128), sizes: '128x128', type: 'image/jpeg' },
                { src: makeSize(192), sizes: '192x192', type: 'image/jpeg' },
                { src: makeSize(256), sizes: '256x256', type: 'image/jpeg' },
                { src: makeSize(384), sizes: '384x384', type: 'image/jpeg' },
                { src: makeSize(512), sizes: '512x512', type: 'image/jpeg' }
            ];
        }

        var taggedUrl = fullUrl + tag;
        return [
            { src: taggedUrl, sizes: '96x96',   type: 'image/jpeg' },
            { src: taggedUrl, sizes: '128x128', type: 'image/jpeg' },
            { src: taggedUrl, sizes: '192x192', type: 'image/jpeg' },
            { src: taggedUrl, sizes: '256x256', type: 'image/jpeg' },
            { src: taggedUrl, sizes: '384x384', type: 'image/jpeg' },
            { src: taggedUrl, sizes: '512x512', type: 'image/jpeg' }
        ];
    }

    // Create & update on-screen Song Length / Progress UI inside the Sticky Player
    function ensurePlayerDurationUI() {
        var wrap = document.getElementById('live-song-progress-wrap');
        if (wrap) return wrap;
        var songContainer = document.getElementById('mini-song') || document.querySelector('.player-song');
        if (!songContainer) return null;

        wrap = document.createElement('div');
        wrap.id = 'live-song-progress-wrap';
        wrap.style.cssText = 'display:flex; align-items:center; gap:6px; margin-top:3px; font-size:11px; color:rgba(255,255,255,0.85); font-weight:500; min-width:170px; max-width:260px;';
        wrap.innerHTML =
            '<span id="song-elapsed-time" style="min-width:28px;">0:00</span>' +
            '<div style="flex:1; height:4px; background:rgba(255,255,255,0.22); border-radius:2px; overflow:hidden;">' +
                '<div id="song-progress-fill" style="width:0%; height:100%; background:#ffc107; border-radius:2px; transition:width 0.4s linear;"></div>' +
            '</div>' +
            '<span id="song-remaining-time" style="min-width:32px; text-align:right;">-0:00</span>' +
            '<span id="song-total-length" style="color:#ffc107; font-weight:600; margin-left:2px;">(0:00)</span>';
        songContainer.appendChild(wrap);
        return wrap;
    }

    window.updateOnScreenSongProgress = function() {
        if (document.hidden) return; // Zero DOM work while screen is locked/backgrounded
        var dur = Number(window._currentSongDuration) || 0;
        var wrap = ensurePlayerDurationUI();
        if (!wrap) return;

        if (dur <= 0 || window._currentTrackKey === 'AD_BREAK_STATE') {
            wrap.style.display = 'none';
            return;
        }

        wrap.style.display = 'flex';
        var elapsed = Math.max(0, (Date.now() - (window._songStartedAt || Date.now())) / 1000);
        if (elapsed > dur) elapsed = dur;
        var remaining = Math.max(0, dur - elapsed);
        var pct = Math.min(100, Math.max(0, (elapsed / dur) * 100));

        var elSpan = document.getElementById('song-elapsed-time');
        var remSpan = document.getElementById('song-remaining-time');
        var totSpan = document.getElementById('song-total-length');
        var fillBar = document.getElementById('song-progress-fill');

        if (elSpan) elSpan.textContent = formatTimeMMSS(elapsed);
        if (remSpan) remSpan.textContent = '-' + formatTimeMMSS(remaining);
        if (totSpan) totSpan.textContent = '(' + formatTimeMMSS(dur) + ')';
        if (fillBar) fillBar.style.width = pct.toFixed(1) + '%';
    };

    window.setStickyPlayerArtwork = function(srcUrl) {
        var targetSrc = srcUrl || _defaultSrc;
        var domArtElements = document.querySelectorAll('#sticky-player-art, .player-cover-art, #player-cover-art');
        for (var i = 0; i < domArtElements.length; i++) {
            var img = domArtElements[i];
            if (!img) continue;
            img.style.display = 'block';
            if (img.getAttribute('src') !== targetSrc) {
                img.src = targetSrc;
            }
            img.onerror = function() {
                this.onerror = null;
                this.src = _defaultSrc;
            };
        }
    };

    window.resetArtworkToStationLogo = function() {
        window._currentArtworkUrl = _defaultAbsoluteSrc;
        window._metadataReady = false;
        window.setStickyPlayerArtwork(_defaultSrc);
    };

    // Update Lock Screen Position State ONCE per state/song change so OS natively animates progress bar with 0 CPU overhead
    window.syncLockScreenPositionState = function() {
        if (!('mediaSession' in navigator) || typeof navigator.mediaSession.setPositionState !== 'function') return;
        try {
            var dur = Number(window._currentSongDuration) || 0;
            if (dur > 0 && window._currentTrackKey !== 'AD_BREAK_STATE') {
                var elapsed = Math.max(0, (Date.now() - (window._songStartedAt || Date.now())) / 1000);
                var pos = Math.min(elapsed, Math.max(0, dur - 0.5));
                var audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : document.getElementById('live-audio');
                var isPlaying = (audio && !audio.paused && !audio.error && !window.userPaused);
                navigator.mediaSession.setPositionState({
                    duration: dur,
                    playbackRate: isPlaying ? 1.0 : 0.0,
                    position: pos
                });
            }
        } catch (e) {}
    };

    // Update Lock Screen / Notification Bar & Car Bluetooth Metadata
    window.reassertMediaSessionMetadata = function() {
        if (!('mediaSession' in navigator)) return;
        try {
            var titleText = window._currentTitleText || _appName;
            var artistText = window._currentArtistText || (stationSlogan || 'Live Radio');
            var dur = Number(window._currentSongDuration) || 0;
            var durLabel = (dur > 0 && window._currentTrackKey !== 'AD_BREAK_STATE') ? (' [' + formatTimeMMSS(dur) + ']') : '';

            var artUrl = window._currentArtworkUrl || _defaultAbsoluteSrc;

            navigator.mediaSession.metadata = new MediaMetadata({
                title: titleText + durLabel,
                artist: artistText,
                album: getAlbumTitle(),
                artwork: buildArtworkObjects(artUrl, window._currentTrackKey)
            });

            if (typeof window.bindMasterMediaSessionHandlers === 'function') {
                window.bindMasterMediaSessionHandlers();
            }

            var audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : document.getElementById('live-audio');
            var isPlaying = (audio && !audio.paused && !audio.error && !window.userPaused);
            navigator.mediaSession.playbackState = isPlaying ? 'playing' : 'paused';

            window.syncLockScreenPositionState();
        } catch (e) {}
    };

    // Preload image into browser cache (even in background so Lock Screen Notification ALWAYS gets the new bitmap!)
    function preloadArtworkStrict(url, timeoutMs) {
        timeoutMs = timeoutMs || 3500;
        return new Promise(function(resolve) {
            if (!url || url === _defaultSrc || url === _defaultAbsoluteSrc) {
                resolve({ ok: false, url: _defaultAbsoluteSrc });
                return;
            }
            var done = false;
            var finish = function(ok, finalUrl) {
                if (done) return;
                done = true;
                resolve({ ok: ok, url: finalUrl });
            };
            var timer = setTimeout(function() { finish(false, _defaultAbsoluteSrc); }, timeoutMs);
            var img = new Image();
            img.onload = function() {
                clearTimeout(timer);
                finish(true, url);
            };
            img.onerror = function() {
                clearTimeout(timer);
                finish(false, _defaultAbsoluteSrc);
            };
            img.src = url;
        });
    }

    function isAdvertisementOrStationBreak(rawTitle, rawArtist) {
        var combined = ((rawArtist || '') + ' ' + (rawTitle || '')).trim().toLowerCase();
        if (!combined) return true;

        var stationLower = (_appName || 'rc 101').toLowerCase();
        var sloganLower = (stationSlogan || 'your rockin').toLowerCase();

        if (combined === stationLower || combined === 'live stream' || combined === 'live radio' || combined === 'unknown' || combined === 'unknown song') {
            return true;
        }
        if ((rawTitle || '').trim().toLowerCase() === stationLower && (!rawArtist || rawArtist.trim().toLowerCase() === stationLower)) {
            return true;
        }
        if (/commercial|advertisement|\bad\s*break\b|\bsponsor\b|station\s*id|promo|sweeper|jingle|rockin'\s*country|rc101country/i.test(combined)) {
            return true;
        }
        if (sloganLower && combined.includes(sloganLower)) {
            return true;
        }
        return false;
    }

    function cleanSearchTerm(str) {
        if (!str) return '';
        return String(str)
            .replace(/\s*(?:feat\.?|ft\.?|featuring|with)\s+.*$/gi, '')
            .replace(/[\(\[\{].*?[\)\]\}]/g, '')
            .replace(/[^\w\s']/g, ' ')
            .replace(/\s{2,}/g, ' ')
            .trim();
    }

    function getPrimaryArtist(artistStr) {
        if (!artistStr) return '';
        var primary = String(artistStr).split(/\s*(?:&|\band\b|\bfeat\.?|\bft\.?|\bwith\b|,|\/|\bx\b)\s*/i)[0];
        return cleanSearchTerm(primary);
    }

    // Properly split combined "Title - Artist" or "Artist - Title" if artist field is empty or generic station name
    function extractCleanArtistAndTitle(rawArtist, rawTitle) {
        var a = String(rawArtist || '').replace(/\s*[0-9]{1,2}[:.][0-9]{2}(?:[:.][0-9]{2})?\s*$/, '').replace(/[\s\-\–\—\|•:]+$/, '').trim();
        var t = String(rawTitle || '').replace(/\s*[0-9]{1,2}[:.][0-9]{2}(?:[:.][0-9]{2})?\s*$/, '').replace(/[\s\-\–\—\|•:]+$/, '').trim();

        var stLower = (_appName || 'rc 101').toLowerCase();
        var artistIsGeneric = (!a || a.toLowerCase() === stLower || a.toLowerCase() === 'now playing' || a.toLowerCase() === 'live stream' || a.toLowerCase() === 'unknown artist');

        if (artistIsGeneric && t) {
            var seps = [' - ', ' – ', ' — ', ' | ', ' • '];
            for (var i = 0; i < seps.length; i++) {
                var s = seps[i];
                if (t.indexOf(s) !== -1) {
                    var parts = t.split(s);
                    if (parts.length >= 2) {
                        a = parts[0].trim();
                        t = parts.slice(1).join(s).trim();
                        break;
                    }
                }
            }
        }
        return { artist: a, title: t };
    }

    async function fetchItunesJson(query, entity) {
        entity = entity || 'song';
        // 1. Server Proxy first (same-origin, works 100% even when Chrome tab is in background / screen locked!)
        try {
            var proxyUrl = '/proxy/itunes-search?term=' + encodeURIComponent(query) + '&entity=' + encodeURIComponent(entity);
            var res = await fetch(proxyUrl, { method: 'GET', cache: 'default' });
            if (res.ok) {
                var data = await res.json();
                if (data && Array.isArray(data.results) && data.results.length > 0) return data;
            }
        } catch (e) {}

        // 2. Direct simple CORS request (no custom headers so zero preflight block)
        try {
            var directUrl = 'https://itunes.apple.com/search?term=' + encodeURIComponent(query) + '&media=music&entity=' + entity + '&limit=10&country=US';
            var res2 = await fetch(directUrl);
            if (res2.ok) {
                var data2 = await res2.json();
                if (data2 && Array.isArray(data2.results) && data2.results.length > 0) return data2;
            }
        } catch (e) {}

        return null;
    }

    async function resolveTrackInfoFromItunes(artist, title) {
        var cacheKey = (artist + '___' + title).toLowerCase();
        if (window._itunesCache[cacheKey]) {
            return window._itunesCache[cacheKey];
        }

        var cleanTitle = cleanSearchTerm(title);
        var cleanArtist = cleanSearchTerm(artist);
        var primaryArtist = getPrimaryArtist(artist);

        var queries = [];
        if (primaryArtist && cleanTitle) queries.push(primaryArtist + ' ' + cleanTitle);
        if (cleanArtist && cleanArtist !== primaryArtist && cleanTitle) queries.push(cleanArtist + ' ' + cleanTitle);
        if (cleanTitle && cleanTitle.length >= 2) queries.push(cleanTitle);

        for (var i = 0; i < queries.length; i++) {
            var data = await fetchItunesJson(queries[i], 'song');
            if (data && data.results && data.results.length > 0) {
                var normTitle = cleanTitle.toLowerCase();
                var normPrimary = primaryArtist.toLowerCase();

                var best = data.results.find(function(r) {
                    return r.artworkUrl100 && r.trackName && r.artistName &&
                        (r.trackName.toLowerCase().includes(normTitle) || normTitle.includes(r.trackName.toLowerCase())) &&
                        (normPrimary ? r.artistName.toLowerCase().includes(normPrimary.split(' ')[0]) : true);
                });

                if (!best && normPrimary) {
                    best = data.results.find(function(r) {
                        return r.artworkUrl100 && r.artistName && r.artistName.toLowerCase().includes(normPrimary.split(' ')[0]);
                    });
                }

                if (!best) {
                    best = data.results.find(function(r) { return r.artworkUrl100; });
                }

                if (best && best.artworkUrl100) {
                    var foundObj = {
                        artwork: best.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb'),
                        duration: best.trackTimeMillis ? Math.round(best.trackTimeMillis / 1000) : 210,
                        matched: true
                    };
                    window._itunesCache[cacheKey] = foundObj;
                    return foundObj;
                }
            }
        }

        // Layer 4: Artist Album Cover Fallback so every singer always has a real photo
        if (primaryArtist && primaryArtist.length >= 2) {
            var artistData = await fetchItunesJson(primaryArtist, 'album');
            if (artistData && artistData.results && artistData.results.length > 0) {
                var albumBest = artistData.results.find(function(r) { return r.artworkUrl100; });
                if (albumBest && albumBest.artworkUrl100) {
                    var fallbackObj = {
                        artwork: albumBest.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb'),
                        duration: 210,
                        matched: true
                    };
                    window._itunesCache[cacheKey] = fallbackObj;
                    return fallbackObj;
                }
            }
        }

        return { artwork: _defaultAbsoluteSrc, duration: 210, matched: false };
    }

    function isNonDefaultProvidedArtwork(url) {
        if (!url || typeof url !== 'string' || !url.startsWith('http')) return false;
        var lower = url.toLowerCase();
        if (lower.indexOf('alexaicon') !== -1 || lower.indexOf('logo') !== -1 || lower.indexOf('social-banner') !== -1 || lower.indexOf('default') !== -1) {
            return false;
        }
        return true;
    }

    async function applyIncomingMetadata(rawArtist, rawTitle, providedArtwork) {
        var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
        if (!isOnline || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        var artistEl = document.getElementById('current-artist');
        var titleEl  = document.getElementById('current-title');
        var songInfo = document.querySelector('.player-song');
        var playText = document.getElementById('play-to-listen-text');
        if (!artistEl || !titleEl) return;

        var parsed = extractCleanArtistAndTitle(rawArtist, rawTitle);
        var cleanArtist = parsed.artist;
        var cleanTitle  = parsed.title;

        // Advertisement / Station Break Handling
        if (isAdvertisementOrStationBreak(cleanTitle, cleanArtist)) {
            if (window._currentTrackKey && window._currentTrackKey !== 'AD_BREAK_STATE') {
                window._adPollConfirmCount = (window._adPollConfirmCount || 0) + 1;
                if (window._adPollConfirmCount < 2) return;
            }
            window._adPollConfirmCount = 0;
            if (window._currentTrackKey === 'AD_BREAK_STATE') return;

            window._currentTrackKey = 'AD_BREAK_STATE';
            window._currentArtworkUrl = _defaultAbsoluteSrc;
            window._currentTitleText = 'ADVERTISEMENT / STATION BREAK';
            window._currentArtistText = _appName + (stationSlogan ? ' — ' + stationSlogan : '');
            window._currentSongDuration = 0;
            window._songStartedAt = Date.now();
            window._metadataReady = true;

            window.setStickyPlayerArtwork(_defaultSrc);
            titleEl.textContent = window._currentTitleText;
            artistEl.textContent = window._currentArtistText;
            document.title = _appName + ' | Live Radio';
            window.updateOnScreenSongProgress();

            if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
                if (songInfo) songInfo.style.display = 'block';
                if (playText) playText.style.display = 'none';
            }
            window.reassertMediaSessionMetadata();
            if (typeof window.checkAndReleasePlaybackGate === 'function') {
                window.checkAndReleasePlaybackGate();
            }
            return;
        }

        window._adPollConfirmCount = 0;
        var trackKey = (cleanArtist + '___' + cleanTitle).toLowerCase();

        if (window._currentTrackKey === trackKey && window._metadataReady) {
            if (typeof window.checkAndReleasePlaybackGate === 'function') {
                window.checkAndReleasePlaybackGate();
            }
            return;
        }

        // Always fetch real song artwork & duration from iTunes (ignore default station logo in providedArtwork)
        var itunesInfo = await resolveTrackInfoFromItunes(cleanArtist, cleanTitle);
        var candidateArt = isNonDefaultProvidedArtwork(providedArtwork) ? providedArtwork : itunesInfo.artwork;

        var preloaded = await preloadArtworkStrict(candidateArt, 3500);

        if ((typeof navigator.onLine !== 'undefined' && !navigator.onLine) || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        // STRICT RULE: If internet failed to load the song's thumbnail image when a new song arrives,
        // treat as Network Error / Reconnecting if user is actively trying to play!
        if (!preloaded.ok && candidateArt !== _defaultAbsoluteSrc) {
            if (!navigator.onLine) {
                window.resetArtworkToStationLogo();
                window._currentTrackKey = '';
                if (typeof window.recoverLiveStream === 'function' && !window.userPaused) {
                    window.recoverLiveStream('artwork-network-fail');
                }
                return;
            }
        }

        var finalArt = preloaded.ok ? preloaded.url : _defaultAbsoluteSrc;

        // ATOMIC 0.0s COMMIT: Thumbnail + Song Name + Singer Name + Song Length all update together at the exact same instant!
        window._currentTrackKey = trackKey;
        window._currentArtworkUrl = finalArt;
        window._currentTitleText = cleanTitle;
        window._currentArtistText = cleanArtist || _appName;
        window._currentSongDuration = itunesInfo.duration || 210;
        window._songStartedAt = Date.now();
        window._metadataReady = true;

        window.setStickyPlayerArtwork(finalArt);
        titleEl.textContent = window._currentTitleText;
        artistEl.textContent = window._currentArtistText;
        document.title = window._currentTitleText + ' - ' + window._currentArtistText + ' | ' + _appName;

        window.updateOnScreenSongProgress();

        if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';
        }

        // Update Lock Screen Notification (even when Paused, so Lock Screen always shows current song & thumbnail!)
        window.reassertMediaSessionMetadata();

        if (typeof window.checkAndReleasePlaybackGate === 'function') {
            window.checkAndReleasePlaybackGate();
        }
    }

    window.updateSongInfo = function(info) {
        if (!info) return;
        var artist = '';
        var title = '';
        var artwork = null;

        if (typeof info === 'object') {
            artist = info.artist || '';
            title = info.song || info.title || '';
            artwork = info.artwork || info.cover || null;
        } else if (typeof info === 'string') {
            title = info.trim();
        }

        applyIncomingMetadata(artist, title, artwork);
    };

    async function pollLiveMetadata() {
        if (window._metadataInFlight) return;
        var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
        if (!isOnline || window._networkIssueDetected) return;

        window._metadataInFlight = true;
        try {
            var streamUrl = window.DEFAULT_STREAM_URL || 'https://streams.radiomast.io/rc101';
            var res = await fetch('/api/stream-metadata?streamUrl=' + encodeURIComponent(streamUrl) + '&_t=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            });

            if (res.ok) {
                var data = await res.json();
                if (data && (data.title || data.song || data.artist)) {
                    await applyIncomingMetadata(data.artist || '', data.song || data.title || '', data.artwork || null);
                }
            } else if (!navigator.onLine) {
                throw new Error('Offline');
            }
        } catch (e) {
            if (typeof navigator.onLine !== 'undefined' && !navigator.onLine) {
                window.resetArtworkToStationLogo();
            }
        } finally {
            window._metadataInFlight = false;
        }
    }

    window.triggerImmediateMetadataSync = function() {
        pollLiveMetadata();
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (window._metadataInitialized) return;
        window._metadataInitialized = true;

        ensurePlayerDurationUI();
        window.setStickyPlayerArtwork(_defaultSrc);
        window.reassertMediaSessionMetadata();
        pollLiveMetadata();

        // Poll for song changes every 5 seconds (updates Lock Screen & Player Bar seamlessly)
        setInterval(pollLiveMetadata, 5000);

        // Update on-screen elapsed/remaining timer every 1 second ONLY when screen is visible (0 background CPU!)
        setInterval(function() {
            if (!document.hidden) {
                window.updateOnScreenSongProgress();
            }
        }, 1000);
    });
</script>