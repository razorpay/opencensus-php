function executeJS() {
  var cdnDashboardUrl = window.cdnDashboardUrl || '';
  window.websiteAssets.js.forEach(function(src) {
    document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
  });
}

module.exports = `${executeJS.toString()} executeJS()`;
