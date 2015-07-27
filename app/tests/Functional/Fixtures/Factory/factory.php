<?php

$factory('Models\Merchant\Entity', [
    'id' => $faker->uniqueid,
    'name' => $faker->word,
    'email' => $faker->email,
    'activated' => 0,
    'live' => 0,
    'pricing_plan_id' => null,
    'international' => 0,
    'website' => $faker->url,
    'billing_label' => $faker->word,
    'category' => 1100,
    'transaction_report_email' => $faker->email,
    'receipt_email_enabled' => true,
]);

$factory('Models\Terminal\Entity', [
    'id' => $faker->uniqueid,
    'merchant_id' => 'factory:Models\Merchant\Entity,',
    'card' => 1,
    'gateway' => 'hdfc',
    'gateway_merchant_id' => $faker->word,
    'gateway_terminal_id' => $faker->word,
    'gateway_terminal_password' => null,
    'gateway_access_code' => null,
    'gateway_secure_secret' => null,
]);

$factory('Models\Merchant\Balance', [
    'id' => $faker->uniqueid,
    'balance' => 0,
]);

$factory('Models\Merchant\BankAccount\Entity', [
    'merchant_id' => $faker->uniqueid,
    'ifsc_code' => 'RZPB0000000',
    'account_number' => 10010101011,
    'beneficiary_name' => 'random_name',
    'beneficiary_code' => 'RAND',
    'beneficiary_address1' => 'address1',
    'beneficiary_address2' => 'address2',
    'beneficiary_address3' => 'address3',
    'beneficiary_address4' => 'address4',
    'beneficiary_city' => 'new delhi',
    'beneficiary_state' => 'DE',
    'beneficiary_country' => 'IN',
    'beneficiary_email' => $faker->email,
    'beneficiary_mobile' => 1234567890,
    'beneficiary_pin' => 100000,
]);

$factory('Models\Card\Entity', [
    'id' => $faker->uniqueid,
    'merchant_id' => 10000000000000,
    'name' => $faker->word,
    'network' => 'Visa',
    'expiry_month' => 01,
    'expiry_year' => 2017,
    'type' => 'debit',
    'country' => 'IN',
    'last4' => 6666,
    'iin' => 401200,
    'length' => 16,
]);

$factory('Models\Key\Entity', [
    'id' => '1DP5mmOlF5G5ag',
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'secret' => 'eyJpdiI6InFjMFFDMkszYzRLeU5UZ2VnajhoMEE9PSIsInZhbHVlIjoiZzY3c0Zkd0VMQkE0cjU1T3hVQXZSSzBub1h4aHJkaThBRlwvZWJwMm5wdkE9IiwibWFjIjoiZmEyZWM5MzIyODBjMmU3N2RhMmQ2ZjA2ODA3OTk5ZjI0ZTY2ZTQ3ZGNiYzJjOTE4ODc5ZWNkYzY4MGQwYTZhZiJ9',
]);

$factory('Models\Payment\Entity', [
    'id' => $faker->uniqueid,
    'merchant_id' => 10000000000000,
    'method' => 'card',
    'card_id' => null,
    'bank' => null,
    'amount' => 1000000,
    'amount_authorized' => 1000000,
    'amount_refunded' => 0,
    'currency' => 'INR',
    'status' => 'created',
    'refund_status' => null,
    'contact' => $faker->randomNumber(6),
    'notes' => $faker->emptyarray,
    'gateway' => 'hdfc',
    'email' => $faker->email,
    'auto_captured' => 0,
    'captured_at' => null,
    'transaction_id' => null,
    'created_at' => $faker->timestamp,
    'updated_at' => $faker->timestamp,
]);

$factory('Models\Payment\Refund\Entity', [
    'id' => $faker->uniqueid,
    'payment_id' => 'factory:Models\Payment\Entity',
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => 100,
    'currency' => 'INR',
    'transaction_id' => null,
]);

$factory('Models\Pricing\Entity', [
    'id' => $faker->uniqueid,
    'plan_id' => '1ycviEdCgurrFI',
    'plan_name' => 'testFixturePlan',
    'payment_method' => 'card',
    'payment_method_type' => 'credit',
    'payment_network' => 'VISA',
    'payment_issuer' => 'ICIC',
    'percent_rate' => 1000,
    'fixed_rate' => 10000,
]);

$factory('Models\Transaction\Entity', [
    'id' => $faker->uniqueid,
    'entity_id' => $faker->uniqueid,
    'type' => 'payment',
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => $faker->randomNumber,
    'fee' => $faker->randomNumber,
    'pricing_rule_id' => null,
    'currency' => 'INR',
    'credit' => $faker->randomNumber,
    'debit' => 0,
    'balance' => $faker->randomNumber,
    'gateway_fee' => null,
    'channel' => 'kotak'
]);

$factory('Models\Settlement\Entity', [
    'id' => $faker->uniqueid,
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => $faker->randomNumber,
    'status' => 'created',
    'transaction_id' => 'factory:Models\Transaction\Entity',
    'channel' => 'kotak',
    'utr' => $faker->randomNumber(8),
    'failure_reason' => null,
    'return_utr' => null,
]);

$factory('Models\Adjustment\Entity', [
    'id' => $faker->uniqueid,
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => $faker->randomNumber,
    'currency' => 'INR',
    'channel' => 'kotak',
    'description' => $faker->string,
    'transaction_id' => 'factory:Models\Transaction\Entity',
]);

$factory('Gateway\Hdfc\Entity', [
    'id' => $faker->randomNumber(6),
    'payment_id' => null,
    'refund_id' => null,
    'gateway_transaction_id' => $faker->hdfcPaymentId,
    'action' => 4,
    'amount' => $faker->randomNumber(2),
    'enroll_result' => 2,
    'status' => 'authorized',
    'result' => 'APPROVED',
    'eci' => 6,
    'auth' => 999999,
    'ref' => $faker->hdfcRef,
    'avr' => 'N',
    'postdate' => $faker->hdfcPostDate,
]);

$factory('Gateway\Atom\Entity', [
    'id' => $faker->randomNumber(6),
    'gateway_payment_id' => 'factory:Models\Payment\Entity',
    'token' => $faker->token,
    'success' => $faker->boolean,
    'callback_data' => null,
    'bank_name' => 'RZP',
    'bank_transaction_id' => $faker->randomNumber(6),
]);

$factory('Models\Card\Detail', [
    'iin' => 411111,
    'category' => null,
    'network' => 'visa',
    'type' => 'credit',
    'country' => 'IN',
    'issuer' => 'SBI',
    'trivia' => $faker->sentence,
]);

$factory('Models\Merchant\Banks\Entity', [
    'merchant_id' => 10000000000000,
    'card'  => '1',
    'banks' => '[]',
    'paytm' => '0',
]);
