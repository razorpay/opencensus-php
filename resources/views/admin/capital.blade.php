<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Razorpay - Capital Onboarding Dashboard</title>
    <meta charset="utf-8">
    <meta name="google" value="notranslate" />
    <link rel="shortcut icon" href="/img/favicon.png">
    <meta name="description" content="Capital onboarding Revamp" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="preconnect" href="https://cdn.razorpay.com">
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Lato&display=swap');

      * {
        box-sizing: border-box;
      }

      body {
        font-family: Lato, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu,
          Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        font-size: 14px;
        margin: 0;
        background: #000;
      }
    </style>
    <script>
      var org = {
        !!json_encode($org) !!
      };
      var user = {
        !!json_encode($user) !!
      };
    </script> @include('partials/environment')
  </head>
  <body>
    <div id="react-root"></div>
    <script src="{{$cdn}}/{{$build_sub_path}}admin-los-revamp/main.js"></script>
  </body>
</html>
