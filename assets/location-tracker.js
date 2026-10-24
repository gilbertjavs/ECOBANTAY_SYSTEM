(function () {
  var script = document.currentScript;
  var indicator = document.getElementById(script && script.dataset.indicator);
  var status = document.getElementById(script && script.dataset.status);
  var endpoint = script && script.dataset.endpoint;
  var token = script && script.dataset.token;
  if (!endpoint || !token) return;

  function setIndicator(message, active) {
    if (!indicator) return;
    var label = indicator.querySelector('.location-share-label');
    if (label) label.textContent = message;
    indicator.classList.toggle('is-active', active);
    indicator.setAttribute('aria-label', active
      ? 'Live location sharing is on. Manage sharing.'
      : 'Live location sharing is paused. Manage sharing.');
    if (status) {
      status.textContent = active
        ? 'Your current location is being shared with authorized barangay administrators.'
        : 'Location sharing is paused. Your marker will disappear unless location updates resume.';
    }
  }

  function sendLocation() {
    if (!navigator.geolocation) {
      setIndicator('Location paused', false);
      return;
    }
    navigator.geolocation.getCurrentPosition(function (position) {
      var body = new URLSearchParams({
        csrf_token: token,
        mode: 'update',
        latitude: String(position.coords.latitude),
        longitude: String(position.coords.longitude),
        accuracy: String(position.coords.accuracy)
      });
      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: body
      }).then(function (response) {
        if (!response.ok) throw new Error('Location update was rejected.');
        return response.json();
      }).then(function (result) {
        if (!result.ok) throw new Error('Location update was not accepted.');
        setIndicator('Sharing ON', true);
      }).catch(function (error) {
        console.warn('Could not update shared location.', error);
        setIndicator('Location paused', false);
      });
    }, function (error) {
      console.warn('Could not refresh shared location.', error);
      setIndicator('Location paused', false);
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  }

  sendLocation();
  window.setInterval(sendLocation, 60000);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') sendLocation();
  });
})();
