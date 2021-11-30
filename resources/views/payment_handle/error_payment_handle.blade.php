<!doctype html>
<html>
  <head>
    <title>Error</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1">
    <link rel="icon" href="https://razorpay.com/favicon.png" />
    <script>
      function renderApp() {
          if(window.RZP && window.RZP.hasOwnProperty("renderApp")) {
              var data = {!!utf8_json_encode($data)!!};

              window.RZP.renderApp('app-container', data);
          }
      }
    </script>
    <link rel="preload" as="image" href="https://cdn.razorpay.com/static/assets/error.svg" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,400;0,600;0,800;1,700&display=swap" rel="stylesheet">
    <link
      rel="stylesheet"
      type="text/css"
      href="{{env('AWS_CF_CDN_URL')}}/static/payment-handle/error.css"
    />
    <script src="{{env('AWS_CF_CDN_URL')}}/static/analytics/bundle.js" defer></script>
    <script src="{{env('AWS_CF_CDN_URL')}}/static/payment-handle/error.js" onload="renderApp()" defer></script>
  </head>
  <body>
      <div id="app-container"></div>
  </body>
</html>
