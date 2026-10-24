(function () {
  function attachPicker(config) {
    var latitudeInput = document.getElementById(config.latitude);
    var longitudeInput = document.getElementById(config.longitude);
    var toggle = document.getElementById(config.toggle);
    var mapElement = document.getElementById(config.map);
    var status = document.getElementById(config.status);
    if (!latitudeInput || !longitudeInput || !toggle || !mapElement || !status) return;

    var map = null;
    var marker = null;

    function setPoint(latitude, longitude) {
      var point = [Number(latitude), Number(longitude)];
      if (!Number.isFinite(point[0]) || !Number.isFinite(point[1])
        || point[0] < -90 || point[0] > 90 || point[1] < -180 || point[1] > 180) return;
      latitudeInput.value = point[0].toFixed(6);
      longitudeInput.value = point[1].toFixed(6);
      if (!map) return;
      if (marker) marker.setLatLng(point);
      else marker = L.marker(point).addTo(map);
      map.setView(point, Math.max(map.getZoom(), 15));
    }

    toggle.addEventListener('click', function () {
      var open = !mapElement.hidden;
      mapElement.hidden = open;
      toggle.setAttribute('aria-expanded', String(!open));
      toggle.textContent = open ? 'Pick on map' : 'Hide map';
      if (open) return;
      if (typeof L === 'undefined') {
        mapElement.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = 'Pick on map';
        status.textContent = 'The map could not load. Enter latitude and longitude manually instead.';
        return;
      }
      if (!map) {
        var hasCoordinates = latitudeInput.value !== '' && longitudeInput.value !== '';
        var latitude = Number(latitudeInput.value);
        var longitude = Number(longitudeInput.value);
        if (!hasCoordinates || !Number.isFinite(latitude) || !Number.isFinite(longitude)
          || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
          latitude = 10.6765;
          longitude = 122.9509;
        }

        try {
          map = L.map(mapElement, { scrollWheelZoom: false }).setView([latitude, longitude], hasCoordinates ? 15 : 12);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          }).addTo(map);
          map.on('click', function (event) {
            setPoint(event.latlng.lat, event.latlng.lng);
            status.textContent = 'Map pin selected. Click another spot to move it.';
          });
          if (hasCoordinates) {
            marker = L.marker([latitude, longitude]).addTo(map);
          }
        } catch (error) {
          console.error('Could not open the admin report location picker.', error);
          mapElement.hidden = true;
          toggle.setAttribute('aria-expanded', 'false');
          toggle.textContent = 'Pick on map';
          status.textContent = 'The map could not load. Enter latitude and longitude manually instead.';
          return;
        }
      }
      map.invalidateSize();
      status.textContent = 'Click the map to set the report location. Both coordinates are saved with the report.';
    });

    if (latitudeInput.value !== '' && longitudeInput.value !== '') {
      status.textContent = 'Coordinates are set. Open the map to check or move the pin.';
    }
  }

  attachPicker({
    latitude: 'newReportLatitude',
    longitude: 'newReportLongitude',
    toggle: 'newReportPickLocation',
    map: 'newReportLocationPicker',
    status: 'newReportLocationStatus'
  });
  attachPicker({
    latitude: 'editReportLatitude',
    longitude: 'editReportLongitude',
    toggle: 'editReportPickLocation',
    map: 'editReportLocationPicker',
    status: 'editReportLocationStatus'
  });
})();
