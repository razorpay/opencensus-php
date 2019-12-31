<html>

<link rel="stylesheet" type="text/css" href="https://x.razorpay.in/dist/payoutlinks.css">

<body>
    <div id="app"></div>
    <script>
        window.data={
            primary_color: '{{ $primary_color }}',
            logo: '{{ $merchant_logo_url }}',
            client: '{{ $merchant_name }}',
            amount: '{{ $amount }}',
            userDetails: {
                name: '{{ $user_name }}',
                maskedPhone: '{{ $user_phone }}',
                maskedEmail: '{{ $user_email }}',
            },
            description: '{{ $description }}',
            receipt: '{{ $receipt }}',
            apiHost: '{{ $api_host }}' + '/v1/',
            payoutLinkId: '{{ $payout_link_id }}',
        };

    </script>
    <script src="https://x.razorpay.in/dist/payoutlinks.js" defer></script>
</body>
</html>
