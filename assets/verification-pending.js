(function () {
  var script = document.currentScript;
  var endpoint = script && script.dataset.endpoint;
  var token = script && script.dataset.token;
  var status = document.getElementById('verificationStatus');
  if (!endpoint || !token || !status) return;

  var checking = false;
  var finished = false;

  function checkVerification() {
    if (checking || finished) return;
    checking = true;
    var body = new URLSearchParams({ csrf_token: token });
    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body
    }).then(function (response) {
      return response.json().then(function (data) {
        if (!response.ok || !data || !data.ok) {
          throw new Error(data && data.message ? data.message : 'Could not check account verification.');
        }
        return data;
      });
    }).then(function (data) {
      if (data.state === 'signed_in' && data.redirect) {
        finished = true;
        status.textContent = 'Your account is verified. Signing you in now…';
        window.location.assign(data.redirect);
      } else if (data.state === 'pending') {
        status.textContent = 'Still waiting for barangay verification. This page will sign you in automatically once approved.';
      } else {
        throw new Error('Received an unexpected verification response.');
      }
    }).catch(function (error) {
      console.warn('Could not check account verification.', error);
      status.textContent = error.message + ' You can refresh this page to try again.';
    }).then(function () {
      checking = false;
    });
  }

  checkVerification();
  window.setInterval(checkVerification, 5000);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') checkVerification();
  });
})();
