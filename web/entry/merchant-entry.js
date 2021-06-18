function merchantEntry() {
  function executeJS() {
    var cdnDashboardUrl = window.cdnDashboardUrl || '';
    if (typeof Sentry !== 'undefined') {
      Sentry.onLoad(() => {
        Sentry.init({
          dsn: window.SENTRY_DSN,
          integrations: [new Sentry.Integrations.BrowserTracing()],
          tracesSampleRate: 1.0,
          environment: window.APP_ENV,
        });

        if (window.rzp_user && window.rzp_user.current) {
          Sentry.configureScope((scope) => {
            Sentry.setTag('app', 'Merchant');
            Sentry.setTag('role', window.rzp_user.role);

            scope.setUser({
              id: window.rzp_user.current,
            });
          });
        }
      });
    }

    var base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^\/]+$/, '');

    var appendLink = function (src) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = src;
      document.documentElement.appendChild(link);
    };

    websiteAssets.js.forEach(function (src) {
      document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
    });
    websiteAssets.css.forEach(function (src) {
      appendLink(cdnDashboardUrl + src);
    });

    appendLink('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

    const script = document.createElement('script');
    script.src = 'https://apis.google.com/js/api:client.js';
    script.async = true;
    script.defer = true;
    document.documentElement.appendChild(script);
    window.loadHubspot = true;
  }

  return `${executeJS.toString()} executeJS()`;
}

module.exports = merchantEntry;
