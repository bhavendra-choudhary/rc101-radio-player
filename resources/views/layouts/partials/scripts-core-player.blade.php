@php
    $currentStationUrl = \App\Models\SiteSetting::get('stream_url', env('STREAM_URL', 'https://streams.radiomast.io/rc101'));
    $currentStationName = config('app.name', 'station');
    $stationId = 'st_' . substr(md5($currentStationUrl . '_' . $currentStationName), 0, 10);
@endphp
<link rel="preconnect" href="https://streams.radiomast.io" crossorigin>
<link rel="dns-prefetch" href="https://streams.radiomast.io">
<script>
    window.CURRENT_STATION_ID = @json($stationId);
    window.DEFAULT_STREAM_URL = @json($currentStationUrl);
    window.getStationStorageKey = function(key) {
        return 'station_' + window.CURRENT_STATION_ID + '_' + key;
    };

    if (!window.safeLocalStorage) {
        var _memStorageMap = {};
        window.safeLocalStorage = {
            getItem: function(k) { try { return window.localStorage.getItem(k); } catch(e) { return _memStorageMap[k] || null; } },
            setItem: function(k, v) { try { window.localStorage.setItem(k, v); } catch(e) { _memStorageMap[k] = String(v); } },
            removeItem: function(k) { try { window.localStorage.removeItem(k); } catch(e) { delete _memStorageMap[k]; } },
            clear: function() { try { window.localStorage.clear(); } catch(e) { _memStorageMap = {}; } }
        };
    }
    if (!window.safeSessionStorage) {
        var _memSessionMap = {};
        window.safeSessionStorage = {
            getItem: function(k) { try { return window.sessionStorage.getItem(k); } catch(e) { return _memSessionMap[k] || null; } },
            setItem: function(k, v) { try { window.sessionStorage.setItem(k, v); } catch(e) { _memSessionMap[k] = String(v); } },
            removeItem: function(k) { try { window.sessionStorage.removeItem(k); } catch(e) { delete _memSessionMap[k]; } },
            clear: function() { try { window.sessionStorage.clear(); } catch(e) { _memSessionMap = {}; } }
        };
    }

    var _bgAudioCtx = null;
    var _wasAudioCtxInterrupted = false;

    function startBackgroundKeepAlive() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            if (!_bgAudioCtx) {
                _bgAudioCtx = new AudioCtx();
                var osc = _bgAudioCtx.createOscillator();
                var gain = _bgAudioCtx.createGain();
                gain.gain.value = 0.00001;
                osc.connect(gain);
                gain.connect(_bgAudioCtx.destination);
                osc.start(0);

                _bgAudioCtx.onstatechange = function() {
                    if (!_bgAudioCtx || !window._hasUserStartedThisSession) return;
                    var st = _bgAudioCtx.state;
                    if (st === 'interrupted') {
                        _wasAudioCtxInterrupted = true;
                        if (window.isIntentStillValid() && !window.userPaused) {
                            window._systemCallInterrupted = true;
                            window.setAudioInterrupted(false);
                            window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Phone Call)");
                        }
                    } else if (st === 'running' && (_wasAudioCtxInterrupted || window._systemCallInterrupted)) {
                        _wasAudioCtxInterrupted = false;
                        if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                            window._systemCallInterrupted = false;
                            setTimeout(function() {
                                if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                                    window.recoverLiveStream('phone-call-ended');
                                }
                            }, 350);
                        }
                    }
                };
            }
            if (_bgAudioCtx.state === 'suspended') {
                _bgAudioCtx.resume().catch(function() {});
            }
        } catch (e) {}
    }

    function stopBackgroundKeepAlive() {
        try {
            if (_bgAudioCtx && _bgAudioCtx.state === 'running') {
                _bgAudioCtx.suspend().catch(function() {});
            }
        } catch (e) {}
    }

    var RadioAudio = (function() {
        var _el = null;
        return {
            get: function() {
                if (_el && document.body && document.body.contains(_el)) return _el;
                _el = document.getElementById('live-audio');
                if (_el) {
                    _el.setAttribute('playsinline', 'true');
                    _el.setAttribute('webkit-playsinline', 'true');
                    _el.removeAttribute('crossorigin');
                }
                return _el;
            },
            reset: function() { _el = null; },
            paused: function() {
                var a = this.get();
                return (!a || typeof a.play !== 'function') ? true : a.paused;
            },
            play: function() {
                var a = this.get();
                if (!a || typeof a.play !== 'function') return Promise.resolve();
                try {
                    var p = a.play();
                    return (p && typeof p.then === 'function') ? p : Promise.resolve();
                } catch (e) {
                    return Promise.reject(e);
                }
            },
            pause: function() {
                var a = this.get();
                if (a && typeof a.pause === 'function') {
                    try {
                        a._internalPauseCall = true;
                        a.pause();
                        setTimeout(function() { a._internalPauseCall = false; }, 150);
                    } catch (e) {}
                }
            }
        };
    })();

    window.PLAYER_STATE = {
        IDLE: 'idle',
        LOADING: 'loading',
        PLAYING: 'playing',
        PAUSED: 'paused',
        RECONNECTING: 'reconnecting',
        ERROR: 'error'
    };
    window._currentPlayerState = window.PLAYER_STATE.IDLE;

    window._hasUserStartedThisSession = false;
    window._hasPlayedSuccessfullyOnce = false;
    window.userPaused = true;
    window.audioInterruptedByDevice = false;
    window._systemCallInterrupted = false;
    window._networkIssueDetected = false;
    window._lastAudioDisconnectTime = 0;
    window._lastRouteEventTime = 0;
    window._streamActivePlayTime = 0;
    window._lastPlayStartTimestamp = 0;

    var _isRecovering = false;
    var _lastRecoveryAttempt = 0;
    var _reconnectAttempts = 0;
    var _reconnectTimer = null;
    var _recoverySafetyTimer = null;
    var _lastToggleTime = 0;
    var _lastWatchdogRun = 0;
    var _wakeLock = null;

    window.isRealAudioPlaying = function() {
        var audio = RadioAudio.get();
        if (!audio) return false;
        if (audio.paused || audio.ended || audio.error) return false;
        if (_isRecovering) return false;
        return (audio.readyState >= 2);
    };

    function AudioPersistenceManager() {
        this.storageKey = window.getStationStorageKey('audio_state');
    }
    AudioPersistenceManager.prototype.saveAudioState = function() {
        var audio = RadioAudio.get();
        if (!audio) return;
        try {
            window.safeLocalStorage.setItem(this.storageKey, JSON.stringify({
                isPlaying: window.isRealAudioPlaying(),
                volume: audio.volume,
                currentTime: audio.currentTime,
                timestamp: Date.now()
            }));
        } catch(e) {}
    };
    var audioPersistenceManager = new AudioPersistenceManager();

    window.showReconnectingDialogue = function(title, message, state) {
        if (!window._hasUserStartedThisSession || window.userPaused) {
            window.hideReconnectingDialogue();
            return;
        }
        var dlg = document.getElementById('stream-reconnecting-dialogue');
        if (!dlg) return;
        var titleEl = document.getElementById('srd-title');
        var msgEl   = document.getElementById('srd-message');
        var iconEl  = document.getElementById('srd-icon');
        var retryBtn = document.getElementById('srd-retry-btn');

        if (titleEl && title) titleEl.textContent = title;
        if (msgEl && message) msgEl.textContent = message;

        dlg.classList.remove('srd-success', 'srd-offline');
        clearTimeout(dlg._hideTimer);

        if (state === 'success') {
            dlg.classList.add('srd-success');
            if (iconEl) iconEl.className = 'fas fa-check-circle srd-icon text-success';
            if (retryBtn) retryBtn.style.display = 'none';
            dlg._hideTimer = setTimeout(function() {
                window.hideReconnectingDialogue();
            }, 2200);
        } else if (state === 'offline') {
            dlg.classList.add('srd-offline');
            if (iconEl) iconEl.className = 'fas fa-wifi-slash srd-icon text-danger';
            if (retryBtn) retryBtn.style.display = 'inline-block';
        } else {
            if (iconEl) iconEl.className = 'fas fa-satellite-dish fa-spin srd-icon text-warning';
            if (retryBtn) retryBtn.style.display = 'inline-block';
        }
        dlg.style.display = 'block';
    };

    window.hideReconnectingDialogue = function() {
        var dlg = document.getElementById('stream-reconnecting-dialogue');
        if (dlg) {
            clearTimeout(dlg._hideTimer);
            dlg.style.display = 'none';
        }
    };

    window.setPlayerState = function(newState, message) {
        window._currentPlayerState = newState;
        var navbarPlayIcon = document.getElementById('navbar-play-icon');
        var miniPlayIcon   = document.getElementById('mini-play-icon');
        var playIcon       = document.getElementById('play-icon');
        var heroIconPage   = document.getElementById('hero-listen-icon-page');
        var songInfo       = document.querySelector('.player-song');
        var playText       = document.getElementById('play-to-listen-text');
        var stickyPlayer   = document.getElementById('sticky-player');
        var stickyArt      = document.getElementById('sticky-player-art');
        var curTitleEl     = document.getElementById('current-title');

        if (stickyPlayer) stickyPlayer.style.display = 'flex';
        if (stickyArt) stickyArt.style.display = 'block';

        var hasReadySong = curTitleEl && curTitleEl.textContent && curTitleEl.textContent.trim() !== '' && curTitleEl.textContent.trim() !== 'Loading...';

        if (newState === window.PLAYER_STATE.PLAYING) {
            if (navbarPlayIcon) navbarPlayIcon.className = "fas fa-pause me-1";
            if (miniPlayIcon)   miniPlayIcon.className   = "fas fa-pause";
            if (playIcon)       playIcon.className       = "fas fa-pause";
            if (heroIconPage)   heroIconPage.className   = "fas fa-pause me-2";

            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';

            if ('mediaSession' in navigator) {
                try { navigator.mediaSession.playbackState = 'playing'; } catch(e) {}
            }

            var dlg = document.getElementById('stream-reconnecting-dialogue');
            if (dlg && dlg.style.display !== 'none' && !dlg.classList.contains('srd-success')) {
                window.showReconnectingDialogue("Connected!", "Live stream resumed.", "success");
            }
            _reconnectAttempts = 0;

            // Sync fresh song + photo as soon as stream is actively playing
            if (typeof window.triggerImmediateMetadataSync === 'function' && !window._currentTrackKey) {
                window.triggerImmediateMetadataSync();
            }
        } else if (newState === window.PLAYER_STATE.LOADING) {
            // RULE 2: When user presses Play, if song is already preloaded, show song title & artist immediately with 0 glitch!
            if (navbarPlayIcon) navbarPlayIcon.className = "fas fa-pause me-1";
            if (miniPlayIcon)   miniPlayIcon.className   = "fas fa-pause";
            if (playIcon)       playIcon.className       = "fas fa-pause";
            if (heroIconPage)   heroIconPage.className   = "fas fa-pause me-2";

            if (hasReadySong) {
                if (songInfo) songInfo.style.display = 'block';
                if (playText) playText.style.display = 'none';
            } else {
                if (songInfo) songInfo.style.display = 'none';
                if (playText) {
                    playText.textContent = "Connecting to stream...";
                    playText.style.display = 'block';
                }
            }
        } else if (newState === window.PLAYER_STATE.RECONNECTING) {
            // RULE 3 & 4: On network loss / reconnecting, hide song name, show "Reconnecting stream..." and show RC 101 Logo!
            if (navbarPlayIcon) navbarPlayIcon.className = "fas fa-spinner fa-spin me-1";
            if (miniPlayIcon)   miniPlayIcon.className   = "fas fa-spinner fa-spin";
            if (playIcon)       playIcon.className       = "fas fa-spinner fa-spin";
            if (heroIconPage)   heroIconPage.className   = "fas fa-spinner fa-spin me-2";

            if (songInfo) songInfo.style.display = 'none';
            if (playText) {
                playText.textContent = message || "Reconnecting stream...";
                playText.style.display = 'block';
            }
            if (typeof window.resetArtworkToStationLogo === 'function') {
                window.resetArtworkToStationLogo();
            }
            if ('mediaSession' in navigator) {
                try { navigator.mediaSession.playbackState = 'paused'; } catch(e) {}
            }
        } else {
            if (navbarPlayIcon) navbarPlayIcon.className = "fas fa-play me-1";
            if (miniPlayIcon)   miniPlayIcon.className   = "fas fa-play";
            if (playIcon)       playIcon.className       = "fas fa-play";
            if (heroIconPage)   heroIconPage.className   = "fas fa-play me-2";

            if (songInfo) songInfo.style.display = 'none';
            if (playText) {
                playText.textContent = message || "Press Play To Listen";
                playText.style.display = 'block';
            }
            if ('mediaSession' in navigator) {
                try { navigator.mediaSession.playbackState = 'paused'; } catch(e) {}
            }
            if (window.userPaused || !window._hasUserStartedThisSession) {
                window.hideReconnectingDialogue();
            }
        }
    };

    function getBaseStreamUrl() {
        var audio = RadioAudio.get();
        var base = (audio && audio.getAttribute('data-stream-url')) ||
                   (audio && audio.dataset && audio.dataset.originalSrc) ||
                   window.DEFAULT_STREAM_URL ||
                   "https://streams.radiomast.io/rc101";
        if (window.location.protocol === 'https:' && base.indexOf('http:') === 0) {
            base = base.replace(/^http:/, 'https:');
        }
        return base.replace(/([?&])_cb=\d+(&|$)/g, '$1').replace(/[?&]$/, '');
    }

    function getFreshStreamUrl() {
        var base = getBaseStreamUrl();
        var sep = base.indexOf('?') !== -1 ? '&' : '?';
        return base + sep + '_cb=' + Date.now();
    }

    function mountFreshStream(audio, freshUrl) {
        if (!audio) return;
        try {
            var childSources = audio.querySelectorAll('source');
            for (var i = 0; i < childSources.length; i++) {
                audio.removeChild(childSources[i]);
            }
        } catch(e) {}
        audio.src = freshUrl;
    }

    function savePlayingIntent(shouldPlay) {
        try {
            window.safeLocalStorage.setItem(window.getStationStorageKey('playing_intent'), shouldPlay ? '1' : '0');
            window.safeLocalStorage.setItem(window.getStationStorageKey('was_playing'), shouldPlay ? '1' : '0');
        } catch (e) {}
    }

    window.isIntentStillValid = function() {
        if (!window._hasUserStartedThisSession || window.userPaused) return false;
        return true;
    };

    window.setAudioInterrupted = function(val) {
        window.audioInterruptedByDevice = !!val;
        if (val) {
            window._lastAudioDisconnectTime = Date.now();
        }
    };

    window.getLastAudioDisconnectTime = function() {
        return window._lastAudioDisconnectTime || 0;
    };

    window.isAudioInterrupted = function() {
        return !!window.audioInterruptedByDevice;
    };

    function requestPlayerWakeLock() {
        if ('wakeLock' in navigator && navigator.wakeLock && typeof navigator.wakeLock.request === 'function') {
            try {
                navigator.wakeLock.request('screen').then(function(lock) {
                    _wakeLock = lock;
                    if (_wakeLock && _wakeLock.addEventListener) {
                        _wakeLock.addEventListener('release', function() { _wakeLock = null; });
                    }
                }).catch(function() {});
            } catch(e) {}
        }
    }
    function releasePlayerWakeLock() {
        if (_wakeLock && _wakeLock.release) {
            try { _wakeLock.release(); } catch(e) {}
            _wakeLock = null;
        }
    }

    window.toggleLiveAudio = function toggleLiveAudio(forceState) {
        var now = Date.now();
        if (now - _lastToggleTime < 200) return;
        _lastToggleTime = now;

        var audio = RadioAudio.get();
        if (!audio) return;

        var shouldPlay = (typeof forceState === 'boolean') ? forceState : (audio.paused || !window.isRealAudioPlaying());

        if (shouldPlay) {
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            _isRecovering = false;
            window._hasUserStartedThisSession = true;
            window.userPaused = false;
            window._systemCallInterrupted = false;
            window._networkIssueDetected = false;
            window.setAudioInterrupted(false);
            savePlayingIntent(true);
            _reconnectAttempts = 0;
            window._lastPlayStartTimestamp = Date.now();

            window.hideReconnectingDialogue();
            startBackgroundKeepAlive();

            // Immediately show preloaded song title & artist (Rule 2)
            window.setPlayerState(window.PLAYER_STATE.LOADING);

            mountFreshStream(audio, getFreshStreamUrl());

            var playPromise = RadioAudio.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.then(function() {
                    _reconnectAttempts = 0;
                    window._hasPlayedSuccessfullyOnce = true;
                    window._streamActivePlayTime = Date.now();
                    audioPersistenceManager.saveAudioState();
                    window.setPlayerState(window.PLAYER_STATE.PLAYING);
                    window.hideReconnectingDialogue();
                    if (typeof window.reassertMediaSessionMetadata === 'function') {
                        window.reassertMediaSessionMetadata();
                    }
                    requestPlayerWakeLock();
                }).catch(function(error) {
                    if (error && (error.name === 'AbortError' || error.code === 20)) return;
                    if (error && (error.name === 'NotAllowedError' || error.name === 'SecurityError')) {
                        window.setPlayerState(window.PLAYER_STATE.PAUSED, "Tap Play To Listen");
                        return;
                    }
                    window._networkIssueDetected = true;
                    window.recoverLiveStream('play-initial-error');
                });
            }
        } else {
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            _isRecovering = false;
            window._hasUserStartedThisSession = false;
            window.userPaused = true;
            window._systemCallInterrupted = false;
            window._networkIssueDetected = false;
            window.setAudioInterrupted(false);
            savePlayingIntent(false);
            stopBackgroundKeepAlive();
            RadioAudio.pause();
            audioPersistenceManager.saveAudioState();
            window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
            window.hideReconnectingDialogue();
            releasePlayerWakeLock();
        }
    };
    toggleLiveAudio = window.toggleLiveAudio;

    window.forceReconnectLiveStream = function() {
        clearTimeout(_reconnectTimer);
        clearTimeout(_recoverySafetyTimer);
        _isRecovering = false;
        _lastRecoveryAttempt = 0;
        _reconnectAttempts = 0;
        window._hasUserStartedThisSession = true;
        window.userPaused = false;
        window._systemCallInterrupted = false;
        window.setAudioInterrupted(false);
        savePlayingIntent(true);
        startBackgroundKeepAlive();
        window.showReconnectingDialogue("Connecting...", "Reconnecting to live stream...", "reconnecting");
        window.recoverLiveStream('user-force-retry');
    };

    function getReconnectDelay(attempt) {
        if (attempt <= 1) return 900;
        if (attempt <= 3) return 1600;
        if (attempt <= 6) return 2500;
        return 3500;
    }

    window.recoverLiveStream = function(reason) {
        var now = Date.now();
        if (_isRecovering && (now - _lastRecoveryAttempt < 1200)) return;

        if (!window.isIntentStillValid() || window.userPaused) {
            window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
            window.hideReconnectingDialogue();
            _isRecovering = false;
            return;
        }

        if (window.isAudioInterrupted()) {
            var isAllowedTrigger = (
                reason.indexOf('bluetooth-reconnected') !== -1 ||
                reason.indexOf('carplay-reconnected') !== -1 ||
                reason.indexOf('mediasession') !== -1 ||
                reason.indexOf('user') !== -1
            );
            if (!isAllowedTrigger) {
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                window.hideReconnectingDialogue();
                _isRecovering = false;
                return;
            }
            window.setAudioInterrupted(false);
        }

        // RULE 3: While reconnecting or offline, reset artwork to RC 101 Logo and show Reconnecting text
        window._networkIssueDetected = true;
        window._currentTrackKey = '';

        var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
        if (!isOnline) {
            window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
            if (window._hasPlayedSuccessfullyOnce) {
                window.showReconnectingDialogue("Network Offline", "Waiting for internet or cellular connection...", "offline");
            }
            _isRecovering = false;
            clearTimeout(_reconnectTimer);
            _reconnectTimer = setTimeout(function() {
                if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                    window.recoverLiveStream('offline-wait-retry');
                }
            }, 1500);
            return;
        }

        _isRecovering = true;
        _lastRecoveryAttempt = now;
        _reconnectAttempts++;

        clearTimeout(_recoverySafetyTimer);
        _recoverySafetyTimer = setTimeout(function() {
            _isRecovering = false;
        }, 3500);

        window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
        if (window._hasPlayedSuccessfullyOnce) {
            var attemptLabel = _reconnectAttempts > 1 ? " (Attempt " + _reconnectAttempts + ")" : "";
            window.showReconnectingDialogue("Reconnecting Stream", "Resuming live broadcast" + attemptLabel + "...", "reconnecting");
        }

        var audio = RadioAudio.get();
        if (!audio) {
            _isRecovering = false;
            return;
        }

        startBackgroundKeepAlive();
        window._lastPlayStartTimestamp = Date.now();

        var onPlaySuccess = function() {
            _isRecovering = false;
            _reconnectAttempts = 0;
            window._hasPlayedSuccessfullyOnce = true;
            window._networkIssueDetected = false;
            window._systemCallInterrupted = false;
            window._streamActivePlayTime = Date.now();
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            audioPersistenceManager.saveAudioState();
            window.setPlayerState(window.PLAYER_STATE.PLAYING);
            if (window._hasPlayedSuccessfullyOnce) {
                window.showReconnectingDialogue("Connected!", "Live stream resumed.", "success");
            }
            if (typeof window.triggerImmediateMetadataSync === 'function') {
                window.triggerImmediateMetadataSync();
            }
            requestPlayerWakeLock();
        };

        var scheduleRetry = function(err) {
            _isRecovering = false;
            if (err && err.name === 'NotAllowedError' && document.hidden) {
                window._systemCallInterrupted = true;
            }
            clearTimeout(_reconnectTimer);
            var delay = getReconnectDelay(_reconnectAttempts);
            _reconnectTimer = setTimeout(function() {
                if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                    window.recoverLiveStream('scheduled-retry-' + _reconnectAttempts);
                }
            }, delay);
        };

        mountFreshStream(audio, getFreshStreamUrl());
        var p = RadioAudio.play();
        if (p && typeof p.then === 'function') {
            p.then(onPlaySuccess).catch(scheduleRetry);
        } else {
            onPlaySuccess();
        }
    };

    window.verifyStreamHealthOnForeground = function(source) {
        if (!window.isIntentStillValid() || window.userPaused) return;
        if (window.isAudioInterrupted()) {
            window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
            return;
        }

        var audio = RadioAudio.get();
        if (!audio) return;

        if (window._systemCallInterrupted || audio.paused || audio.error || audio.ended) {
            window._systemCallInterrupted = false;
            window.recoverLiveStream('foreground-resume-' + source);
        }
    };

    var _knownAudioDevices = null;
    var _checkingDevices = false;
    var _btResumeTimer = null;

    function getAudioDeviceSignature(devices) {
        var outputCount = 0;
        var totalAudioCount = 0;
        var ids = [];
        for (var i = 0; i < devices.length; i++) {
            var d = devices[i];
            if (d.kind === 'audiooutput' || d.kind === 'audioinput') {
                totalAudioCount++;
                if (d.kind === 'audiooutput') outputCount++;
                ids.push((d.kind || '') + ':' + (d.deviceId || '') + ':' + (d.groupId || '') + ':' + (d.label || ''));
            }
        }
        ids.sort();
        return { outputCount: outputCount, count: totalAudioCount, hash: ids.join('|') };
    }

    function handleBluetoothRouteChange(eventSource) {
        if (_checkingDevices) return;
        _checkingDevices = true;
        if (eventSource === 'devicechange') {
            window._lastRouteEventTime = Date.now();
        }

        var canEnumerate = (navigator.mediaDevices && typeof navigator.mediaDevices.enumerateDevices === 'function');
        if (!canEnumerate) {
            _checkingDevices = false;
            return;
        }

        navigator.mediaDevices.enumerateDevices().then(function(devices) {
            _checkingDevices = false;
            var currentSig = getAudioDeviceSignature(devices);
            var prevSig = _knownAudioDevices;
            _knownAudioDevices = currentSig;

            if (prevSig === null || !window._hasUserStartedThisSession) return;

            var now = Date.now();
            var timeSinceDisconnect = now - window.getLastAudioDisconnectTime();
            var wasPlayingRecently = !RadioAudio.paused() || (now - window._streamActivePlayTime < 3500);

            var deviceRemoved = (currentSig.count < prevSig.count) || (currentSig.outputCount < prevSig.outputCount);
            var routeShiftedWhilePlaying = (eventSource === 'devicechange' && currentSig.hash !== prevSig.hash && wasPlayingRecently && !window.isAudioInterrupted());

            if (!window.isAudioInterrupted() && (deviceRemoved || routeShiftedWhilePlaying)) {
                clearTimeout(_btResumeTimer);
                clearTimeout(_reconnectTimer);
                window.userPaused = false;
                window._systemCallInterrupted = false;
                window.setAudioInterrupted(true);
                RadioAudio.pause();
                startBackgroundKeepAlive();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                window.hideReconnectingDialogue();
                return;
            }

            var deviceAdded = (currentSig.count > prevSig.count) || (currentSig.outputCount > prevSig.outputCount);
            var routeReturned = (currentSig.hash !== prevSig.hash && timeSinceDisconnect > 1500) ||
                                (window.isAudioInterrupted() && eventSource === 'devicechange' && timeSinceDisconnect > 1500);

            if ((deviceAdded || routeReturned) && window.isAudioInterrupted() && window.isIntentStillValid() && !window.userPaused) {
                clearTimeout(_btResumeTimer);
                _btResumeTimer = setTimeout(function() {
                    if (window.isAudioInterrupted() && window.isIntentStillValid() && !window.userPaused) {
                        window.setAudioInterrupted(false);
                        window.userPaused = false;
                        savePlayingIntent(true);
                        window.recoverLiveStream('bluetooth-reconnected-' + eventSource);
                    }
                }, 650);
            }
        }).catch(function() {
            _checkingDevices = false;
        });
    }

    if (typeof navigator !== 'undefined' && navigator.mediaDevices) {
        handleBluetoothRouteChange('init');
        if (typeof navigator.mediaDevices.addEventListener === 'function') {
            navigator.mediaDevices.addEventListener('devicechange', function() {
                handleBluetoothRouteChange('devicechange');
            });
        }
    }

    window.bindMasterMediaSessionHandlers = function() {
        if (!('mediaSession' in navigator)) return;
        try {
            navigator.mediaSession.setActionHandler('play', function() {
                window._hasUserStartedThisSession = true;
                window.userPaused = false;
                window._systemCallInterrupted = false;
                window.setAudioInterrupted(false);
                savePlayingIntent(true);
                startBackgroundKeepAlive();
                window.recoverLiveStream('mediasession-play');
            });
        } catch (e) {}

        try {
            navigator.mediaSession.setActionHandler('pause', function() {
                var sinceRouteChange = Date.now() - window._lastRouteEventTime;
                if (sinceRouteChange < 3500 || window.isAudioInterrupted()) {
                    window.userPaused = false;
                    window.setAudioInterrupted(true);
                    RadioAudio.pause();
                    startBackgroundKeepAlive();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                } else {
                    window.userPaused = true;
                    window.setAudioInterrupted(false);
                    savePlayingIntent(false);
                    stopBackgroundKeepAlive();
                    RadioAudio.pause();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                    window.hideReconnectingDialogue();
                }
            });
        } catch (e) {}

        try {
            navigator.mediaSession.setActionHandler('stop', function() {
                window.userPaused = true;
                window.setAudioInterrupted(false);
                savePlayingIntent(false);
                stopBackgroundKeepAlive();
                RadioAudio.pause();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                window.hideReconnectingDialogue();
            });
        } catch (e) {}
    };

    function initAudio() {
        var liveAudio = RadioAudio.get();
        if (!liveAudio) return null;
        window.bindMasterMediaSessionHandlers();
        return liveAudio;
    }

    function setupAudioListeners() {
        var liveAudio = RadioAudio.get();
        if (!liveAudio || liveAudio._hasBoundListeners) return;
        liveAudio._hasBoundListeners = true;

        liveAudio.addEventListener('playing', function() {
            _isRecovering = false;
            window._hasPlayedSuccessfullyOnce = true;
            window._networkIssueDetected = false;
            window._systemCallInterrupted = false;
            window._streamActivePlayTime = Date.now();
            clearTimeout(_reconnectTimer);
            startBackgroundKeepAlive();
            window.bindMasterMediaSessionHandlers();
            audioPersistenceManager.saveAudioState();
            window.setPlayerState(window.PLAYER_STATE.PLAYING);
            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata();
            }
        });

        liveAudio.addEventListener('timeupdate', function() {
            if (!liveAudio.paused) {
                window._streamActivePlayTime = Date.now();
            }
        });

        liveAudio.addEventListener('pause', function() {
            audioPersistenceManager.saveAudioState();
            if (liveAudio._internalPauseCall) return;
            if (!window._hasUserStartedThisSession || window.userPaused) {
                window.setAudioInterrupted(false);
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                window.hideReconnectingDialogue();
                return;
            }

            if (Date.now() - window._lastPlayStartTimestamp < 1500 && _isRecovering) {
                return;
            }

            var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
            if (!isOnline || window._networkIssueDetected || liveAudio.error || liveAudio.ended) {
                window._networkIssueDetected = true;
                window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                window.recoverLiveStream('network-pause-recovery');
                return;
            }

            if (_wasAudioCtxInterrupted || (_bgAudioCtx && _bgAudioCtx.state === 'interrupted')) {
                window._systemCallInterrupted = true;
                window.setAudioInterrupted(false);
                startBackgroundKeepAlive();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Phone Call)");
                return;
            }

            setTimeout(function() {
                if (!window._hasUserStartedThisSession || window.userPaused || !liveAudio.paused) return;

                var ctxState = _bgAudioCtx ? _bgAudioCtx.state : '';
                if (_wasAudioCtxInterrupted || ctxState === 'interrupted') {
                    window._systemCallInterrupted = true;
                    window.setAudioInterrupted(false);
                    startBackgroundKeepAlive();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Phone Call)");
                    return;
                }

                window.setAudioInterrupted(true);
                startBackgroundKeepAlive();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                window.hideReconnectingDialogue();
            }, 450);
        });

        liveAudio.addEventListener('stalled', function() {
            if (window._hasPlayedSuccessfullyOnce && window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                window._networkIssueDetected = true;
                window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                window.recoverLiveStream('stalled');
            }
        });

        liveAudio.addEventListener('error', function() {
            if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                window._networkIssueDetected = true;
                window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                window.recoverLiveStream('error');
            }
        });

        liveAudio.addEventListener('ended', function() {
            if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
                window._networkIssueDetected = true;
                window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                window.recoverLiveStream('ended');
            }
        });

        liveAudio.addEventListener('waiting', function() {
            clearTimeout(liveAudio._waitingTimeout);
            liveAudio._waitingTimeout = setTimeout(function() {
                if (window._hasPlayedSuccessfullyOnce && window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted() && !window._systemCallInterrupted) {
                    if (!window.isRealAudioPlaying() || liveAudio.readyState < 3) {
                        window._networkIssueDetected = true;
                        window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                        window.recoverLiveStream('waiting-timeout');
                    }
                }
            }, 4000);
        });
    }

    window.addEventListener('online', function() {
        if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
            window._networkIssueDetected = false;
            _reconnectAttempts = 0;
            window.showReconnectingDialogue("Internet Restored", "Reconnecting to live stream...", "reconnecting");
            window.recoverLiveStream('network-online-restored');
        }
    });

    window.addEventListener('offline', function() {
        // RULE 3: Even if paused or playing, if network drops, reset photo to RC 101 Logo so stale song photo doesn't stay stuck
        window._networkIssueDetected = true;
        window._currentTrackKey = '';
        if (typeof window.resetArtworkToStationLogo === 'function') {
            window.resetArtworkToStationLogo();
        }
        if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
            window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
            window.showReconnectingDialogue("Network Offline", "Waiting for internet connection...", "offline");
        }
    });

    function runStreamWatchdog(source) {
        var now = Date.now();
        if (now - _lastWatchdogRun < 2500) return;
        _lastWatchdogRun = now;

        if (!window.isIntentStillValid() || window.userPaused) return;

        if (window.isAudioInterrupted()) {
            handleBluetoothRouteChange('watchdog-poll');
            return;
        }

        var audio = RadioAudio.get();
        if (!audio) return;

        if (window._systemCallInterrupted && audio.paused) {
            if (_bgAudioCtx && _bgAudioCtx.state === 'suspended') {
                _bgAudioCtx.resume().catch(function() {});
            }
            if (!_bgAudioCtx || _bgAudioCtx.state === 'running') {
                window._systemCallInterrupted = false;
                window.recoverLiveStream('watchdog-call-ended');
            }
            return;
        }

        if (window._networkIssueDetected || audio.error || audio.ended) {
            window.recoverLiveStream('watchdog-net-' + source);
            return;
        }

        if (!audio.paused && typeof audio.currentTime === 'number') {
            if (typeof audio._lastWdTime !== 'undefined' && audio.currentTime === audio._lastWdTime) {
                audio._wdStallTicks = (audio._wdStallTicks || 0) + 1;
                if (audio._wdStallTicks >= 3) {
                    audio._wdStallTicks = 0;
                    window._networkIssueDetected = true;
                    window.recoverLiveStream('watchdog-stalled-socket');
                }
            } else {
                audio._lastWdTime = audio.currentTime;
                audio._wdStallTicks = 0;
            }
        }
    }

    setInterval(function() {
        runStreamWatchdog('interval');
    }, 2500);

    try {
        if (typeof window.Worker !== 'undefined' && typeof window.Blob !== 'undefined') {
            var workerBlob = new Blob(['setInterval(function(){postMessage("tick");},2500);'], { type: 'application/javascript' });
            var bgWorker = new Worker(URL.createObjectURL(workerBlob));
            bgWorker.onmessage = function() { runStreamWatchdog('worker'); };
        }
    } catch (e) {}

    document.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest ? e.target.closest('#mini-play-btn, [data-action="toggle-play"], #navbar-play-btn, #hero-listen-btn') : null;
        if (btn) {
            if (btn.id === 'mini-play-btn' && btn.getAttribute('onclick')) return;
            e.preventDefault();
            e.stopPropagation();
            window.toggleLiveAudio();
        }
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            window.verifyStreamHealthOnForeground('visibilitychange');
        }
    });

    window.addEventListener('focus', function() {
        window.verifyStreamHealthOnForeground('focus');
    });

    window.addEventListener('pageshow', function() {
        window.verifyStreamHealthOnForeground('pageshow');
    });

    document.addEventListener('DOMContentLoaded', function() {
        savePlayingIntent(false);
        window.setAudioInterrupted(false);
        window.hideReconnectingDialogue();
        initAudio();
        setupAudioListeners();
        window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
        var stickyPlayer = document.getElementById('sticky-player');
        if (stickyPlayer) stickyPlayer.style.display = 'flex';
    });
</script>