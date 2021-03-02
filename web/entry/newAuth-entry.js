function NewAuthEntry() {
  function executeJS() {
    var cdnDashboardUrl = window.cdnDashboardUrl || '';
    websiteAssets.js.forEach(function (src) {
      document.write('<script src="' + cdnDashboardUrl + src + '"></script>');
    });

    const script = document.createElement('script');
    script.src = 'https://apis.google.com/js/api:client.js';
    script.async = true;
    script.defer = true;
    document.documentElement.appendChild(script);

    const gAuthOneTapscript = document.createElement('script');
    gAuthOneTapscript.src = 'https://accounts.google.com/gsi/client';
    gAuthOneTapscript.async = true;
    document.documentElement.appendChild(gAuthOneTapscript);

    window.loadHubspot = true;
  }
  return `${executeJS.toString()} executeJS()`;
}
module.exports = NewAuthEntry;
