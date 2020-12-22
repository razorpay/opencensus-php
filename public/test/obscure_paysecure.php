<!doctype html>
<html>
<?php
require('../scripts/sanitizeParams.php');

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

$key_id = $_GET['key'] ?? 'rzp_test_1DP5mmOlF5G5ag';
$secret = 'thisissupersecret';

$testCase = $_GET['test'] ?? 'AQPG_01';
$card = $_GET['card'] ?? '6074819900004939';

$public_url = $baseurl;
$private_url = $key_id.':'.$secret.'@'.$baseurl;
$callback_url = 'http://'.$baseurl.'/return/callback?key_id='.$key_id;
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Razorpay - Testing page</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
        }
        form {
            margin: 20px auto;
            max-width: 700px;
        }
        input[type=submit] {
            color: #414141;
            border: 1px solid #ccc;
            background-color: #E6E6E6;
            text-decoration: none;
            border-radius: 2px;
            padding: 10px 20px;
            text-transform: uppercase;
            margin: 10px 0;
        }
        input[type=text], select {
            width: 100%;
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            outline: none;
            border: 1px solid #ccc;
            border-radius: 2px;
            background: none;
            line-height: 16px;
            padding: 6px 12px;
            background: #fff;
        }
        input[type=checkbox] {
            width: 20px;
            height: 20px;
            margin: 0;
            vertical-align: middle;
            margin-right: 4px;
        }
        table {
            line-height: 36px;
            font-size: 14px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            background: #fafafa;
            padding: 10px 20px;
            /*white-space: nowrap;*/
        }

        .cardtype::before {
            content: '';
            background: no-repeat url('data:image/png;base64,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');
            height: 11px;
            width: 35px;
            -webkit-background-size: 600% 600%;
            background-size: 600%;
            margin: 5px 0 0 -2px;
            background-position: -20%;
            display: block;
        }

        [cardtype=visa]::before, .networkicon.visa {
            background-position: 0;
        }

        [cardtype=amex]::before, .networkicon.amex {
            background-position: 100%;
        }

        [cardtype=diners]::before, .networkicon.diners {
            background-position: 60%;
        }

        [cardtype=maestro]::before, .networkicon.maestro {
            background-position: 40%;
        }

        [cardtype=mastercard]::before, .networkicon.mastercard {
            background-position: 20%;
        }

        [cardtype=rupay]::before, .networkicon.rupay {
            background-position: 80%;
        }

        div#container {
            margin: auto;
            width: 50%;
            border: 3px solid green;
            padding: 10px;
        }

        #container table {
            margin: auto;
            width: 80%;
            padding: 10px;
        }

        #container thead td {
            margin: 0px auto;
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }

        .footer {
            margin: 0px auto;
            text-align: center;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/razorpay.js"></script>
    <script>
        window.onload = function(e) {
            var getEl = document.getElementById.bind(document);

            if (window.performance && window.performance.navigation.type == window.performance.navigation.TYPE_BACK_FORWARD) {
                body = document.getElementsByTagName('body')[0];
                body.innerHTML = `<div id="container">
            <table>
                <thead>
                    <td colspan="2">TRANSACTION DETAILS</td>
                </thead>
                <tr>
                    <td>RRN</td>
                    <td>Not generated</td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td id="date"></td>
                </tr>
                <tr>
                    <td>Transaction Status</td>
                    <td>
                                                    Failed
                                            </td>
                </tr>
            </table>
        </div>
        <br/>
        <div class="footer"><b>You may safely close this tab.</b></div>`;
                var today = new Date().toISOString().slice(0, 10);
                var date = getEl('date');
                date.innerHTML = today;
            }


            window.formatter = Razorpay.setFormatter(getEl('paymentform'));
            var cvvField = getEl('card_cvv');

            formatter.add('card', getEl('card_number'))
              .on('network', function(o) {

                var type = this.type;
                typeEle = getEl('cardtype');
                typeEle.setAttribute("cardtype", type);

                // set length of cvv element based on amex card
                var cvvlen = type === 'amex' ? 4 : 3;
                cvvField.maxLength = cvvlen;
                cvvField.pattern = '^[0-9]{' + cvvlen + '}$';

                getEl('card_type').innerHTML = type;
              })
              .on('change', function() {
                var isValid = this.isValid();
                getEl('card_valid').innerHTML = isValid ? 'valid' : 'invalid';

                // automatically focus next field if card number is valid and filled
                if (isValid && this.el.value.length === this.caretPosition) {
                  getEl('card_expiry').focus();
                }
              })
        };
    </script>
</head>
<body>
<script type="application/javascript">
    function disableEmptyInputs(form) {
        var controls = form.elements;
        for (var i=0, iLen=controls.length; i<iLen; i++) {
            controls[i].disabled = controls[i].value == '';
        }
    }
</script>
<form method="post" id="paymentform" action="//<?=$public_url?>/payments" onsubmit="disableEmptyInputs(this)">
    <div style="background: brown; color: #fff; text-align: center; padding: 8px 0">Enter Parameters</div>
    <table>
        <tr>
            <input type="hidden" value="card" name="method">

        </tr>
        <tr>
            <td colspan="40">Sponsor bank:</td>
            <td style="display: flex;">
                <div>
                    <strong>RBL Bank&nbsp;&nbsp;&nbsp;&nbsp;</strong>
                </div>
                <div>
                    <img style="width: 80px; height: auto;" src="/test/images/rbl_logo.png"/>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="40">Razorpay Key:</td>
            <td colspan="2">
                <input type="text" value="<?=$key_id?>" name="key_id">
            </td>
        </tr>
        <tr>
            <td colspan='40'>Card Holder Name:</td>
            <td><input type="text" name="card[name]" size="25" value="User Name"></td>
            <!-- <td><input type="text" name="callback_url" value="<?= $callback_url ?>"></td> -->
            <td><input type="hidden" value="INR" name="currency"></td>
        </tr>
        <tr>
            <td colspan="40">Card No:</td>
            <td colspan="2">
                <input id="card_number" type="text" name="card[number]" value="<?=$card?>>" size="25">
                <div class="cardtype" id="cardtype" cardtype=""></div>
            </td>
        </tr>
        <tr>
            <td colspan="40">CVV:</td>
            <td><input size="3" id="card_cvv" type="password" name="card[cvv]" value="123" maxlength=4></td>
        </tr>
        <tr>
            <td colspan ='40'>Exp Date:</td>
            <td><input type="text" name="card[expiry_month]" value="11"></td>
            <td><input type="text" name="card[expiry_year]" value="2020"></td>
        </tr>
        <tr>
            <td colspan='40'>Amount:</td>
            <td><input type="text" name="amount" size="25" value="100"></td>
            <td>
                <select name="currency">
                    <option value="INR">INR</option>
                    <option value="USD">US Dollar</option>
                    <option value="EUR">Euro</option>
                    <option value="SGD">Singapore Dollar</option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan='40'>Email:</td>
            <td><input type="text" name="email" size="25" value="test@razorpay.com"></td>
            <td><input type="text" name="contact" size="25" value="9976543210"></td>
        </tr>
        <tr>
            <td colspan='40'>Test Name:</td>
            <td><input type="text" name="notes[test_name]" size="25" value="<?= $testCase ?>"></td>
        </tr>
        <tr>
            <td colspan="100" align="center">
                <input type="submit" value="  Submit  " >
            </td>
        </tr>
    </table>
    <div style="background: brown; color: #fff; text-align: center; height: 20px"></div>
</form>
<br><br>
</body>
</html>
