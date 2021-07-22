(function () {
  function theme(
    it,
    /*``*/
  ) {
    var orgBg = '';
    if (it.navBgUrl)
      orgBg =
        '.auth-container { background: url("' +
        it.navBgUrl +
        '") no-repeat !important; background-size: contain !important; } .rzp-logo .logo { visibility:' +
        (it.navBgUrl ? 'hidden' : 'visible') +
        ' !important; }';
    var out =
      '.ds-btn.secondary{ color: ' +
      it.primary +
      ' !important; border:1px solid ' +
      it.primary +
      ' !important;}' +
      '.forgot-password-text { color: ' +
      it.primary +
      ' !important;}' +
      '.ds-highlight-clickable-text{ color: ' +
      it.primary +
      ' !important;}' +
      '.highlight-clickable-text{ color:' +
      it.primary +
      ' !important;}' +
      'body.bob .navbar-brand img { width: 100%; height: 100%;}.bg-dark { background: ' +
      (it.navBg || it.primary) +
      ';}.bg-dark .nav>li>a:hover { background: ' +
      it.transparent +
      ';}.bg-dark .nav > li:focus > a,.bg-dark .nav > li.active > a { background: ' +
      it.transparentDark +
      '}.bg-dark .nav>li.active { background: ' +
      it.transparentDark +
      ';}.bg-dark .nav>li.active a{ background: none;}.bg-dark .text-warning-lter,.bg-dark .nav > li > a,.bg-dark .text-info,.bg-dark .text-muted { color: ' +
      it.textLight +
      ' !important;}.bg-dark .line { background-color: ' +
      it.transparent +
      ';}.table-striped > tbody > tr:nth-child(odd) > td,.table-striped > tbody > tr:nth-child(odd) > th { background-color: ' +
      it.primaryTransparent +
      ';}.table-striped > tbody > tr:nth-child(even) > td,.table-striped > tbody > tr:nth-child(even) > th { background-color: #ffffff;} .btn-primary { background-color: ' +
      it.primary +
      '!important; border-color: ' +
      it.primary +
      '!important; }.bg-light.lter, .bg-light .lter { background-color: ' +
      it.primaryTransparent +
      ' !important;}.panel-default .panel-heading { background-color: ' +
      it.primaryTransparent +
      ';}.form-control[disabled],.form-control[readonly],fieldset[disabled] .form-control { background-color: ' +
      it.primaryTransparent +
      ';}.alert-danger { background-color: ' +
      it.errorBackground +
      '; color: ' +
      it.tertiary +
      ';}@-webkit-keyframes changebar { 0% { background-color: ' +
      it.primary +
      '; } 33.3% { background-color: ' +
      it.primary +
      '; } 33.33% { background-color: ' +
      it.secondary +
      '; } 66.6% { background-color: ' +
      it.secondary +
      '; } 66.66% { background-color: ' +
      it.tertiary +
      '; } 99.9% { background-color: ' +
      it.tertiary +
      '; }}@-moz-keyframes changebar { 0% { background-color: ' +
      it.primary +
      '; } 33.3% { background-color: ' +
      it.primary +
      '; } 33.33% { background-color: ' +
      it.secondary +
      '; } 66.6% { background-color: ' +
      it.secondary +
      '; } 66.66% { background-color: ' +
      it.tertiary +
      '; } 99.9% { background-color: ' +
      it.tertiary +
      '; }}@keyframes changebar { 0% { background-color: ' +
      it.primary +
      '; } 33.3% { background-color: ' +
      it.primary +
      '; } 33.33% { background-color: ' +
      it.secondary +
      '; } 66.6% { background-color: ' +
      it.secondary +
      '; } 66.66% { background-color: ' +
      it.primaryLight +
      '; } 99.9% { background-color: ' +
      it.primaryLight +
      '; }}';
    return orgBg + ' ' + out;
  }
  var itself = theme,
    _encodeHTML = (function (doNotSkipEncoded) {
      var encodeHTMLRules = {
          '&': '&#38;',
          '<': '&#60;',
          '>': '&#62;',
          '"': '&#34;',
          "'": '&#39;',
          '/': '&#47;',
        },
        matchHTML = doNotSkipEncoded ? /[&<>"'\/]/g : /&(?!#?\w+;)|<|>|"|'|\//g;
      return function (code) {
        return code
          ? code.toString().replace(matchHTML, function (m) {
              return encodeHTMLRules[m] || m;
            })
          : '';
      };
    })();
  if (typeof module !== 'undefined' && module.exports) module.exports = itself;
  else if (typeof define === 'function')
    define(function () {
      return itself;
    });
  else {
    themes = themes || {};
    themes['theme'] = itself;
  }
})();
