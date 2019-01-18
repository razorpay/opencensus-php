(function() {
  var base = Array.prototype.slice
    .call(document.querySelectorAll('script[src]'), -1)[0]
    .src.replace(/[^\/]+$/, '');

  function appendChild(el) {
    document.documentElement.appendChild(el);
  }

  function appendLink(src) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = src;
    appendChild(link);
  }

  function createScript(src) {
    var script = document.createElement('script');
    script.src = src;
    script.async = false;
    return script;
  }

  if (/\.razorpay\.com$/.test(location.hostname)) {
    var sentryJs = createScript(
      'https://browser.sentry-cdn.com/4.5.1/bundle.min.js'
    );
    sentryJs.setAttribute('crossorigin', 'anonymous');

    var sentryUrl =
      'https://2a95ab4410674a558dc14669c3840974@sentry.razorpay.com/20';

    sentryJs.onload = function() {
      Sentry.configureScope(scope => {
        scope.setUser({ id: user.email });
      });
      Sentry.init({ dsn: sentryUrl });
    };

    appendChild(sentryJs);
  }

  appendChild(createScript(base + 'vendor_a.js'));
  appendChild(createScript(base + 'admin.js'));
  appendLink(base + 'css/admin.css');
})();
