function merchantTncEntry() {
  function executeJS() {
    var cdnDashboardUrl = window.cdnDashboardUrl || '';

    var base = Array.prototype.slice
      .call(document.querySelectorAll('script[src]'), -1)[0]
      .src.replace(/[^\/]+$/, '');

    websiteAssets.js.forEach(function (src) {
      document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
    });
  }

  return `${executeJS.toString()} executeJS()`;
}

module.exports = merchantTncEntry;
