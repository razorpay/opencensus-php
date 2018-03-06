;(function () {

  var sentryUrl = "https://1b84af8f6b0049a8b8fc56be8c408639@sentry.razorpay.com/12";

  document.write(
   '<script '+
      'src="https://cdn.ravenjs.com/3.22.4/raven.min.js" '+
      'crossorigin="anonymous">'+
   '</script>'
  );
  document.write(
    '<script>' +
      'Raven.config("' + sentryUrl + '").install()' +
    '</script>'
  );
}());
