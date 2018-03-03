<!doctype html>
<html>
<?php

$baseurl = $_SERVER['HTTP_HOST'] . '/v1';

if (strpos($baseurl, 'charlie') !== false){
    $key = 'rzp_test_hFgDbHkE0UXImx';
    $secret = 'TBEdLrZwQo89aZ01XMTEvLXx';
} else{
    $key = $_GET['key'] ?? 'rzp_test_1DP5mmOlF5G5ag';
    $secret = 'thisissupersecret';
}

//define('CURRENT_URL', get_current_url());
define('PUBLIC_URL', $baseurl);
define('PRIVATE_URL', $key . ':' . $secret . '@' . $baseurl);
define('CALLBACK_URL', 'http://' . $baseurl . '/return/callback?key_id=' . $key);

$messages = [
    'success' => [],
    'failure' => [],
];

include_once './core_functions.php';

$payments = get_last_payments(['count' => '10'], $messages);
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Razorpay - Testing page</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <?php if (count($messages['failure']) > 0): ?>
        <div class="alert alert-danger mt-3"><?= implode("<br>", $messages['failure'])?></div>
    <?php elseif(count($messages['success']) > 0): ?>
        <div class="alert alert-success mt-3"><?= implode("<br>", $messages['success'])?></div>
    <?php endif; ?>
    <div class="mt-3">
        <h4 class="alert alert-primary text-center">
            Wallet | Amazon Pay <a href="?refreshed" class="btn btn-sm btn-outline-primary float-right">Refesh</a>
        </h4>
        <form id="payment_form" class="form  mt-5" class="w-50" method="post" action="//<?= PUBLIC_URL ?>/payments" >
            <div class="form-group">
                <input type="hidden" value="<?= $key ?>" name="key_id">
                <input type="hidden" value="wallet" name="method">
                <input type="hidden" value="amazonpay" name="wallet">
                <input type="hidden" name="email" size="25" value="amazonpay@razorpay.com">
                <input type="hidden" name="contact" size="25" value="9810099100">
                <input type="hidden" name="currency" size="25" value="INR">
            </div>
            <h5>New Payment</h5>
            <hr>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="">Amount <small> (In Paisa)</small> </span>
                </div>
                <input class="form-control" type="text" name="amount" size="25" value="100">
                <div class="input-group-append">
                    <button class="btn btn-success" type="submit">Pay Now!</button>
                </div>
            </div>
        </form>

        <div class="mt-5">
            <h5>Payments</h5>
            <hr>
            <div class="" style="font-family: monospace">
                <?php foreach ($payments as $payment): ?>
                    <div class="w-100 alert alert-info">
                        <form method="post" class="row mt-2">
                            <div class="col-md-2"><?=$payment->id ?></div>
                            <div class="col-md-1 text-center"><?=$payment->amount?></div>
                            <div class="col-md-2 text-center"><?=$payment->status?></div>
                            <div class="col-md-1 text-center"><?=time_elapsed_string('@'.$payment->created_at)?></div>
                            <div class="col-md-3">
                                <input class="form-control" name="amount"
                                       value="<?=$payment->amount - $payment->amount_refunded ?>">
                            </div>
                            <div class="col-md-3 text-right">
                                <input type="hidden" name="payment_id" value="<?=$payment->id ?>">
                                <?php if($payment->status === 'authorized'): ?>
                                    <input type="hidden" name="action" value="capture">
                                    <button class="btn btn-success w-100" type="submit">Capture</button>
                                <?php elseif($payment->status === 'captured'): ?>
                                    <input type="hidden" name="action" value="refund">
                                    <button class="btn btn-warning w-100" type="submit">Refund</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var myForm = document.getElementById('payment_form');
    myForm.onsubmit = function() {
        var win = window.open('about:blank','Popup_Window',
            'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,' +
            'resizable=1,width=960,height=600,left = 100,top = 100');
        var timer = setInterval(function() {
            if(win.closed) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 500);
        this.target = 'Popup_Window';
    };
</script>
</body>
</html>
