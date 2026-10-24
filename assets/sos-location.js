(function () {
  var mapElement = document.getElementById('sosLocationMap');
  var statusElement = document.getElementById('sosLocationStatus');
  var latitudeElement = document.getElementById('sosLatitude');
  var longitudeElement = document.getElementById('sosLongitude');
  var latitudeInput = document.getElementById('sosLatitudeInput');
  var longitudeInput = document.getElementById('sosLongitudeInput');
  var useGpsButton = document.getElementById('useMyGps');
  var alertButton = document.getElementById('sendSosAlert');
  if (!mapElement || !statusElement || !latitudeElement || !longitudeElement
    || !latitudeInput || !longitudeInput || !useGpsButton || !alertButton) return;

  var initialLocation = null;
  try {
    initialLocation = JSON.parse(mapElement.getAttribute('data-location') || 'null');
  } catch (error) {
    console.error('Could not read the initial location.', error);
  }

  var map = null;
  var marker = null;
  if (typeof L !== 'undefined') {
    var initialPoint = initialLocation
      ? [Number(initialLocation.lat), Number(initialLocation.lng)]
      : [10.6765, 122.9509];
    map = L.map(mapElement, { scrollWheelZoom: false }).setView(initialPoint, initialLocation ? 16 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
  } else {
    statusElement.textContent = 'The map could not load. GPS and live-sharing controls remain available.';
  }

  function setLocation(latitude, longitude, description) {
    var lat = Number(latitude);
    var lng = Number(longitude);
    if (!Number.isFinite(lat) || lat < -90 || lat > 90 || !Number.isFinite(lng) || lng < -180 || lng > 180) {
      statusElement.textContent = 'The selected location is invalid. Choose another point or use GPS.';
      return;
    }

    var point = [lat, lng];
    latitudeInput.value = lat.toFixed(7);
    longitudeInput.value = lng.toFixed(7);
    latitudeElement.textContent = lat.toFixed(5);
    longitudeElement.textContent = lng.toFixed(5);
    alertButton.disabled = false;
    if (map) {
      if (!marker) {
        marker = L.circleMarker(point, {
          radius: 9,
          color: '#b8431e',
          fillColor: '#ff642d',
          fillOpacity: 0.95,
          weight: 2
        }).addTo(map);
      } else {
        marker.setLatLng(point);
      }
      marker.bindPopup('Your selected location').openPopup();
      map.setView(point, 16);
    }
    statusElement.textContent = description;
  }

  if (initialLocation) {
    setLocation(initialLocation.lat, initialLocation.lng, 'Showing your latest shared location. Click the map to choose a different point for an SOS.');
  } else {
    alertButton.disabled = true;
  }

  if (map) {
    map.on('click', function (event) {
      setLocation(event.latlng.lat, event.latlng.lng, 'Map point selected. This location will be included only if you send an SOS alert.');
    });
  }

  useGpsButton.addEventListener('click', function () {
    if (!navigator.geolocation) {
      statusElement.textContent = 'Location is unavailable in this browser. You can select a point directly on the map.';
      return;
    }
    useGpsButton.disabled = true;
    statusElement.textContent = 'Requesting your location. Allow access in the browser prompt to continue.';
    navigator.geolocation.getCurrentPosition(function (position) {
      var accuracy = Math.round(position.coords.accuracy);
      setLocation(position.coords.latitude, position.coords.longitude,
        'Showing your current GPS position (approximate accuracy: ' + accuracy + ' m). It is not sent until you submit an SOS alert.');
      useGpsButton.disabled = false;
    }, function (error) {
      statusElement.textContent = error.code === error.PERMISSION_DENIED
        ? 'Location permission was denied. Allow access in browser settings or select a point on the map.'
        : 'Could not get your location. Try again where GPS is available or select a point on the map.';
      useGpsButton.disabled = false;
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  });

  var shareForm = document.getElementById('dashboardLiveShareForm');
  var shareConsent = document.getElementById('dashboardLiveShareConsent');
  var shareButton = document.getElementById('enableDashboardLiveShare');
  if (shareForm && shareConsent && shareButton) {
    shareButton.addEventListener('click', function () {
      if (!shareConsent.checked) {
        shareConsent.focus();
        statusElement.textContent = 'Confirm the live-sharing consent before enabling live location updates.';
        return;
      }
      if (!navigator.geolocation) {
        statusElement.textContent = 'Location is unavailable in this browser. Live sharing cannot start.';
        return;
      }

      shareButton.disabled = true;
      statusElement.textContent = 'Requesting your location to start live sharing. Allow access in the browser prompt.';
      navigator.geolocation.getCurrentPosition(function (position) {
        var form = new URLSearchParams({
          csrf_token: shareForm.elements.csrf_token.value,
          mode: 'enable',
          consent: 'yes',
          latitude: String(position.coords.latitude),
          longitude: String(position.coords.longitude),
          accuracy: String(position.coords.accuracy)
        });
        fetch(shareForm.getAttribute('data-endpoint'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'Accept': 'application/json'
          },
          body: form
        }).then(function (response) {
          return response.json().then(function (result) {
            if (!response.ok || !result.ok) throw new Error(result.message || 'Live location sharing could not be enabled.');
            window.location.reload();
          });
        }).catch(function (error) {
          statusElement.textContent = error.message || 'Live location sharing could not be enabled. Please try again.';
          shareButton.disabled = false;
        });
      }, function (error) {
        statusElement.textContent = error.code === error.PERMISSION_DENIED
          ? 'Location permission was denied. Live sharing remains off.'
          : 'Could not get your location. Live sharing remains off; please try again where GPS is available.';
        shareButton.disabled = false;
      }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
    });
  }
})();
