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

    function isRelativePath(link) {
      const relativePathPattern = /^(?!www\.|(?:http|https):\/\/|[A-Za-z]:\\|\/\/).*/;
      return relativePathPattern.test(link);
    }

    /* eslint-disable-next-line */
    websiteAssets.js.forEach(function (src) {
      if (!isRelativePath(src)) {
        appendScript(src, false, true);
      } else {
        appendScript(cdnDashboardUrl + src, false, true);
      }
    });

    if (!window.location.hostname.includes('axis')) {
      appendScript('https://apis.google.com/js/api:client.js', false, true);
    }

    window.loadHubspot = true;
  }
  return `${executeJS.toString()} executeJS()`;
}
module.exports = NewAuthEntry;
