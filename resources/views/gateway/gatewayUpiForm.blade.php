<!DOCTYPE html>
<html>
<head>
  <title>Payment in progress • Razorpay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
</head>
<body>
@include('partials.loader')
</body>
<script>
// Async Payment data //
var key_id = "{{$data['key']}}";
var data = {!!utf8_json_encode($data['data'])!!};
// Async Payment data //
</script>
<script src='{{$data['cdn']}}/static/pay/upi.js'></script>
