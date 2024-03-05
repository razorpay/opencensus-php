// Comments are not supported inside merchantEntry function have to disable the eslint for this file

function merchantEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    const appendLink = (src) => {
      const link = document.createElement('link');
      link.type = 'text/css';
      link.rel = 'stylesheet';
      link.href = src;
      document.head.appendChild(link);
    };

    const appendScript = (src) => {
      const s = document.createElement('script');
      s.src = src;
      document.documentElement.appendChild(s);
    };

    websiteAssets.css.forEach((src) => {
      if (isRedirector) {
        appendLink(src);
      } else {
        appendLink(cdnDashboardUrl + src);
      }
    });

    websiteAssets.js.forEach((src) => {
      if (isRedirector) {
        appendScript(src);
      } else {
        appendScript(cdnDashboardUrl + src);
      }
    });

    const script = document.createElement('script');
    script.src = 'https://apis.google.com/js/api:client.js';
    script.async = false;
    script.defer = true;
    document.documentElement.appendChild(script);
    window.loadHubspot = true;
  }

  return `${executeJS.toString()} executeJS()`;
}

module.exports = merchantEntry;
