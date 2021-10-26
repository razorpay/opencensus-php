function NewAuthEntry() {
  function executeJS() {
    function appendScript(src, async, defer) {
      const script = document.createElement('script');
      script.src = src;
      script.async = async;
      script.defer = defer;
      document.documentElement.appendChild(script);
    }

    const cdnDashboardUrl = window.cdnDashboardUrl || '';
    // eslint-disable-next-line
    websiteAssets.js.forEach(function (src) {
      appendScript(cdnDashboardUrl + src, false, true);
    });

    if (!window.location.hostname.includes('axis')) {
      appendScript('https://apis.google.com/js/api:client.js', true, true);
    }

    window.loadHubspot = true;
  }
  return `${executeJS.toString()} executeJS()`;
}
module.exports = NewAuthEntry;
