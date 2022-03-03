(function(d, t) {
  var email = window['rzp_user'].email;

  function continueToDashboard() {
    location.reload();
  }

  function injectPopup() {
    // Container
    var popup = d.createElement('div');
    popup.setAttribute('class', 'ExtensionPopup');

    // Header
    var header = d.createElement('div');
    header.setAttribute('class', 'ExtensionPopup-Header');
    var logo = d.createElement('img');
    logo.setAttribute('src', 'https://razorpay.com/assets/razorpay-logo-white-e1ddfbf7c6.svg');
    header.appendChild(logo);

    // Body
    var loggedInText = d.createElement('div');
    loggedInText.setAttribute('class', 'ExtensionPopup-Text--dark');
    loggedInText.innerHTML = 'Logged in as <b>' + email + '</b>'; // nosemgrep : https://semgrep.dev/s/swati31196:rzp-insecure-document-method

    var text = d.createElement('div');
    text.setAttribute('class', 'ExtensionPopup-Text');
    text.innerHTML =
      'Click the Razorpay Icon <img src="https://razorpay.com/favicon.png" class="ExtensionPopup-Icon" /> in the top right to load the Chrome Extension';

    var continueText = d.createElement('div');
    continueText.setAttribute('class', 'ExtensionPopup-Text--small');
    continueText.innerText = 'or continue to the dashboard.';
    continueText.addEventListener('click', continueToDashboard);

    // Put everything together
    popup.appendChild(header);
    popup.appendChild(loggedInText);
    popup.appendChild(text);
    popup.appendChild(continueText);
    t.appendChild(popup);
  }

  // Remove splash
  d.getElementById('splash').remove();
  // Remove Key
  localStorage.removeItem('referrer');
  // Inject popup
  injectPopup();
})(document, document.body);

import '../../css/extension-popup.styl';

export default {};
