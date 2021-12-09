function merchantTncEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    const base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^/]+$/, '');

    const appendScript = (src) => {
      const s = document.createElement('script');
      s.src = src;
      document.documentElement.appendChild(s);
    };

    websiteAssets.js.forEach((src) => {
      appendScript(cdnDashboardUrl + src);
    });
  }

  return `${executeJS.toString()} executeJS()`;
}

module.exports = merchantTncEntry;
