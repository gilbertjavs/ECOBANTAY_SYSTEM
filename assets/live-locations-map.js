(function () {
  var mapElement = document.getElementById('liveLocationMap');
  var statusElement = document.getElementById('liveLocationStatus');
  var countElement = document.getElementById('liveLocationCount');
  if (!mapElement || !statusElement || !countElement) return;

  if (typeof L === 'undefined') {
    statusElement.textContent = 'The map could not load. Refresh this page to try again.';
    return;
  }

  var endpoint = mapElement.getAttribute('data-endpoint');
  var markers = new Map();
  var map = L.map(mapElement, { scrollWheelZoom: false }).setView([10.6765, 122.9509], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  function validLocation(location) {
    var latitude = Number(location.lat);
    var longitude = Number(location.lng);
    return Number.isFinite(latitude) && Number.isFinite(longitude)
      && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
  }

  function popupContent(location) {
    var popup = document.createElement('div');
    var name = document.createElement('strong');
    name.textContent = location.name;
    var role = document.createElement('div');
    role.textContent = location.role;
    var accuracy = document.createElement('div');
    accuracy.textContent = location.accuracy === null
      ? 'GPS accuracy not reported'
      : 'Approximate accuracy: ' + Math.round(Number(location.accuracy)) + ' m';
    var updated = document.createElement('div');
    updated.textContent = 'Last updated: ' + location.updatedAt;
    popup.appendChild(name);
    popup.appendChild(role);
    popup.appendChild(accuracy);
    popup.appendChild(updated);
    return popup;
  }

  function updateLocations(locations) {
    var currentIds = new Set();
    var bounds = [];
    locations.forEach(function (location) {
      if (!validLocation(location)) return;
      var id = String(location.id);
      currentIds.add(id);
      var point = [Number(location.lat), Number(location.lng)];
      bounds.push(point);
      var marker = markers.get(id);
      if (!marker) {
        marker = L.circleMarker(point, {
          radius: 9,
          color: '#234d35',
          fillColor: '#55bf76',
          fillOpacity: 0.9,
          weight: 2
        }).addTo(map);
        markers.set(id, marker);
      } else {
        marker.setLatLng(point);
      }
      marker.bindPopup(popupContent(location));
    });

    markers.forEach(function (marker, id) {
      if (!currentIds.has(id)) {
        map.removeLayer(marker);
        markers.delete(id);
      }
    });
    if (bounds.length && !map._hasLocatedUsers) {
      if (bounds.length > 1) map.fitBounds(bounds, { padding: [24, 24], maxZoom: 15 });
      else map.setView(bounds[0], 15);
      map._hasLocatedUsers = true;
    }
    countElement.textContent = locations.length + (locations.length === 1
      ? ' person sharing a recent location.'
      : ' people sharing a recent location.');
    statusElement.textContent = locations.length
      ? 'Map refreshes every 30 seconds. A person’s marker disappears when sharing stops or updates become stale.'
      : 'No one is currently sharing a recent location. Users must opt in from the Location Sharing page.';
  }

  function clearMarkers() {
    markers.forEach(function (marker) { map.removeLayer(marker); });
    markers.clear();
    map._hasLocatedUsers = false;
  }

  function refreshLocations() {
    fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) {
        if (!response.ok) throw new Error('Could not refresh live locations.');
        return response.json();
      })
      .then(function (data) {
        if (!data || !Array.isArray(data.locations)) throw new Error('Invalid live location response.');
        updateLocations(data.locations);
      })
      .catch(function (error) {
        console.error('Could not refresh live locations.', error);
        clearMarkers();
        countElement.textContent = 'Live locations are unavailable.';
        statusElement.textContent = 'Could not refresh the map. Current markers were cleared; try refreshing this page.';
      });
  }

  updateLocations(JSON.parse(mapElement.getAttribute('data-locations') || '[]'));
  window.setInterval(refreshLocations, 30000);
})();
