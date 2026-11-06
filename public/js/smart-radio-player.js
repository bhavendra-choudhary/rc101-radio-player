/**
 * SmartRadioPlayer & AudioRouteDetector — Unified Bridge to Master Core Player Engine
 * Prevents duplicate event listeners and race conditions on #live-audio
 */
(function() {
  function AudioRouteDetector(options) {
    options = options || {};
    this.onRouteConnected = options.onRouteConnected || function() {};
    this.onRouteDisconnected = options.onRouteDisconnected || function() {};
  }
  AudioRouteDetector.prototype.start = function() { return Promise.resolve(true); };
  AudioRouteDetector.prototype.stop = function() {};

  function SmartRadioPlayer(options) {
    options = options || {};
    this.streamUrl = options.streamUrl || '';
    this.audioElId = options.audioElementId || 'live-audio';
    this.onStateChange = options.onStateChange || function() {};
    this.audio = document.getElementById(this.audioElId);
    this.state = SmartRadioPlayer.STATES.IDLE;
    this.userIntentPlay = false;
    this.userPaused = false;
    window._smartPlayer = this;
  }

  SmartRadioPlayer.STATES = {
    IDLE: 'IDLE',
    LOADING: 'LOADING',
    PLAYING: 'PLAYING',
    PAUSED: 'PAUSED',
    BUFFERING: 'BUFFERING',
    INTERRUPTED: 'INTERRUPTED',
    RECONNECTING: 'RECONNECTING',
    FAILED: 'FAILED',
    STOPPED: 'STOPPED'
  };

  SmartRadioPlayer.prototype.init = function() {
    this.audio = document.getElementById(this.audioElId);
    return this;
  };

  SmartRadioPlayer.prototype.userPlay = function() {
    this.userIntentPlay = true;
    this.userPaused = false;
    if (typeof window.toggleLiveAudio === 'function') {
      window.toggleLiveAudio(true);
    }
    return Promise.resolve();
  };

  SmartRadioPlayer.prototype.userPause = function() {
    this.userIntentPlay = false;
    this.userPaused = true;
    if (typeof window.toggleLiveAudio === 'function') {
      window.toggleLiveAudio(false);
    }
  };

  SmartRadioPlayer.prototype.play = function() { return this.userPlay(); };
  SmartRadioPlayer.prototype.pause = function() { return this.userPause(); };
  SmartRadioPlayer.prototype.resume = function() { return this.userPlay(); };
  SmartRadioPlayer.prototype.stop = function() { return this.userPause(); };

  SmartRadioPlayer.prototype.toggle = function() {
    if (typeof window.toggleLiveAudio === 'function') {
      window.toggleLiveAudio();
    }
    return Promise.resolve();
  };

  SmartRadioPlayer.prototype.jumpToLiveEdge = function(reason) {
    if (typeof window.recoverLiveStream === 'function' && !window.userPaused) {
      window.recoverLiveStream('jumpToLiveEdge-' + (reason || 'manual'));
    }
  };

  SmartRadioPlayer.prototype.setUserPaused = function(val) {
    if (val) this.userPause();
    else this.userPlay();
  };

  SmartRadioPlayer.prototype.destroy = function() {};

  window.AudioRouteDetector = AudioRouteDetector;
  window.SmartRadioPlayer = SmartRadioPlayer;
})();