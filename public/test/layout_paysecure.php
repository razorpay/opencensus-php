<!doctype html>
<html>
<?php
require('../scripts/sanitizeParams.php');

$key_id = $_GET['key'] ?? 'rzp_test_1DP5mmOlF5G5ag';
?>
    <style>
        div#container {
            padding: 20px;
            /*background-color: red;*/
        }

        h1 {
            text-align: center;
        }

        div#content {
            width: 60%;
            margin: 0px auto;
        }

        input#amount, button#rzp-button1 {
            font-size:24px;
            border-radius: 10px;
            padding: 5px 15px;
        }

        table#table1 td{
            padding: 5px;
            text-align: center;
        }
    </style>
    <body>
        <div id="container">
            <h1>Razorpay - PaySecure Integration testing page</h1>
            <div id="content">
                <table id="table1">
                    <tr>
                        <td>Amount</td>
                        <td><input type="text" id="amount" value="100"/></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button id="rzp-button1">Pay</button></td>
                    </tr>
                </table>


            </div>
        </div>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
        var options = {
            "key": "<?= $key_id ?>",
            "amount": "100",
            "name": "Merchant Name",
            "description": "Test payment",
            "handler": function (response){
                alert(response.razorpay_payment_id);
            },
            "prefill": {
                name: 'Test User',
                email: 'test_user@razorpay.com',
                contact: '+919888888889',
                'card[number]': '6074849900004936',
                'card[expiry]': '1123',
                'card[cvv]': '123'
            },
            "notes": {
                "address": "Hello World"
            },
            "theme": {
                "color": "#F37254"
            }
        };

        document.getElementById('rzp-button1').onclick = function(e){
            amount = document.getElementById('amount').value;
            options['amount'] = amount;

            var rzp1 = new Razorpay(options);
            rzp1.open();
            e.preventDefault();
        }
        </script>
    </body>
</html>