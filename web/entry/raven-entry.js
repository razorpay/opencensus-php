;(function () {

  var sentryUrl = "https://1b84af8f6b0049a8b8fc56be8c408639@sentry.razorpay.com/12",
      ravenContextVals = [];

  function _setRavenContext (val) {
    Raven.setExtraContext(val);
  }

  window.setRavenContext = function (val) {

    if (!val || val.constructor !== Object) {

      return;
    }

    if (!window.Raven) {

      return ravenContextVals.push(val);
    }

    _setRavenContext(val);
  };

  var script = document.createElement("script");
  script.src = "https://cdn.ravenjs.com/3.22.4/raven.min.js";
  script.setAttribute("crossorigin", "anonymous");

  script.onload = function () {
    Raven.config(sentryUrl).install();
    Raven.setUserContext({
      id: window.rzp_user.current
    });
    ravenContextVals.forEach(_setRavenContext);
  };

  document.getElementsByTagName("head")[0].appendChild(script);
}());
