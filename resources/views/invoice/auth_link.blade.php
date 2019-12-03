
<!DOCTYPE html>
<html lang="en">
<head>

    <title> {{$data['invoice']['merchant_label']}} </title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">

    <link rel="icon" href="https://razorpay.com/favicon.png" type="image/x-icon" />
</head>
<body>
</body>

<script type="text/javascript">
    const data = {!!utf8_json_encode($data)!!};
    var Razorpay = {
        config: {
            api: "{{env('APP_URL')}}/"
        }
    }

</script>
<script type="text/javascript" src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script type="text/javascript" src="{{env('AWS_CF_CDN_URL')}}/static/auth_link/bundle.js"></script>
<script type="text/javascript" src='https://cdn.razorpay.com/static/analytics/bundle.js' async></script>
</html>
