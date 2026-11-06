<script>
    window.onerror = function (message, source, lineno, colno, error) {
        console.error('Global error:', { message, source, lineno, colno, error });
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
    const _defaultAbsoluteSrc = _defaultSrc.startsWith('http') ? _defaultSrc : (window.location.origin + _defaultSrc);

    window._defaultLogoUrl = _defaultSrc;
    window._currentTrackKey = '';
    window._currentArtworkUrl = _defaultSrc;
    window._currentTitleText = '';
    window._currentArtistText = '';
    window._metadataReady = false;
    window._metadataInFlight = false;
    window._artworkCache = {};

    function getAlbumTitle() {
        return stationSlogan ? (_appName + ' - ' + stationSlogan) : _appName;
    }

    function buildArtworkObjects(srcUrl) {
        var fullUrl = (srcUrl || _defaultAbsoluteSrc);
        if (!fullUrl.startsWith('http') && !fullUrl.startsWith('data:')) {
            fullUrl = window.location.origin + (fullUrl.startsWith('/') ? '' : '/') + fullUrl;
        }
        return [
            { src: fullUrl, sizes: '96x96',   type: 'image/jpeg' },
            { src: fullUrl, sizes: '128x128', type: 'image/jpeg' },
            { src: fullUrl, sizes: '192x192', type: 'image/jpeg' },
            { src: fullUrl, sizes: '256x256', type: 'image/jpeg' },
            { src: fullUrl, sizes: '384x384', type: 'image/jpeg' },
            { src: fullUrl, sizes: '512x512', type: 'image/jpeg' }
        ];
    }

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
        window._currentArtworkUrl = _defaultSrc;
        window._metadataReady = false;
        window.setStickyPlayerArtwork(_defaultSrc);
    };

    function preloadImageWithTimeout(url, timeoutMs) {
        timeoutMs = timeoutMs || 3500;
        return new Promise(function(resolve) {
            if (!url || url === _defaultSrc) {
                resolve({ url: _defaultSrc, loaded: true });
                return;
            }
            var done = false;
            var finish = function(finalUrl, ok) {
                if (done) return;
                done = true;
                resolve({ url: finalUrl, loaded: ok });
            };
            var timer = setTimeout(function() { finish(_defaultSrc, false); }, timeoutMs);
            var img = new Image();
            img.onload = function() {
                clearTimeout(timer);
                finish(url, true);
            };
            img.onerror = function() {
                clearTimeout(timer);
                finish(_defaultSrc, false);
            };
            img.src = url;
        });
    }

    // Keep Android / iOS Lock Screen & Notification Shade MediaSession 100% Persistent (Playing AND Paused)
    window.reassertMediaSessionMetadata = function() {
        if (!('mediaSession' in navigator)) return;
        try {
            var titleText = window._currentTitleText || _appName;
            var artistText = window._currentArtistText || (stationSlogan || 'Live Radio');
            var artUrl = window._currentArtworkUrl || _defaultAbsoluteSrc;

            navigator.mediaSession.metadata = new MediaMetadata({
                title: titleText,
                artist: artistText,
                album: getAlbumTitle(),
                artwork: buildArtworkObjects(artUrl)
            });

            if (typeof window.bindMasterMediaSessionHandlers === 'function') {
                window.bindMasterMediaSessionHandlers();
            }

            var audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : document.getElementById('live-audio');
            var isPlaying = (audio && !audio.paused && !audio.error && audio.readyState >= 2 && !window.userPaused);
            navigator.mediaSession.playbackState = isPlaying ? 'playing' : 'paused';
        } catch (e) {}
    };

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

    async function resolveArtworkFromItunes(artist, title) {
        var cacheKey = (artist + '___' + title).toLowerCase();
        if (window._artworkCache[cacheKey]) {
            return window._artworkCache[cacheKey];
        }

        var cleanTitle = cleanSearchTerm(title);
        var cleanArtist = cleanSearchTerm(artist);
        if (!cleanTitle || cleanTitle.length < 2) return _defaultSrc;

        var queries = [];
        if (cleanArtist && cleanArtist.length >= 2) {
            queries.push(cleanArtist + ' ' + cleanTitle);
        }
        queries.push(cleanTitle);

        for (var i = 0; i < queries.length; i++) {
            var q = queries[i];
            try {
                var res = await fetch('/proxy/itunes-search?term=' + encodeURIComponent(q), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'force-cache'
                });
                if (!res.ok) continue;
                var data = await res.json();
                if (data && Array.isArray(data.results) && data.results.length > 0) {
                    var normArtist = cleanArtist.toLowerCase();
                    var normTitle = cleanTitle.toLowerCase();

                    var best = data.results.find(function(r) {
                        return r.artworkUrl100 &&
                            r.trackName && r.artistName &&
                            r.trackName.toLowerCase().includes(normTitle) &&
                            (normArtist ? r.artistName.toLowerCase().includes(normArtist.split(' ')[0]) : true);
                    });
                    if (!best) {
                        best = data.results.find(function(r) {
                            return r.artworkUrl100 && r.trackName && r.trackName.toLowerCase().includes(normTitle);
                        });
                    }
                    if (!best) {
                        best = data.results.find(function(r) { return r.artworkUrl100; });
                    }
                    if (best && best.artworkUrl100) {
                        var hiRes = best.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb');
                        window._artworkCache[cacheKey] = hiRes;
                        return hiRes;
                    }
                }
            } catch (e) {}
        }
        return _defaultSrc;
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
        if (!artistEl || !titleEl) return;

        var cleanTitle = String(rawTitle || '').replace(/\s*[0-9]{1,2}[:.][0-9]{2}(?:[:.][0-9]{2})?\s*$/, '').replace(/[\s\-\–\—\|•:]+$/, '').trim();
        var cleanArtist = String(rawArtist || '').replace(/[\s\-\–\—\|•:]+$/, '').trim();

        // Advertisement / Station Break Handling
        if (isAdvertisementOrStationBreak(cleanTitle, cleanArtist)) {
            var adKey = 'AD_BREAK_STATE';
            window._currentTrackKey = adKey;
            window._currentArtworkUrl = _defaultSrc;
            window._currentTitleText = 'ADVERTISEMENT / STATION BREAK';
            window._currentArtistText = _appName + (stationSlogan ? ' — ' + stationSlogan : '');
            window._metadataReady = true;

            window.setStickyPlayerArtwork(_defaultSrc);
            titleEl.textContent = window._currentTitleText;
            artistEl.textContent = window._currentArtistText;
            document.title = _appName + ' | Live Radio';

            window.reassertMediaSessionMetadata();
            if (typeof window.checkAndReleasePlaybackGate === 'function') {
                window.checkAndReleasePlaybackGate();
            }
            return;
        }

        var trackKey = (cleanArtist + '___' + cleanTitle).toLowerCase();
        if (window._currentTrackKey === trackKey && window._metadataReady) {
            // Reassert lockscreen state and release gate if waiting
            if (typeof window.checkAndReleasePlaybackGate === 'function') {
                window.checkAndReleasePlaybackGate();
            }
            return;
        }

        var targetArtUrl = (providedArtwork && String(providedArtwork).startsWith('http')) ? providedArtwork : null;
        if (!targetArtUrl) {
            targetArtUrl = await resolveArtworkFromItunes(cleanArtist, cleanTitle);
        }

        var preloadResult = await preloadImageWithTimeout(targetArtUrl, 3500);

        if ((typeof navigator.onLine !== 'undefined' && !navigator.onLine) || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        // Commit Title + Artist + Preloaded Photo atomically at the exact same millisecond!
        window._currentTrackKey = trackKey;
        window._currentArtworkUrl = preloadResult.url;
        window._currentTitleText = cleanTitle;
        window._currentArtistText = cleanArtist || _appName;
        window._metadataReady = true;

        window.setStickyPlayerArtwork(preloadResult.url);
        titleEl.textContent = window._currentTitleText;
        artistEl.textContent = window._currentArtistText;
        document.title = window._currentTitleText + ' - ' + window._currentArtistText + ' | ' + _appName;

        // Always update Lock Screen / Notification bar (even when Paused!)
        window.reassertMediaSessionMetadata();

        // Notify audio engine that matching Song + Photo are ready so playback can unmute/start together
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
            if (!artist && title && title.includes(' - ')) {
                var parts = title.split(' - ');
                artist = parts[0].trim();
                title = parts.slice(1).join(' - ').trim();
            }
        } else if (typeof info === 'string') {
            if (info.includes(' - ')) {
                var parts2 = info.split(' - ');
                artist = parts2[0].trim();
                title = parts2.slice(1).join(' - ').trim();
            } else {
                title = info.trim();
            }
        }

        applyIncomingMetadata(artist, title, artwork);
    };

    async function pollLiveMetadata() {
        if (window._metadataInFlight) return;
        var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
        if (!isOnline || window._networkIssueDetected) return;

        window._metadataInFlight = true;
        try {
            var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
            var timeoutId = controller ? setTimeout(function() { controller.abort(); }, 4500) : null;

            var streamUrl = window.DEFAULT_STREAM_URL || 'https://streams.radiomast.io/rc101';
            var fetchOpts = {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            };
            if (controller) fetchOpts.signal = controller.signal;

            var res = await fetch('/api/stream-metadata?streamUrl=' + encodeURIComponent(streamUrl) + '&_t=' + Date.now(), fetchOpts);
            if (timeoutId) clearTimeout(timeoutId);

            if (res.ok) {
                var data = await res.json();
                if (data && (data.title || data.song || data.artist)) {
                    await applyIncomingMetadata(data.artist || '', data.song || data.title || '', data.artwork || null);
                }
            }
        } catch (e) {
            // Transient timeout or network blip
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

        window.setStickyPlayerArtwork(_defaultSrc);
        pollLiveMetadata();

        // Poll every 5 seconds (lightweight on mobile bandwidth so background audio NEVER stutters!)
        setInterval(pollLiveMetadata, 5000);
    });
</script>