<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
    <meta charset="utf-8">
    <meta name="google" value="notranslate" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Razorpay">
    <link rel="shortcut icon" href="https://dashboard.razorpay.com/img/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
    <title>Terms and Condition</title>
    <meta name="robots" content="noindex">
    <meta name="robots" content="nofollow" />
    @include('partials/environment')
    <script type="text/javascript">
        window.api_host = "{!! $api_host !!}"
        window.org = {!! $org !!};
    </script>
    @include('partials/common')
		<script src="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/tnc-dashboard/tnc-dashboard.entry.js"></script>
</body>
</html>
