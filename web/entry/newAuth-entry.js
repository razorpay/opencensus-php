function executeJS() {
  var cdnDashboardUrl = window.cdnDashboardUrl || '';
  window.websiteAssets.js.forEach(function(src) {
    document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
  });
  
  const script = document.createElement('script');
  script.src = 'https://apis.google.com/js/api:client.js';
  script.async = true;
  script.defer = true;
  document.documentElement.appendChild(script);
  
  window.loadHubspot = true;
}

module.exports = `${executeJS.toString()} executeJS()`;
