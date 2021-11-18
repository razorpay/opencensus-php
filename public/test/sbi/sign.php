<html><body onload="document.forms[0].submit()">
<?php
require('../scripts/sanitizeParams.php');

$sign_url = 'https://checkout.stage.razorpay.in/demo/sign';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sign_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);

$params = [
  'key' => 'rzp_test_QVTINEMQnN6S5j',
  'amount' => $_POST['amount'],
  'currency' => 'INR',
  'name' => 'Test Merchant',
  'merchant_order_id' => '1124',
  'description' => 'Test Order',
  // 'prefill' => [
  //   'method' => 'netbanking',
  //   'bank' => 'SBIN'
  // ],
  // 'theme' => [
  //   'hide_topbar' => 'true'
  // ]
];

$query = http_build_query($params);

$query .= '&notes[test]=test&url[callback]=https://rzp.com&url[cancel]=https://rzp.com';



curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
$signature = curl_exec($ch);
curl_close($ch);

$params['signature'] = $signature;


?>
<form action="https://checkout.stage.razorpay.in/" method="post">
<?php
foreach ($params as $name => $value) {
?>
<input name="<?=$name?>" value="<?=$value?>" type="hidden">
<?php
}

?>
<input name="notes[test]=test" value="test" type="hidden">
<input name="url[callback]" value="https://rzp.com" type="hidden">
<input name="url[cancel]" value="https://rzp.com" type="hidden">
</form>
</body>
