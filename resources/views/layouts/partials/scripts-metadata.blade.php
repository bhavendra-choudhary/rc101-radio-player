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

    window._defaultLogoUrl = _defaultSrc;
    window._currentTrackKey = '';
    window._currentArtworkUrl = _defaultSrc;
    window._currentTitleText = '';
    window._currentArtistText = '';
    window._metadataReady = false;
    window._metadataInFlight = false;
    window._adPollConfirmCount = 0;
    window._itunesCache = {};

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
        timeoutMs = timeoutMs || 3000;
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
            var timer = setTimeout(function() { finish(url); }, timeoutMs);
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

    async function fetchItunesJson(query, entity) {
        entity = entity || 'song';
        try {
            var directUrl = 'https://itunes.apple.com/search?term=' + encodeURIComponent(query) + '&media=music&entity=' + entity + '&limit=10&country=US';
            var res = await fetch(directUrl, { method: 'GET', headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                var data = await res.json();
                if (data && Array.isArray(data.results) && data.results.length > 0) return data;
            }
        } catch (e) {}

        try {
            var proxyUrl = '/proxy/itunes-search?term=' + encodeURIComponent(query);
            var res2 = await fetch(proxyUrl, { method: 'GET', headers: { 'Accept': 'application/json' } });
            if (res2.ok) {
                var data2 = await res2.json();
                if (data2 && Array.isArray(data2.results) && data.results.length > 0) return data2;
            }
        } catch (e) {}

        return null;
    }

    async function resolveTrackArtworkFromItunes(artist, title) {
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
                    var artHiRes = best.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb');
                    window._itunesCache[cacheKey] = artHiRes;
                    return artHiRes;
                }
            }
        }

        if (primaryArtist && primaryArtist.length >= 2) {
            var artistData = await fetchItunesJson(primaryArtist, 'album');
            if (artistData && artistData.results && artistData.results.length > 0) {
                var albumBest = artistData.results.find(function(r) { return r.artworkUrl100; });
                if (albumBest && albumBest.artworkUrl100) {
                    var fallbackArt = albumBest.artworkUrl100.replace(/\b\d{2,4}x\d{2,4}bb\b/, '512x512bb');
                    window._itunesCache[cacheKey] = fallbackArt;
                    return fallbackArt;
                }
            }
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
        var songInfo = document.querySelector('.player-song');
        var playText = document.getElementById('play-to-listen-text');
        if (!artistEl || !titleEl) return;

        var cleanTitle = String(rawTitle || '').replace(/\s*[0-9]{1,2}[:.][0-9]{2}(?:[:.][0-9]{2})?\s*$/, '').replace(/[\s\-\–\—\|•:]+$/, '').trim();
        var cleanArtist = String(rawArtist || '').replace(/[\s\-\–\—\|•:]+$/, '').trim();

        if (isAdvertisementOrStationBreak(cleanTitle, cleanArtist)) {
            if (window._currentTrackKey && window._currentTrackKey !== 'AD_BREAK_STATE') {
                window._adPollConfirmCount = (window._adPollConfirmCount || 0) + 1;
                if (window._adPollConfirmCount < 2) return;
            }
            window._adPollConfirmCount = 0;
            if (window._currentTrackKey === 'AD_BREAK_STATE') return;

            window._currentTrackKey = 'AD_BREAK_STATE';
            window._currentArtworkUrl = _defaultSrc;
            window._currentTitleText = 'ADVERTISEMENT / STATION BREAK';
            window._currentArtistText = _appName + (stationSlogan ? ' — ' + stationSlogan : '');
            window._metadataReady = true;

            window.setStickyPlayerArtwork(_defaultSrc);
            titleEl.textContent = window._currentTitleText;
            artistEl.textContent = window._currentArtistText;
            document.title = _appName + ' | Live Radio';

            if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
                if (songInfo) songInfo.style.display = 'block';
                if (playText) playText.style.display = 'none';
            }
            return;
        }

        window._adPollConfirmCount = 0;
        var trackKey = (cleanArtist + '___' + cleanTitle).toLowerCase();

        if (window._currentTrackKey === trackKey && window._metadataReady) {
            return;
        }

        var targetArtUrl = (providedArtwork && String(providedArtwork).startsWith('http'))
            ? providedArtwork
            : await resolveTrackArtworkFromItunes(cleanArtist, cleanTitle);

        var readyArtUrl = await preloadImageWithTimeout(targetArtUrl, 3000);

        if ((typeof navigator.onLine !== 'undefined' && !navigator.onLine) || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        window._currentTrackKey = trackKey;
        window._currentArtworkUrl = readyArtUrl;
        window._currentTitleText = cleanTitle;
        window._currentArtistText = cleanArtist || _appName;
        window._metadataReady = true;

        window.setStickyPlayerArtwork(readyArtUrl);
        titleEl.textContent = cleanTitle;
        artistEl.textContent = cleanArtist || _appName;
        document.title = cleanTitle + ' - ' + (cleanArtist || _appName) + ' | ' + _appName;

        if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';
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

    async function pollLiveMetadata(forceEvenIfHidden) {
        // ZERO BACKGROUND CPU/NETWORK RULE: Do not run fetch or image decoding while tab is in background!
        // This guarantees 0.0ms micro-stutter when switching or closing other apps!
        if (document.hidden && !forceEvenIfHidden) return;
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
        pollLiveMetadata(true);
    };

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Instant sync as soon as user returns to the tab
            pollLiveMetadata(true);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (window._metadataInitialized) return;
        window._metadataInitialized = true;

        window.setStickyPlayerArtwork(_defaultSrc);
        pollLiveMetadata(true);

        setInterval(function() {
            pollLiveMetadata(false);
        }, 6000);
    });
</script>