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
    window._metadataReady = false;
    window._metadataInFlight = false;
    window._adPollConfirmCount = 0;
    window._itunesCache = {};

    function getAlbumTitle() {
        return stationSlogan ? (_appName + ' - ' + stationSlogan) : _appName;
    }

    // Feed multi-size HTTPS image URLs into Browser's Native Notification so thumbnail ALWAYS updates on song change
    function buildNativeNotificationArtwork(srcUrl, trackKey) {
        var fullUrl = (srcUrl || _defaultAbsoluteSrc);
        if (!fullUrl.startsWith('http') && !fullUrl.startsWith('data:')) {
            fullUrl = window.location.origin + (fullUrl.startsWith('/') ? '' : '/') + fullUrl;
        }
        var sep = fullUrl.indexOf('?') === -1 ? '?' : '&';
        var cacheTag = sep + 'track=' + encodeURIComponent(trackKey || 'live');

        if (fullUrl.indexOf('mzstatic.com') !== -1) {
            var makeSize = function(sz) {
                return fullUrl.replace(/\b\d{2,4}x\d{2,4}bb\b/, sz + 'x' + sz + 'bb') + cacheTag;
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

        var tagged = fullUrl + cacheTag;
        return [
            { src: tagged, sizes: '96x96',   type: 'image/jpeg' },
            { src: tagged, sizes: '128x128', type: 'image/jpeg' },
            { src: tagged, sizes: '192x192', type: 'image/jpeg' },
            { src: tagged, sizes: '256x256', type: 'image/jpeg' },
            { src: tagged, sizes: '384x384', type: 'image/jpeg' },
            { src: tagged, sizes: '512x512', type: 'image/jpeg' }
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
        window._currentArtworkUrl = _defaultAbsoluteSrc;
        window._metadataReady = false;
        window.setStickyPlayerArtwork(_defaultSrc);
        window.syncNativeBrowserNotification();
    };

    // Modify Browser's Own Native Notification to show Song Name, Singer Name, and Song Picture
    window.syncNativeBrowserNotification = function() {
        if (!('mediaSession' in navigator) || typeof MediaMetadata === 'undefined') return;
        try {
            var titleText = window._currentTitleText || _appName;
            var artistText = window._currentArtistText || (stationSlogan || 'Live Radio');
            var artUrl = window._currentArtworkUrl || _defaultAbsoluteSrc;

            navigator.mediaSession.metadata = new MediaMetadata({
                title: titleText,
                artist: artistText,
                album: getAlbumTitle(),
                artwork: buildNativeNotificationArtwork(artUrl, window._currentTrackKey)
            });

            if (typeof window.bindNativeNotificationControls === 'function') {
                window.bindNativeNotificationControls();
            }
        } catch (e) {}
    };

    function preloadImageWithTimeout(url, timeoutMs) {
        timeoutMs = timeoutMs || 3000;
        return new Promise(function(resolve) {
            if (!url || url === _defaultSrc || url === _defaultAbsoluteSrc) {
                resolve(_defaultAbsoluteSrc);
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
                finish(_defaultAbsoluteSrc);
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
        try {
            var proxyUrl = '/proxy/itunes-search?term=' + encodeURIComponent(query) + '&entity=' + encodeURIComponent(entity);
            var res = await fetch(proxyUrl, { method: 'GET', cache: 'default' });
            if (res.ok) {
                var data = await res.json();
                if (data && Array.isArray(data.results) && data.results.length > 0) return data;
            }
        } catch (e) {}

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

        return _defaultAbsoluteSrc;
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

        // Remove any old song-length progress bar if present
        var oldProgressWrap = document.getElementById('live-song-progress-wrap');
        if (oldProgressWrap && oldProgressWrap.parentNode) {
            oldProgressWrap.parentNode.removeChild(oldProgressWrap);
        }

        var parsed = extractCleanArtistAndTitle(rawArtist, rawTitle);
        var cleanArtist = parsed.artist;
        var cleanTitle  = parsed.title;

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
            window._metadataReady = true;

            window.setStickyPlayerArtwork(_defaultSrc);
            titleEl.textContent = window._currentTitleText;
            artistEl.textContent = window._currentArtistText;
            document.title = _appName + ' | Live Radio';

            if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
                if (songInfo) songInfo.style.display = 'block';
                if (playText) playText.style.display = 'none';
            }
            window.syncNativeBrowserNotification();
            return;
        }

        window._adPollConfirmCount = 0;
        var trackKey = (cleanArtist + '___' + cleanTitle).toLowerCase();

        if (window._currentTrackKey === trackKey && window._metadataReady) {
            return;
        }

        var candidateArt = isNonDefaultProvidedArtwork(providedArtwork)
            ? providedArtwork
            : await resolveTrackArtworkFromItunes(cleanArtist, cleanTitle);

        var readyArtUrl = await preloadImageWithTimeout(candidateArt, 3000);

        if ((typeof navigator.onLine !== 'undefined' && !navigator.onLine) || window._networkIssueDetected) {
            window.resetArtworkToStationLogo();
            window._currentTrackKey = '';
            return;
        }

        // Update Song Name + Singer Name + Picture together at the exact same instant
        window._currentTrackKey = trackKey;
        window._currentArtworkUrl = readyArtUrl;
        window._currentTitleText = cleanTitle;
        window._currentArtistText = cleanArtist || _appName;
        window._metadataReady = true;

        window.setStickyPlayerArtwork(readyArtUrl);
        titleEl.textContent = window._currentTitleText;
        artistEl.textContent = window._currentArtistText;
        document.title = window._currentTitleText + ' - ' + window._currentArtistText + ' | ' + _appName;

        if (window._currentPlayerState === (window.PLAYER_STATE && window.PLAYER_STATE.PLAYING)) {
            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';
        }

        // Push Song Name + Singer Name + Picture into Browser's Native Notification
        window.syncNativeBrowserNotification();
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
            }
        } catch (e) {
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
        window.syncNativeBrowserNotification();
        pollLiveMetadata();

        setInterval(pollLiveMetadata, 6000);
    });
</script>