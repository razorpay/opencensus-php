// Comments are not supported inside merchantEntry function have to disable the eslint for this file

function merchantEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

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

module.exports = merchantEntry;
