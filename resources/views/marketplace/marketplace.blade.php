<!DOCTYPE html>
<html lang=\"en\">
<head>
    <title><h1>Marketplace Redirect<h1></title>
    <style type="text/css">
        body {
            background-color: #080D29;
            padding:30px;
            margin:0;
        }

        p{
            color:#fff;
        }
    </style>
</head>
<body><p>please wait...</p></body>
<script type="application/javascript">
    window.addEventListener('load', function() {
        if(window.opener) {
            window.opener.postMessage({ data: { isMarketplaceAuthenticated: '{{$status}}' } }, '*');
        } else {
            window.location = "https://x.razorpay.com/instant-settlements";
        }
    })
</script>
</html>
