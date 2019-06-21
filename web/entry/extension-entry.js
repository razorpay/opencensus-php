(function(d, t) {
  var email = window['rzp_user'].email;

  function injectPopup() {
    // Container
    var popup = d.createElement('div');
    popup.setAttribute('class', 'ExtensionPopup');

    // Header
    var header = d.createElement('div');
    var logo = d.createElement('img');
    logo.setAttribute(
      'src',
      'https://razorpay.com/assets/razorpay-logo-white-e1ddfbf7c6.svg'
    );
    header.appendChild(logo);

    // Body
    var loggedInText = d.createElement('div');
    loggedInText.setAttribute('class', 'ExtensionPopup-Text--dark');
    loggedInText.innerHTML = 'Logged in as <b>' + email + '</b>';

    var text = d.createElement('div');
    text.setAttribute('class', 'ExtensionPopup-Text');
    text.innerHTML =
      'Click the Razorpay Icon <img src="https://razorpay.com/favicon.png" class="ExtensionPopup-Icon" /> in the top right to load the Chrome Extension';

    var showText = d.createElement('div');
    showText.setAttribute('class', 'ExtensionPopup-Text--small');
    showText.innerText = 'or continue to the dashboard.';

    // Put everything together
    popup.appendChild(header);
    popup.appendChild(loggedInText);
    popup.appendChild(text);
    popup.appendChild(showText);
    t.appendChild(popup);
  }

  // Remove splash
  d.getElementById('splash').remove();

  injectPopup();
})(document, document.body);
