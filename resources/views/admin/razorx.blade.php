<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="Razorpay">
  <link rel="shortcut icon" href="/img/favicon.png">
  <title>Razorpay - RazorX Dashboard</title>
  <meta name="description" content="RazorX Dashboard for Ramp-up release of features and products" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <script>
    var org = {!! json_encode($org) !!};
    var user = {!! json_encode($user) !!};
  </script>
  @include('partials/environment')
</head>
<body>
<div id="react-root" class="react-root"></div>
<script src="{{$cdn}}/dist/razorx-entry.js"></script>
