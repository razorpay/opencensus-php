<!DOCTYPE html>
<html>
<head>
  <title>Payment in progress • {{ $data['data']['org_name'] }}</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
