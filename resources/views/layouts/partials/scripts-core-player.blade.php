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

    // Exact same clean <audio> setup that triggered Chrome's native notification in 25781.jpg & zero background stutter!
    var RadioAudio = (function() {
        var _el = null;
        return {
            get: function() {
                if (_el && document.body && document.body.contains(_el)) return _el;
                _el = document.getElementById('live-audio');
                if (_el) {
                    _el.style.cssText = 'display:none !important; width:0; height:0; pointer-events:none;';
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
                        setTimeout(function() { a._internalPauseCall = false; }, 200);
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

    // Connect Play/Pause actions of Browser's Native Notification directly to our player
    window.bindNativeNotificationControls = function() {
        if (!('mediaSession' in navigator)) return;
        try {
            navigator.mediaSession.setActionHandler('play', function() {
                clearTimeout(_reconnectTimer);
                clearTimeout(_recoverySafetyTimer);
                _isRecovering = false;
                var pausedDuration = Date.now() - (window._lastPauseTimestamp || 0);
                startOrResumeStream(pausedDuration > 25000);
            });
            navigator.mediaSession.setActionHandler('pause', function() {
                clearTimeout(_reconnectTimer);
                clearTimeout(_recoverySafetyTimer);
                _isRecovering = false;
                window._lastPauseTimestamp = Date.now();

                var sinceRouteChange = Date.now() - window._lastRouteEventTime;
                if (sinceRouteChange < 2500 || window.isAudioInterrupted()) {
                    window.userPaused = false;
                    window.setAudioInterrupted(true);
                    RadioAudio.pause();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                } else {
                    // User tapped Pause on the Lock Screen Notification -> Lock pause state 100% so it NEVER restarts by itself!
                    window.userPaused = true;
                    window._networkIssueDetected = false;
                    window.setAudioInterrupted(false);
                    RadioAudio.pause();
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
                    window.hideReconnectingDialogue();
                }
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

        var playPromise = RadioAudio.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(function() {
                _reconnectAttempts = 0;
                window._hasPlayedSuccessfullyOnce = true;
                window.setPlayerState(window.PLAYER_STATE.PLAYING);
                window.hideReconnectingDialogue();
                if (typeof window.syncNativeBrowserNotification === 'function') {
                    window.syncNativeBrowserNotification();
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
                        if (typeof window.syncNativeBrowserNotification === 'function') {
                            window.syncNativeBrowserNotification();
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
            if (typeof window.syncNativeBrowserNotification === 'function') {
                window.syncNativeBrowserNotification();
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
            var wasPlayingRecently = !RadioAudio.paused();

            var deviceRemoved = (currentSig.count < prevSig.count) || (currentSig.outputCount < prevSig.outputCount);
            var routeShiftedWhilePlaying = (eventSource === 'devicechange' && currentSig.hash !== prevSig.hash && wasPlayingRecently && !window.isAudioInterrupted());

            if (!window.isAudioInterrupted() && (deviceRemoved || routeShiftedWhilePlaying)) {
                clearTimeout(_btResumeTimer);
                clearTimeout(_reconnectTimer);
                window.userPaused = false;
                window.setAudioInterrupted(true);
                RadioAudio.pause();
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

    function setupAudioListeners() {
        var liveAudio = RadioAudio.get();
        if (!liveAudio || liveAudio._hasBoundListeners) return;
        liveAudio._hasBoundListeners = true;

        liveAudio.addEventListener('playing', function() {
            _isRecovering = false;
            window._hasPlayedSuccessfullyOnce = true;
            window._networkIssueDetected = false;
            clearTimeout(_reconnectTimer);
            window.setPlayerState(window.PLAYER_STATE.PLAYING);
            if (typeof window.syncNativeBrowserNotification === 'function') {
                window.syncNativeBrowserNotification();
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

                if (Date.now() - window._lastRouteEventTime < 3000 || window.isAudioInterrupted()) {
                    window.setAudioInterrupted(true);
                    window.setPlayerState(window.PLAYER_STATE.PAUSED, "Paused (Bluetooth Disconnected)");
                    window.hideReconnectingDialogue();
                    return;
                }

                // When paused from Browser's Native Lock Screen Notification, mark userPaused = true so it NEVER restarts!
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
        window.bindNativeNotificationControls();
        setupAudioListeners();
        window.setPlayerState(window.PLAYER_STATE.PAUSED, "Press Play To Listen");
        var stickyPlayer = document.getElementById('sticky-player');
        if (stickyPlayer) stickyPlayer.style.display = 'flex';
    });
</script>