<?php

require('vars.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = 'rzp_test_1DP5mmOlF5G5ag';

$public_url = $key_id.'@'.$baseurl;

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="Viewport" content="width=device-width, initial-scale=0.6" />
  <!-- <script src="http://192.168.0.105:8080/target/target-script-min.js"></script> -->
  <script src="<?= $checkout ?>/v1/razorpay.js"></script>
</head>
<body>
    <h1>Demo razorpay.js Payment Page</h1>
    <form id="container" onsubmit="return false" class="pure-form">
        <div class="section">
            <label>Email* </label><input id="customer_email" type="email" placeholder="Email" required value="pranav@razorpay.com">
            <label>Phone </label><input id="customer_phone" type="tel" placeholder="Phone" required value="8888899999">
            <label>How many? </label><select id="item_quantity" required onchange="updateAmount(this)">
                <option value="1" selected>1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
            <label>Amount</label><input disabled id="amount" value="₹ 200">
        </div>
        <div class="section">
            <label>Name on card</label><input required id="card_name" value="Pranav">
            <label>Card Number</label><input required id="card_number" value="4111111111111111">
            <label>Expiry Month</label><input required id="card_month" value="05">
            <label>Expiry Year</label><input required id="card_year" value="34">
            <label>CVV</label><input required id="card_cvv" value="300">
        </div>
        <div class="section">
            <label>Customer Id</label><input id="customer_id" value="">
            <label>Token</label><input id="token" value="">
        </div>

        <div class="section">
            <label>App Token</label><input id="app_token" value="">
            <label>Save</label><input type="input" id="save" value="">
        </div>
        <div style="clear: both"></div>
        <input id="submit" type="submit" value="Purchase" class="pure-button pure-button-primary">
    </form>
    <script>
        Razorpay.configure({
          key: 'rzp_test_1DP5mmOlF5G5ag',
          protocol: 'http',//'<?= $protocol ?>',
          hostname: '<?= $hostname ?>'
        });

        function updateAmount(el) {
            gel('amount').value = '₹ ' + parseInt(el.value)*200;
        }

        function gel(id) {
            return document.getElementById(id);
        }

        function val(id) {
            return gel(id).value;
        }

        gel('container').onsubmit = function(e){
            e.preventDefault();
            gel('submit').setAttribute('disabled', 'disabled'); // add loading animation here
            Razorpay.payment.authorize({
                data: {
                    amount: val('amount').replace(/[^0-9]+/,'')*100,
                    email: val('customer_email'),
                    contact: val('customer_phone'),
                    method: 'card',
                    'card[name]': val('card_name'),
                    'card[number]': val('card_number'),
                    'card[expiry_month]': val('card_month'),
                    'card[expiry_year]': val('card_year'),
                    'card[cvv]': val('card_cvv')
                },
                success: function(response){
                    alert(JSON.stringify(response));
                },
                error: function(response){
                    alert(JSON.stringify(response)); // focus invalid inputs using returned 'field';
                    gel('submit').removeAttribute('disabled'); // re-enable pay button
                }
            })
        }
    </script>
</body>
<style>
/*!
Pure v0.6.0
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
https://github.com/yahoo/pure/blob/master/LICENSE.md
*/
/*!
normalize.css v^3.0 | MIT License | git.io/normalize
Copyright (c) Nicolas Gallagher and Jonathan Neal
*/
body{
    font-family: sans-serif;
    background: #eee;
    color: #414141;
}
#container{
    background: #fff;
    position: relative;
    top: 20px;
    border: 2px solid #ccc;
    border-radius: 2px;
    padding: 30px 0;
    width: 600px;
    margin: 0 auto;
}
.section{
    float: left;
    width: 50%;
    padding: 0 30px;
    box-sizing: border-box;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
}
.section:first-child{
    border-right: 1px solid #eee;
    margin-right: -3px;
}
label{
    line-height: 28px;
}
input, select{
    display: block;
    width: 240px;
    margin: 5px 0;
}
input[type=submit]{
    width: 140px;
    margin: 30px auto 0;
    display: block;
}
h1{
    text-align: center;
}
