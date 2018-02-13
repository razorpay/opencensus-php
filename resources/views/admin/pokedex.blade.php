<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="google" value="notranslate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .sticky-content {
      top: 0 !important;
      width: 100% !important;
    }
    .sticky-content .pull-right, a[target=_blank] {
      display: none;
    }
    .dashboard > .row:nth-child(n+3) {
      display: none;
    }
  </style>
  <script>
    window.rzpAnalytics = function(){}
    var merchantId = location.pathname.match(/[^\/]+(?=\/*$)/)[0];
    window.rzp_user = {
      "current": merchantId,
      "id": merchantId,
      "tags": []
    }
  </script>
</head>
<body>
<div id="react-root" class="react-root"></div>
<script src="{{$cdn}}/dist/pokedex-entry.js"></script>
