(function () {
  var latitudeInput = document.getElementById('latitudeInput');
  var longitudeInput = document.getElementById('longitudeInput');
  var useLocationButton = document.getElementById('useCurrentLocationBtn');
  var pickLocationButton = document.getElementById('pickLocationBtn');
  var mapElement = document.getElementById('reportLocationPicker');
  var statusElement = document.getElementById('reportLocationStatus');
  if (!latitudeInput || !longitudeInput || !useLocationButton || !pickLocationButton || !mapElement || !statusElement) return;

  var map = null;
  var marker = null;

  function setCoordinates(latitude, longitude) {
    latitudeInput.value = Number(latitude).toFixed(6);
    longitudeInput.value = Number(longitude).toFixed(6);
    if (map) {
      var point = [Number(latitude), Number(longitude)];
      if (marker) marker.setLatLng(point);
      else marker = L.marker(point).addTo(map);
      map.setView(point, Math.max(map.getZoom(), 15));
    }
  }

  function initializeMap() {
    if (map) {
      map.invalidateSize();
      return true;
    }
    if (typeof L === 'undefined') {
      statusElement.textContent = 'The map could not load. You can still use your location or enter coordinates manually.';
      return false;
    }

    try {
      var initialLatitude = Number(latitudeInput.value);
      var initialLongitude = Number(longitudeInput.value);
      var hasInitialPoint = latitudeInput.value !== '' && longitudeInput.value !== ''
        && Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
      var initialPoint = hasInitialPoint ? [initialLatitude, initialLongitude] : [10.6765, 122.9509];

      map = L.map(mapElement, { scrollWheelZoom: false }).setView(initialPoint, hasInitialPoint ? 15 : 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      }).addTo(map);
      map.on('click', function (event) {
        setCoordinates(event.latlng.lat, event.latlng.lng);
        statusElement.textContent = 'Map pin selected. You can move it by clicking another spot.';
      });
      if (hasInitialPoint) marker = L.marker(initialPoint).addTo(map);
      map.invalidateSize();
      return true;
    } catch (error) {
      console.error('Could not open the report location picker.', error);
      statusElement.textContent = 'The map could not load. You can still use your location or enter coordinates manually.';
      return false;
    }
  }

  pickLocationButton.addEventListener('click', function () {
    var isOpen = !mapElement.hidden;
    mapElement.hidden = isOpen;
    pickLocationButton.setAttribute('aria-expanded', String(!isOpen));
    pickLocationButton.textContent = isOpen ? 'Pick on map' : 'Hide map';
    if (isOpen) return;

    if (!initializeMap()) {
      mapElement.hidden = true;
      pickLocationButton.setAttribute('aria-expanded', 'false');
      pickLocationButton.textContent = 'Pick on map';
      return;
    }
    statusElement.textContent = 'Click the map to place the report pin. The pin is saved only when you submit the report.';
  });

  useLocationButton.addEventListener('click', function () {
    if (!navigator.geolocation) {
      statusElement.textContent = 'Location is unavailable in this browser. Choose a spot on the map or enter coordinates manually.';
      return;
    }

    useLocationButton.disabled = true;
    statusElement.textContent = 'Getting your location. Allow location access when your browser asks.';
    navigator.geolocation.getCurrentPosition(function (position) {
      setCoordinates(position.coords.latitude, position.coords.longitude);
      if (mapElement.hidden) {
        mapElement.hidden = false;
        pickLocationButton.setAttribute('aria-expanded', 'true');
        pickLocationButton.textContent = 'Hide map';
        if (!initializeMap()) {
          mapElement.hidden = true;
          pickLocationButton.setAttribute('aria-expanded', 'false');
          pickLocationButton.textContent = 'Pick on map';
          statusElement.textContent = 'Your GPS coordinates are selected, but the map preview could not load. You can still submit the location or edit the coordinates.';
          useLocationButton.disabled = false;
          return;
        }
        if (map) setCoordinates(position.coords.latitude, position.coords.longitude);
      }
      statusElement.textContent = 'Your location is selected. Confirm that the pin marks the trash problem before submitting.';
      useLocationButton.disabled = false;
    }, function (error) {
      console.warn('Could not get the report location.', error);
      var message = error.code === error.PERMISSION_DENIED
        ? 'Location access was denied. Choose a spot on the map or enter coordinates manually.'
        : 'Could not get your location. Choose a spot on the map or enter coordinates manually.';
      statusElement.textContent = message;
      useLocationButton.disabled = false;
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
  });

  if (latitudeInput.value !== '' && longitudeInput.value !== '') {
    statusElement.textContent = 'Coordinates are set. Open the map to check or move the pin.';
  }
})();
