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

    var RadioAudio = (function() {
        var _el = null;
        return {
            get: function() {
                if (_el && document.body && document.body.contains(_el)) return _el;
                _el = document.getElementById('live-audio');
                if (_el) {
                    // Do NOT use display:none (which blocks Chrome Android Notification)!
                    // Keep element as a standard non-controls audio node so Chrome always shows the Lock Screen Notification!
                    _el.preload = 'auto';
                    _el.muted = false;
                    _el.volume = 1.0;
                    _el.setAttribute('preload', 'auto');
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
                    a.muted = false;
                    a.volume = 1.0;
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
                        setTimeout(function() { a._internalPauseCall = false; }, 250);
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

    // 1-Hour Bluetooth Auto-Reconnect Window (3,600,000 ms)
    var BT_AUTO_RESUME_WINDOW_MS = 60 * 60 * 1000;

    window._hasUserStartedThisSession = false;
    window._hasPlayedSuccessfullyOnce = false;
    window.userPaused = true;
    window.audioInterruptedByDevice = false;
    window._btDisconnectTimestamp = 0;
    window._btDisconnectedDeviceHash = '';
    window._networkIssueDetected = false;
    window._lastAudioDisconnectTime = 0;
    window._lastRouteEventTime = 0;
    window._lastPlayStartTimestamp = 0;
    window._lastPauseTimestamp = 0;

    var _isRecovering = false;
    var _lastRecoveryAttempt = 0;
    var _reconnectAttempts = 0;
    var _reconnectTimer = null;
    var _recoverySafetyTimer = null;
    var _lastToggleTime = 0;
    var _lastWatchdogRun = 0;

    window.isRealAudioPlaying = function() {
        var audio = RadioAudio.get();
        if (!audio) return false;
        if (audio.paused || audio.ended || audio.error) return false;
        if (_isRecovering) return false;
        return (audio.readyState >= 2);
    };

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

        var hasReadySong = window._metadataReady || (curTitleEl && curTitleEl.textContent && curTitleEl.textContent.trim() !== '' && curTitleEl.textContent.trim() !== 'Loading...');

        if (newState === window.PLAYER_STATE.PLAYING) {
            if (navbarPlayIcon) navbarPlayIcon.className = "fas fa-pause me-1";
            if (miniPlayIcon)   miniPlayIcon.className   = "fas fa-pause";
            if (playIcon)       playIcon.className       = "fas fa-pause";
            if (heroIconPage)   heroIconPage.className   = "fas fa-pause me-2";

            if (songInfo) songInfo.style.display = 'block';
            if (playText) playText.style.display = 'none';

            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata(true);
            }

            var dlg = document.getElementById('stream-reconnecting-dialogue');
            if (dlg && dlg.style.display !== 'none' && !dlg.classList.contains('srd-success')) {
                window.showReconnectingDialogue("Connected!", "Live stream resumed.", "success");
            }
            _reconnectAttempts = 0;
        } else if (newState === window.PLAYER_STATE.LOADING) {
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
                    playText.textContent = message || "Connecting to stream...";
                    playText.style.display = 'block';
                }
            }
        } else if (newState === window.PLAYER_STATE.RECONNECTING) {
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
            // When paused, show RC 101 Logo + Name in the Lock Screen Notification!
            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata(true);
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
        audio.preload = 'auto';
        audio.muted = false;
        audio.volume = 1.0;
        audio.src = freshUrl;
    }

    window.isIntentStillValid = function() {
        if (!window._hasUserStartedThisSession || window.userPaused) return false;
        return true;
    };

    window.setAudioInterrupted = function(val, disconnectedHash) {
        window.audioInterruptedByDevice = !!val;
        if (val) {
            var now = Date.now();
            window._lastAudioDisconnectTime = now;
            window._btDisconnectTimestamp = now;
            if (disconnectedHash) {
                window._btDisconnectedDeviceHash = disconnectedHash;
            }
        } else {
            window._btDisconnectTimestamp = 0;
            window._btDisconnectedDeviceHash = '';
        }
    };

    window.getLastAudioDisconnectTime = function() {
        return window._lastAudioDisconnectTime || 0;
    };

    // Automatically expires the Bluetooth auto-resume hold after 1 Hour (3,600,000 ms)
    window.isAudioInterrupted = function() {
        if (!window.audioInterruptedByDevice) return false;
        if (window._btDisconnectTimestamp > 0 && (Date.now() - window._btDisconnectTimestamp > BT_AUTO_RESUME_WINDOW_MS)) {
            window.audioInterruptedByDevice = false;
            window._btDisconnectTimestamp = 0;
            window._btDisconnectedDeviceHash = '';
            window.userPaused = true;
            return false;
        }
        return true;
    };

    // Lock Screen Notification & Bluetooth Hardware AVRCP Controls
    window.bindMasterMediaSessionHandlers = function() {
        if (!('mediaSession' in navigator)) return;

        var disabledActions = ['previoustrack', 'nexttrack', 'seekbackward', 'seekforward', 'seekto'];
        for (var i = 0; i < disabledActions.length; i++) {
            try { navigator.mediaSession.setActionHandler(disabledActions[i], null); } catch (e) {}
        }

        try {
            navigator.mediaSession.setActionHandler('play', function() {
                clearTimeout(_reconnectTimer);
                clearTimeout(_recoverySafetyTimer);
                _isRecovering = false;
                window.setAudioInterrupted(false);
                var pausedDuration = Date.now() - (window._lastPauseTimestamp || 0);
                startOrResumeStream(pausedDuration > 25000);
            });
        } catch (e) {}

        try {
            navigator.mediaSession.setActionHandler('pause', function() {
                clearTimeout(_reconnectTimer);
                clearTimeout(_recoverySafetyTimer);
                _isRecovering = false;
                window._lastPauseTimestamp = Date.now();

                var sinceRouteChange = Date.now() - window._lastRouteEventTime;
                if (sinceRouteChange < 2500 || window.isAudioInterrupted()) {
                    // Bluetooth disconnected while playing -> Pause and arm 1-Hour Auto-Reconnect Timer!
                    window.userPaused = false;
                    window.setAudioInterrupted(true);
                    RadioAudio.pause();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                } else {
                    // User pressed Pause on Lock Screen Notification -> Stay paused & show RC 101 Logo in Notification!
                    window.userPaused = true;
                    window._networkIssueDetected = false;
                    window.setAudioInterrupted(false);
                    RadioAudio.pause();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                    window.hideReconnectingDialogue();
                }
            });
        } catch (e) {}

        try {
            navigator.mediaSession.setActionHandler('stop', function() {
                clearTimeout(_reconnectTimer);
                clearTimeout(_recoverySafetyTimer);
                _isRecovering = false;
                window._lastPauseTimestamp = Date.now();
                window.userPaused = true;
                window._networkIssueDetected = false;
                window.setAudioInterrupted(false);
                RadioAudio.pause();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                window.hideReconnectingDialogue();
            });
        } catch (e) {}
    };

    function startOrResumeStream(forceFreshMount) {
        var audio = RadioAudio.get();
        if (!audio) return;

        window._hasUserStartedThisSession = true;
        window.userPaused = false;
        window._networkIssueDetected = false;
        window.setAudioInterrupted(false);
        window._lastPlayStartTimestamp = Date.now();

        audio.muted = false;
        audio.volume = 1.0;

        window.setPlayerState(window.PLAYER_STATE.LOADING);

        if (!window._metadataReady && typeof window.triggerImmediateMetadataSync === 'function') {
            window.triggerImmediateMetadataSync();
        }

        var hasValidExistingSrc = audio.src && audio.src.indexOf('http') === 0 && !audio.error;
        if (forceFreshMount || !hasValidExistingSrc) {
            mountFreshStream(audio, getFreshStreamUrl());
        }

        // Assert MediaSession inside user gesture so Chrome/iOS registers the Lock Screen Notification immediately!
        if (typeof window.reassertMediaSessionMetadata === 'function') {
            window.reassertMediaSessionMetadata(true);
        }

        var playPromise = RadioAudio.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(function() {
                _reconnectAttempts = 0;
                window._hasPlayedSuccessfullyOnce = true;
                window.setPlayerState(window.PLAYER_STATE.PLAYING);
                window.hideReconnectingDialogue();
                if (typeof window.reassertMediaSessionMetadata === 'function') {
                    window.reassertMediaSessionMetadata(true);
                }
            }).catch(function(error) {
                if (error && (error.name === 'AbortError' || error.code === 20)) return;
                if (error && (error.name === 'NotAllowedError' || error.name === 'SecurityError')) {
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Tap Play To Listen");
                    return;
                }
                if (!forceFreshMount) {
                    mountFreshStream(audio, getFreshStreamUrl());
                    RadioAudio.play().then(function() {
                        window._hasPlayedSuccessfullyOnce = true;
                        window.setPlayerState(window.PLAYER_STATE.PLAYING);
                        if (typeof window.reassertMediaSessionMetadata === 'function') {
                            window.reassertMediaSessionMetadata(true);
                        }
                    }).catch(function() {
                        window._networkIssueDetected = true;
                        window.recoverLiveStream('play-fallback-error');
                    });
                } else {
                    window._networkIssueDetected = true;
                    window.recoverLiveStream('play-initial-error');
                }
            });
        }
    }

    window.toggleLiveAudio = function toggleLiveAudio(forceState) {
        var now = Date.now();
        if (now - _lastToggleTime < 250) return;
        _lastToggleTime = now;

        var audio = RadioAudio.get();
        if (!audio) return;

        var shouldPlay = (typeof forceState === 'boolean') ? forceState : (audio.paused || !window.isRealAudioPlaying());

        if (shouldPlay) {
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            _isRecovering = false;
            _reconnectAttempts = 0;
            window.hideReconnectingDialogue();
            var pausedDuration = Date.now() - (window._lastPauseTimestamp || 0);
            var needFresh = !window._hasPlayedSuccessfullyOnce || (pausedDuration > 25000);
            startOrResumeStream(needFresh);
        } else {
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            _isRecovering = false;
            window.userPaused = true;
            window._lastPauseTimestamp = Date.now();
            window._networkIssueDetected = false;
            window.setAudioInterrupted(false);
            RadioAudio.pause();
            window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
            window.hideReconnectingDialogue();
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
        window.setAudioInterrupted(false);
        window.showReconnectingDialogue("Connecting...", "Reconnecting to live stream...", "reconnecting");
        window.recoverLiveStream('user-force-retry');
    };

    function getReconnectDelay(attempt) {
        if (attempt <= 1) return 1000;
        if (attempt <= 3) return 2000;
        if (attempt <= 6) return 3000;
        return 4000;
    }

    window.recoverLiveStream = function(reason) {
        var now = Date.now();
        if (_isRecovering && (now - _lastRecoveryAttempt < 2000)) return;

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

        window._networkIssueDetected = true;
        window._currentTrackKey = '';
        if (typeof window.resetArtworkToStationLogo === 'function') {
            window.resetArtworkToStationLogo();
        }

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
            }, 2000);
            return;
        }

        _isRecovering = true;
        _lastRecoveryAttempt = now;
        _reconnectAttempts++;

        clearTimeout(_recoverySafetyTimer);
        _recoverySafetyTimer = setTimeout(function() {
            _isRecovering = false;
        }, 4500);

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

        window._lastPlayStartTimestamp = Date.now();
        audio.muted = false;
        audio.volume = 1.0;

        var onPlaySuccess = function() {
            _isRecovering = false;
            _reconnectAttempts = 0;
            window._hasPlayedSuccessfullyOnce = true;
            window._networkIssueDetected = false;
            clearTimeout(_reconnectTimer);
            clearTimeout(_recoverySafetyTimer);
            window.setPlayerState(window.PLAYER_STATE.PLAYING);
            if (window._hasPlayedSuccessfullyOnce) {
                window.showReconnectingDialogue("Connected!", "Live stream resumed.", "success");
            }
            if (typeof window.triggerImmediateMetadataSync === 'function') {
                window.triggerImmediateMetadataSync();
            }
            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata(true);
            }
        };

        var scheduleRetry = function(err) {
            _isRecovering = false;
            if (err && err.name === 'NotAllowedError') {
                return;
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

    // Bluetooth Route Manager:
    // 1. Phone playing + Bluetooth connects -> Seamless handoff to Bluetooth (keeps playing!)
    // 2. Bluetooth playing + Bluetooth disconnects -> Pauses immediately (0 speaker leak) & starts 1-Hour timer
    // 3. Same Bluetooth reconnects within 1 Hour -> Resumes playing automatically!
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
            var isCurrentlyPlaying = !RadioAudio.paused();

            var deviceRemoved = (currentSig.count < prevSig.count) || (currentSig.outputCount < prevSig.outputCount);
            var deviceAdded   = (currentSig.count > prevSig.count) || (currentSig.outputCount > prevSig.outputCount);

            // CASE 1: Phone is playing and a Bluetooth device connects (deviceAdded) -> Keep playing on Bluetooth!
            if (deviceAdded && isCurrentlyPlaying && !window.isAudioInterrupted()) {
                return;
            }

            // CASE 2: Playing on Bluetooth and Bluetooth disconnects (deviceRemoved) -> Pause & start 1-Hour timer!
            if (!window.isAudioInterrupted() && deviceRemoved && isCurrentlyPlaying) {
                clearTimeout(_btResumeTimer);
                clearTimeout(_reconnectTimer);
                window.userPaused = false;
                window.setAudioInterrupted(true, prevSig.hash);
                RadioAudio.pause();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                window.hideReconnectingDialogue();
                return;
            }

            // CASE 3: Bluetooth was disconnected while playing, and reconnects within the 1-Hour window!
            if (window.isAudioInterrupted() && window.isIntentStillValid() && !window.userPaused) {
                var sameDeviceReturned = window._btDisconnectedDeviceHash && (currentSig.hash === window._btDisconnectedDeviceHash);
                var routeRestored = deviceAdded || sameDeviceReturned || (currentSig.hash !== prevSig.hash && timeSinceDisconnect > 1200);

                if (routeRestored) {
                    clearTimeout(_btResumeTimer);
                    _btResumeTimer = setTimeout(function() {
                        if (window.isAudioInterrupted() && window.isIntentStillValid() && !window.userPaused) {
                            window.setAudioInterrupted(false);
                            window.userPaused = false;
                            window.recoverLiveStream('bluetooth-reconnected-' + eventSource);
                        }
                    }, 500);
                }
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

    function setupAudioListeners() {
        var liveAudio = RadioAudio.get();
        if (!liveAudio || liveAudio._hasBoundListeners) return;
        liveAudio._hasBoundListeners = true;

        liveAudio.addEventListener('playing', function() {
            _isRecovering = false;
            window._hasPlayedSuccessfullyOnce = true;
            window._networkIssueDetected = false;
            clearTimeout(_reconnectTimer);
            window.bindMasterMediaSessionHandlers();
            window.setPlayerState(window.PLAYER_STATE.PLAYING);
            if (typeof window.reassertMediaSessionMetadata === 'function') {
                window.reassertMediaSessionMetadata(true);
            }
        });

        liveAudio.addEventListener('pause', function() {
            if (liveAudio._internalPauseCall) return;
            if (!window._hasUserStartedThisSession || window.userPaused) {
                window.userPaused = true;
                window.setAudioInterrupted(false);
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                window.hideReconnectingDialogue();
                return;
            }

            if (Date.now() - window._lastPlayStartTimestamp < 1800 && _isRecovering) {
                return;
            }

            var isOnline = (typeof navigator.onLine === 'undefined') ? true : navigator.onLine;
            if (!isOnline || window._networkIssueDetected || liveAudio.error || liveAudio.ended) {
                window._networkIssueDetected = true;
                window.setPlayerState(window.PLAYER_STATE.RECONNECTING, "Reconnecting stream...");
                window.recoverLiveStream('network-pause-recovery');
                return;
            }

            setTimeout(function() {
                if (!window._hasUserStartedThisSession || window.userPaused || !liveAudio.paused) return;

                // If pause was triggered by Bluetooth disconnect, arm the 1-Hour Auto-Reconnect Timer!
                if (Date.now() - window._lastRouteEventTime < 3000 || window.isAudioInterrupted()) {
                    window.setAudioInterrupted(true, _knownAudioDevices ? _knownAudioDevices.hash : '');
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                    window.hideReconnectingDialogue();
                    return;
                }

                // Otherwise user paused from Lock Screen Notification -> Stay paused & show RC 101 Logo!
                window.userPaused = true;
                window._lastPauseTimestamp = Date.now();
                window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                window.hideReconnectingDialogue();
            }, 350);
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
    }

    window.addEventListener('online', function() {
        window._networkIssueDetected = false;
        if (typeof window.triggerImmediateMetadataSync === 'function') {
            window.triggerImmediateMetadataSync();
        }
        if (window.isIntentStillValid() && !window.userPaused && !window.isAudioInterrupted()) {
            _reconnectAttempts = 0;
            window.showReconnectingDialogue("Internet Restored", "Resuming live stream...", "reconnecting");
            window.recoverLiveStream('network-online-restored');
        }
    });

    window.addEventListener('offline', function() {
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
        if (now - _lastWatchdogRun < 6000) return;
        _lastWatchdogRun = now;

        if (!window.isIntentStillValid() || window.userPaused) return;

        var audio = RadioAudio.get();
        if (!audio) return;

        // Zero work when audio is playing so background playback never stutters!
        if (!audio.paused && !audio.error && !audio.ended) {
            return;
        }

        if (window.isAudioInterrupted()) {
            handleBluetoothRouteChange('watchdog-poll');
            return;
        }

        if (window._networkIssueDetected || audio.error || audio.ended) {
            window.recoverLiveStream('watchdog-net-' + source);
        }
    }

    setInterval(function() {
        runStreamWatchdog('interval');
    }, 6000);

    document.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest ? e.target.closest('#mini-play-btn, [data-action="toggle-play"], #navbar-play-btn, #hero-listen-btn') : null;
        if (btn) {
            if (btn.id === 'mini-play-btn' && btn.getAttribute('onclick')) return;
            e.preventDefault();
            e.stopPropagation();
            window.toggleLiveAudio();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        window.setAudioInterrupted(false);
        window.hideReconnectingDialogue();
        RadioAudio.get();
        window.bindMasterMediaSessionHandlers();
        setupAudioListeners();
        window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
        var stickyPlayer = document.getElementById('sticky-player');
        if (stickyPlayer) stickyPlayer.style.display = 'flex';
    });
</script>