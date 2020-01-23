<!doctype html>
<html>
  <head>
    <title>Payment Page · Razorpay</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
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
      var meta = {!! $meta !!};
      var options = {!! $options !!};
      var urls = {!! $urls !!};
      options.key = "{!! $key !!}";
    </script>
  </head>
  <body>
    @include('partials.loader')
    <script src="{{ $script }}"></script>
