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

    function getAlbumTitle() {
        return stationSlogan ? (_appName + ' - ' + stationSlogan) : _appName;
    }

    function buildDefaultArtwork() {
        const base = _defaultAbsoluteSrc;
        return [
            { src: base, sizes: '96x96', type: 'image/jpeg' },
            { src: base, sizes: '128x128', type: 'image/jpeg' },
            { src: base, sizes: '192x192', type: 'image/jpeg' },
            { src: base, sizes: '256x256', type: 'image/jpeg' },
            { src: base, sizes: '384x384', type: 'image/jpeg' },
            { src: base, sizes: '512x512', type: 'image/jpeg' }
        ];
    }

    window._defaultArtwork = buildDefaultArtwork();
    window._currentTrackKey = '';
    window._currentArtworkUrl = _defaultSrc;
    window._isCurrentlyAdBreak = false;
    window._metadataInFlight = false;

    window.setStickyPlayerArtwork = function(srcUrl) {
        const targetSrc = srcUrl || _defaultSrc;
        const domArtElements = document.querySelectorAll('#sticky-player-art, .player-cover-art, #player-cover-art');
        domArtElements.forEach(img => {
            if (!img) return;
            img.style.display = 'block';
            if (img.getAttribute('src') !== targetSrc) {
                img.src = targetSrc;
            }
            img.onerror = function() {
                this.onerror = null;
                this.src = _defaultSrc;
            };
        });
    };

    window.resetArtworkToStationLogo = function() {
        window._currentArtworkUrl = _defaultSrc;
        window.setStickyPlayerArtwork(_defaultSrc);
    };

    function preloadImageWithTimeout(url, timeoutMs) {
        timeoutMs = timeoutMs || 2500;
        return new Promise(function(resolve) {
            if (!url || url === _defaultSrc) {
                resolve(_defaultSrc);
                return;
            }
            var done = false;
            var finish = function(finalUrl) {
                if (done) return;
                done = true;
                resolve(finalUrl);
            };
            var timer = setTimeout(function() { finish(_defaultSrc); }, timeoutMs);
            var img = new Image();
            img.onload = function() {
                clearTimeout(timer);
                finish(url);
            };
            img.onerror = function() {
                clearTimeout(timer);
                finish(_defaultSrc);
            };
            img.src = url;
        });
    }

    window.reassertMediaSessionMetadata = function() {
        if (!('mediaSession' in navigator)) return;
        try {
            var titleEl = document.getElementById('current-title');
            var artistEl = document.getElementById('current-artist');
            var titleText = (titleEl && titleEl.textContent.trim()) ? titleEl.textContent.trim() : _appName;
            var artistText = (artistEl && artistEl.textContent.trim()) ? artistEl.textContent.trim() : (stationSlogan || 'Live Radio');
            var artUrl = window._currentArtworkUrl || _defaultAbsoluteSrc;
            var fullArtUrl = artUrl.startsWith('http') ? artUrl : (window.location.origin + artUrl);

            navigator.mediaSession.metadata = new MediaMetadata({
                title: titleText,
                artist: artistText,
                album: getAlbumTitle(),
                artwork: [
                    { src: fullArtUrl, sizes: '512x512', type: 'image/jpeg' },
                    { src: fullArtUrl, sizes: '1024x1024', type: 'image/jpeg' }
                ]
            });

            var audio = (typeof RadioAudio !== 'undefined') ? RadioAudio.get() : document.getElementById('live-audio');
            var isPlaying = (typeof window.isRealAudioPlaying === 'function')
                ? window.isRealAudioPlaying()
                : (audio && !audio.paused && !audio.error && audio.readyState >= 2);
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
                    cache: 'default'
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
                        return best.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb');
                    }
                }
            } catch (e) {}
        }
        return _defaultSrc;
    }

    async function applyIncomingMetadata(rawArtist, rawTitle, providedArtwork) {
        var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
        var isReconnecting = window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.RECONNECTING) || window._networkIssueDetected;
        if (!isOnline || isReconnecting) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        var artistEl = document.getElementById('current-artist');
        var titleEl  = document.getElementById('current-title');
        var songInfo = document.querySelector('.player-song');
        var playText = document.getElementById('play-to-listen-text');
        if (!artistEl || !titleEl) return;

        var cleanTitle = String(rawTitle || '').replace(/\s*[0-9]{1,2}[:.][0-9]{2}(?:[:.][0-9]{2})?\s*$/, '').replace(/[\s\-\–\—\|•:]+$/, '').trim();
        var cleanArtist = String(rawArtist || '').replace(/[\s\-\–\—\|•:]+$/, '').trim();

        if (isAdvertisementOrStationBreak(cleanTitle, cleanArtist)) {
            var adKey = 'AD_BREAK_STATE';
            if (window._currentTrackKey === adKey) return;
            window._currentTrackKey = adKey;
            window._isCurrentlyAdBreak = true;
            window._currentArtworkUrl = _defaultSrc;

            window.setStickyPlayerArtwork(_defaultSrc);
            titleEl.textContent = 'ADVERTISEMENT / STATION BREAK';
            artistEl.textContent = _appName + (stationSlogan ? ' — ' + stationSlogan : '');
            document.title = _appName + ' | Live Radio';

            if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
                if (songInfo) songInfo.style.display = 'block';
                if (playText) playText.style.display = 'none';
            }
            window.reassertMediaSessionMetadata();
            return;
        }

        window._isCurrentlyAdBreak = false;
        var trackKey = (cleanArtist + '___' + cleanTitle).toLowerCase();

        if (window._currentTrackKey === trackKey && titleEl.textContent === cleanTitle) {
            return;
        }

        var targetArtUrl = (providedArtwork && String(providedArtwork).startsWith('http')) ? providedArtwork : null;
        if (!targetArtUrl) {
            targetArtUrl = await resolveArtworkFromItunes(cleanArtist, cleanTitle);
        }
        var readyArtUrl = await preloadImageWithTimeout(targetArtUrl, 2500);

        if ((typeof navigator.onLine !== 'undefined' && !navigator.onLine) || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        window._currentTrackKey = trackKey;
        window._currentArtworkUrl = readyArtUrl;

        window.setStickyPlayerArtwork(readyArtUrl);
        titleEl.textContent = cleanTitle;
        artistEl.textContent = cleanArtist || _appName;
        document.title = cleanTitle + ' - ' + (cleanArtist || _appName) + ' | ' + _appName;

        if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';
        }

        window.reassertMediaSessionMetadata();
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
            }
        } catch (e) {
        } finally {
            window._metadataInFlight = false;
        }
    }

    window.triggerImmediateMetadataSync = function() {
        window._currentTrackKey = '';
        pollLiveMetadata();
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (window._metadataInitialized) return;
        window._metadataInitialized = true;

        window.setStickyPlayerArtwork(_defaultSrc);
        pollLiveMetadata();
        setInterval(pollLiveMetadata, 2500);
    });
</script>