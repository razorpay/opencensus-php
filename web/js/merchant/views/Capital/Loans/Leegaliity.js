!(function () {
  'use strict';
  function t(t) {
    (this.name = 'LeegalityExecption'), (this.message = t);
  }
  (window.Leegality = function (e) {
    if (!e || !e.callback) throw new t('Callback function is required.');
    (this.callback = e.callback), (this.logoUrl = e.logoUrl);
  }).prototype = {
    popup: null,
    callback: null,
    logoUrl: null,
    responseData: null,
    handleResponse: function (t) {
      this.cancel(), this.callback(t), (this.responseData = null);
    },
    getLoadingHtml: function () {
      var t =
          '<html><head><meta http-equiv="Content-Security-Policy" content="default-src *; img-src * \'self\' data: https:; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' *; style-src \'self\' \'unsafe-inline\' *"><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script><script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script><style>body{background: #d2d6de;}.logo-div{margin-top: 5%;}.loading-div{margin-top: 10%;}.text-center{text-align: center;}.loading-text{font-size: 16px;}.logo-image{max-width: 50%;}.loader{max-width: 40px;max-height: 40px;}</style></head><body><div className="container"><div className="row logo-div"><div className="col-md-4"></div><div className="col-md-4 text-center"><img src="',
        e =
          '" className="logo-image"></div><div className="col-md-4"></div></div><div className="row loading-div"><div className="col-md-4"></div><div className="col-md-4 text-center"><p className="loading-text">Loading..Please Wait!</p><img src="https://s3.ap-south-1.amazonaws.com/leegality-production-helpers/spinner2.gif" className="loader"/></div><div className="col-md-4"></div></div></div></body></html>';
      return this.logoUrl
        ? t + this.logoUrl + e
        : t +
            'https://s3.ap-south-1.amazonaws.com/leegality-production-helpers/leegality-logo.png' +
            e;
    },
    init: function () {
      this.popup && this.popup.close();
      var t = 0.4 * screen.width,
        e = 0.85 * screen.height,
        s = 0.3 * screen.width;
      (this.popup = window.open(
        '',
        '_blank',
        'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=0,width=' +
          t +
          ',height=' +
          e +
          ',top=0, left=' +
          s,
      )),
        this.popup.document.write(this.getLoadingHtml());
      var o = this;
      window.addEventListener(
        'message',
        function (t) {
          var e = t.data;
          e &&
            'object' == typeof e &&
            (e.hasOwnProperty('error') ||
              (e.hasOwnProperty('message') && e.hasOwnProperty('documentId'))) &&
            (o.responseData = e);
        },
        !1,
      );
      var i = window.setInterval(function () {
        o.responseData
          ? (window.clearInterval(i), o.handleResponse(o.responseData))
          : o.popup.closed && (o.handleResponse({ error: 'Cancelled.' }), window.clearInterval(i));
      }, 1e3);
    },
    esign: function (e) {
      if (!e) throw new t('Url is required.');
      if (!this.popup) throw new t('Intialize signing first.');
      this.popup.location = e;
    },
    cancel: function () {
      this.popup && this.popup.close();
    },
  };
})();
