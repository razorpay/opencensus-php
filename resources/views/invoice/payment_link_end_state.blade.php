
<!DOCTYPE html>
<html>
    <head>
        <title>Invoice</title>

        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

        <link href="https://fonts.googleapis.com/css?family=Lato:300,400,600" rel="stylesheet" type="text/css" />
        <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />

        <link href="{{env('AWS_CF_CDN_URL')}}/static/payment-links/error.css" rel="stylesheet" type="text/css" />

        <script>
            function renderApp() {
                if(window.RZP && window.RZP.hasOwnProperty("renderApp")) {
                    var data = {!!utf8_json_encode($data)!!};

                    window.RZP.renderApp('app-container', data); 
                }
            }
        </script>

        <script src="{{env('AWS_CF_CDN_URL')}}/static/payment-links/error.js" onload="renderApp()" defer></script>
    </head>

    <body>
        <div id="app-container"></div>
    </body>
</html>
