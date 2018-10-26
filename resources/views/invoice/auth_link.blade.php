
<!DOCTYPE html>
<html lang="en">
<head>

    <title> {{$data['invoice']['merchant_label']}} </title>

    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
</head>
<body>
</body>

<script type="text/javascript">
    const data = {!!utf8_json_encode($data)!!};
</script>
<script type="text/javascript" src="{{env('CHECKOUT_URL')}}/v1/checkout.js"></script>
<script type="text/javascript" src="{{env('AWS_CF_CDN_URL')}}/static/auth_link/bundle.js"></script>
</html>