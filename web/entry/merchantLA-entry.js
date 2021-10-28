// Comments are not supported inside merchantEntry function have to disable the eslint for this file

function MerchantLAEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';
    if (typeof window.Sentry !== 'undefined') {
      const environment = window.INSTANCE_TYPE === 'canary' ? 'canary' : window.APP_ENV;

      window.Sentry.onLoad(() => {
        window.Sentry.init({
          release: __VERSION__,
          dsn: window.SENTRY_DSN,
          environment,
          beforeSend: (event, hint) => {
            if (
              hint &&
              hint.originalException &&
              hint.originalException.code === 'UNKNOWN_ERROR_CODE'
            ) {
              return null;
            }

            return event;
          },
        });

        if (window.rzp_user && window.rzp_user.current) {
          window.Sentry.configureScope((scope) => {
            window.Sentry.setTag('app', 'MerchantLA');
            window.Sentry.setTag('role', window.rzp_user.role);

            scope.setUser({
              id: window.rzp_user.current,
            });
          });
        }
      });
    }

    const appendLink = (src) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = src;
      document.documentElement.appendChild(link);
    };

    websiteAssets.js.forEach((src) => {
      document.write(`<script src="${cdnDashboardUrl}${src}"></script>`);
    });

    websiteAssets.css.forEach((src) => {
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
module.exports = MerchantLAEntry;
