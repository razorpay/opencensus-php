<!doctype html>
<html>
<?php
require('../scripts/sanitizeParams.php');

$key_id = 'rzp_test_1DP5mmOlF5G5ag';
?>
<head>
  <meta name="viewport" content="width=device-width">
  <title>Razorpay - Testing page</title>
  <style>
    html {
      background: #eee;
    }
    body {
      margin: 20px auto;
      background: #fff;
      color: #414141;
      padding: 30px 30px 20px;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
      max-width: 300px;
    }
    label {
      display: block;
      font-weight: bold;
      font-size: 12px;
      text-transform: uppercase;
      color: #666;
    }
    input {
      width: 100%;
      box-sizing: border-box;
      -webkit-box-sizing: border-box;
      outline: none;
      box-shadow: inset 0 1px 3px #ddd;
      border: 1px solid #ddd;
      border-radius: 4px;
      background: none;
      line-height: 16px;
      padding: 12px 16px;
      background: #fff;
      margin: 4px 0 20px;
    }
    input:focus {
      border-color: #3498db;
      box-shadow: none;
    }
    button {
      float: right;
      display: block;
      color: #fff;
      border: 1px solid #27ae60;
      background-color: #2ecc71;
      text-decoration: none;
      border-radius: 2px;
      padding: 10px 20px;
      text-transform: uppercase;
    }
    form {
      overflow: auto;
      zoom: 1;
    }
  </style>
</head>
<body>
  <form method="post" id="paymentform" action="/v1/checkout/hosted">
    <label>Key</label>
    <input name="checkout[key]" value="<?= $key_id ?>">
    <label>Amount</label>
    <input name="checkout[amount]" value="300">
    <label>Callback URL</label>
    <input name="url[callback]" value="/">
    <label>Cancel URL</label>
    <input name="url[cancel]" value="">

    <button type="submit">submit</button>
  </form>
</body>
