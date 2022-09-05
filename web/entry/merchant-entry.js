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
      document.head.appendChild(s);
    };

    websiteAssets.js.forEach((src) => {
      appendScript(cdnDashboardUrl + src);
    });

    websiteAssets.css.forEach((src) => {
      appendLink(cdnDashboardUrl + src);
    });

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
