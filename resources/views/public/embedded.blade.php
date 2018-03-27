<!doctype html>
<html>
  <head>
    <title>Payment Page · Razorpay</title>
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <?php if ($_SERVER['HTTP_HOST'] !== "api.razorpay.com"): ?>
    <script>
    var Razorpay = {
      config: {
        api: '/'
      }
    };
    </script>
    <?php endif; ?>
    <script>
      var options = {!! $options !!};
    </script>
  </head>
  <body>
    @include('partials.loader')
    <script src="{{ $script }}"></script>
