function RazorxEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    const base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^/]+$/, '');

    function isRelativePath(link) {
      const relativePathPattern = /^(?!www\.|(?:http|https):\/\/|[A-Za-z]:\\|\/\/).*/;
      return relativePathPattern.test(link);
    }

    const appendLink = (src) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = src;
      document.documentElement.appendChild(link);
    };

    const appendScript = (src) => {
      const s = document.createElement('script');
      s.src = src;
      document.documentElement.appendChild(s);
    };

    appendLink(
      'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
    );

    websiteAssets.js.forEach((src) => {
      if (!isRelativePath(src)) {
        appendScript(src);
      } else {
        appendScript(cdnDashboardUrl + src);
      }
    });
    websiteAssets.css.forEach((src) => {
      if (!isRelativePath(src)) {
        appendLink(src);
      } else {
        appendLink(cdnDashboardUrl + src);
      }
    });
  }
  return `${executeJS.toString()} executeJS()`;
}

module.exports = RazorxEntry;
