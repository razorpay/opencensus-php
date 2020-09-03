function RazorxEntry() {
  function executeJS() {
    var cdnDashboardUrl = window.cdnDashboardUrl || '';
    var base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^\/]+$/, '');

    var appendLink = function (src) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = src;
      document.documentElement.appendChild(link);
    };

    websiteAssets.js.forEach(function (src) {
      document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
    });
    websiteAssets.css.forEach(function (src) {
      appendLink(cdnDashboardUrl + src);
    });
  }
  return `${executeJS.toString()} executeJS()`;
}

module.exports = RazorxEntry;
