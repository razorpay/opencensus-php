function merchantTncEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    function isRelativePath(link) {
      const relativePathPattern = /^(?!www\.|(?:http|https):\/\/|[A-Za-z]:\\|\/\/).*/;
      return relativePathPattern.test(link);
    }

    const base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^/]+$/, '');

    const appendScript = (src) => {
      const s = document.createElement('script');
      s.src = src;
      document.documentElement.appendChild(s);
    };

    websiteAssets.js.forEach((src) => {
      if (!isRelativePath(src)) {
        appendScript(src);
      } else {
        appendScript(cdnDashboardUrl + src);
      }
    });
  }

  return `${executeJS.toString()} executeJS()`;
}

module.exports = merchantTncEntry;
