function PokedexEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    const base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^/]+$/, '');

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

    websiteAssets.js.forEach((src) => {
      appendScript(cdnDashboardUrl + src);
    });
    websiteAssets.css.forEach((src) => {
      appendLink(cdnDashboardUrl + src);
    });
    appendLink('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  }
  return `${executeJS.toString()} executeJS()`;
}

module.exports = PokedexEntry;
