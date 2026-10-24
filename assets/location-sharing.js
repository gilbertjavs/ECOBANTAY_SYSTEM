(function () {
  var form = document.getElementById('locationSharingForm');
  var button = document.getElementById('startLocationSharing');
  var consent = document.getElementById('locationSharingConsent');
  var status = document.getElementById('locationSharingStatus');
  if (!form || !button || !consent || !status) return;

  button.addEventListener('click', function () {
    if (!consent.checked) {
      consent.focus();
      status.textContent = 'Please check the consent box before sharing your location.';
      return;
    }
    if (!navigator.geolocation) {
      status.textContent = 'Location is unavailable in this browser. Live sharing cannot start.';
      return;
    }

    button.disabled = true;
    status.textContent = 'Requesting your location. Allow access in your browser prompt to continue.';
    navigator.geolocation.getCurrentPosition(function (position) {
      form.elements.latitude.value = position.coords.latitude;
      form.elements.longitude.value = position.coords.longitude;
      form.elements.accuracy.value = position.coords.accuracy;
      status.textContent = 'Starting location sharing...';
      form.submit();
    }, function (error) {
      console.warn('Could not start live location sharing.', error);
      status.textContent = error.code === error.PERMISSION_DENIED
        ? 'Location permission was denied. Sharing remains off; allow access in browser settings if you want to try again.'
        : 'Could not get your location. Sharing remains off; please try again where GPS is available.';
      button.disabled = false;
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  });
})();
