(function () {
  var mapElement = document.getElementById('reportMap');
  var statusElement = document.getElementById('reportMapStatus');
  if (!mapElement || !statusElement) return;

  if (typeof L === 'undefined') {
    statusElement.textContent = 'The map could not load. You can still open individual report locations from their details.';
    return;
  }

  try {
    var reports = JSON.parse(mapElement.getAttribute('data-markers') || '[]');
    var map = L.map(mapElement, { scrollWheelZoom: false }).setView([10.6765, 122.9509], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    var bounds = [];
    var colors = {
      Pending: '#c28718',
      Investigating: '#2b7280',
      Verified: '#386b43',
      'Action Taken': '#7053a0',
      Resolved: '#26803a',
      Rejected: '#a64236'
    };

    reports.forEach(function (report) {
      var latitude = Number(report.lat);
      var longitude = Number(report.lng);
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)
        || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return;

      var point = [latitude, longitude];
      bounds.push(point);
      var color = colors[report.status] || '#386b43';
      var marker = L.circleMarker(point, {
        radius: 8,
        color: color,
        fillColor: color,
        fillOpacity: 0.85,
        weight: 2
      }).addTo(map);

      var popup = document.createElement('div');
      var heading = document.createElement('strong');
      heading.textContent = '#' + report.report + ' · ' + report.problem;
      var location = document.createElement('div');
      location.textContent = report.location + ' · ' + report.status;
      if (report.photo) {
        var photo = document.createElement('img');
        photo.src = report.photo;
        photo.alt = 'Photo attached to report ' + report.report;
        photo.loading = 'lazy';
        photo.referrerPolicy = 'no-referrer';
        photo.className = 'report-map-photo';
        popup.appendChild(photo);
      }
      var link = document.createElement('a');
      link.href = report.href;
      link.textContent = 'Open report';
      popup.appendChild(heading);
      popup.appendChild(location);
      popup.appendChild(link);
      marker.bindPopup(popup);
    });

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [24, 24], maxZoom: 15 });
    } else if (bounds.length === 1) {
      map.setView(bounds[0], 15);
    }
    statusElement.textContent = reports.length
      ? 'Select a pin to view its report. Marker colors indicate report status. Map tiles are provided by OpenStreetMap.'
      : 'No reports in the current results have coordinates yet. Add coordinates when submitting or editing a report to show it here.';
  } catch (error) {
    console.error('Could not display the report map.', error);
    statusElement.textContent = 'The map could not be displayed. You can still open individual report locations from their details.';
  }
})();
