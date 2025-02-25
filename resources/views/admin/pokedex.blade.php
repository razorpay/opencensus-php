<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay - Admin Panel</title>
  <style>
    .sticky-content {
      top: 0 !important;
      width: 100% !important;
    }

    .sticky-content .pull-right,
    a[target=_blank] {
      display: none;
    }
  </style>
  <link rel="preload" href="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/shell/shell.remoteEntry.js" as="script">
  <link rel="preload"
    href="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/payments-dashboard/payments_dashboard.remoteEntry.js"
    as="script">
  <link rel="preload"
    href="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/pokedex-dashboard/pokedex_dashboard.remoteEntry.js"
    as="script">
  <script>
    window.rzpAnalytics = function () { }
    var merchantId = location.pathname.match(/[^\/]+(?=\/*$)/)[0];
    window.rzp_user = {
      "current": merchantId,
      "id": merchantId,
      "tags": []
    }
  </script>
  @include('partials/environment')
</head>

<body>
  <div id="react-root" class="react-root"></div>
  <!-- Blank interface init before loading the project entry file -->
  @include('partials/rzpq-interface')
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js" crossorigin="anonymous"
    integrity="sha512-oJp0DdQuQQrRsKVly+Ww6fAN1GwJN7d1bi8UubpEbzDUh84WrJ2CFPBnT4LqBCcfqTcHR5OGXFFhaPe3g1/bzQ=="></script>
  <script src="{{$cdnDashboardAssetsUrl}}/dashboard/core-bundles/pokedex-dashboard/pokedex-dashboard.entry.js"></script>
  @include('partials/blade-coverage-script')
