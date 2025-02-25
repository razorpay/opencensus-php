// Comments are not supported inside merchantEntry function have to disable the eslint for this file

function MerchantLAEntry() {
  function executeJS() {
    const cdnDashboardUrl = window.cdnDashboardUrl || '';

    function isRelativePath(link) {
      console.log(link);
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

    appendLink('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

    const script = document.createElement('script');
    script.src = 'https://apis.google.com/js/api:client.js';
    script.async = false;
    script.defer = true;
    document.documentElement.appendChild(script);

    window.loadHubspot = true;
  }
  return `${executeJS.toString()} executeJS()`;
}
module.exports = MerchantLAEntry;
