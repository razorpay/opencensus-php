<?php
error_reporting(E_ALL);
ini_set('display_errors','1');

$checkout = 'https://checkout.razorpay.com';
$protocol = 'https';
$hostname = 'api.razorpay.com';

if (file_exists('config.php'))
{
    require('config.php');
}

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
    <link href="<?= $checkout ?>/v1/css/style.css" type="text/css" rel="stylesheet">

    <script src="<?= $checkout ?>/v1/checkout.js" ></script>
</head>
<body>
    <button class="btn" id="rzp-button1">Tron Legacy</button>
    <button class="btn" id="rzp-button2">Alive (2007)</button>

    <script>
        var options = {
            'key': 'rzp_test_1DP5mmOlF5G5ag',
            'amount': '5100',
            'name': 'Daft Punk',
            'description': 'Tron Legacy',
            'image': 'https://i.imgur.com/3g7nmJC.png',
            'handler': function (transaction) {
                alert("You have successfully purchased " + rzp1.options.description);
            },
            'protocol': '<?= $protocol ?>',
            'hostname': '<?= $hostname ?>',
            'prefill': {
                'name': 'Harshil Mathur',
                'email': 'harshil@razorpay.com',
                'contact': '9999999999'
            },
            udf: {
                'address': 'Hello World'
            },
            netbanking: true
        }
        var rzp1 = new Razorpay(options);

        $('#rzp-button1').click(function(e) {
            rzp1.open();
            e.preventDefault();
        });

        $.extend(true, options, {
            amount : 5200,
            description : 'Alive (2007)',
            image : 'https://i.imgur.com/GXalrU0.png',
            handler : function (transaction){
                alert("You have successfully purchased "+rzp2.options.description);
            },
            udf: {
                'shipping': "Bye World"
            }
        });

        var rzp2 = new Razorpay(options);

        $('#rzp-button2').click(function(e){
            rzp2.open();
            e.preventDefault();
        })
    </script>

    <style>

    html {
        background-image: url('https://i.imgur.com/zx7XGPJ.jpg');
        background-size: cover cover;
        /**background-position: center center fixed;*/
        background-repeat: no-repeat;
        background-position: -300px 0px;
    }
    .btn {
        width: 20px;
        background-color: #28B3D2;
        color: white;
        width: 200px;
        border-radius: 3px;
        height: 40px;
        font-size: 20px;
        text-align: center;
        border: none;
        position: absolute;
        left: 45%;
    }
    #rzp-button2 {
        top: 400px;
    }
    #rzp-button1 {
        top: 350px;
    }
    </style>
</body>
</html>