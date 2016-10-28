<?php

$factory(\RZP\Models\Merchant\Entity::class, [
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
    'transaction_report_email' => ['test@razorpay.com'],
    'receipt_email_enabled' => true,
    'settlement_schedule' => 3,
    'fee_bearer' => \RZP\Models\Merchant\FeeBearer::PLATFORM,
    'risk_rating' => 3,
]);

$factory(\RZP\Models\Terminal\Entity::class, [
    'id' => $faker->uniqueid,
    'merchant_id' => 'factory:Models\Merchant\Entity,',
    'card' => 1,
    'netbanking' => 0,
    'shared' => 1,
    'gateway' => 'hdfc',
    'gateway_merchant_id' => $faker->word,
    'gateway_terminal_id' => $faker->word,
    'gateway_terminal_password' => null,
    'gateway_access_code' => null,
    'gateway_secure_secret' => null,
]);

$factory(\RZP\Models\Merchant\Balance\Entity::class, [
    'id' => $faker->uniqueid,
    'balance' => 0,
]);

$factory(\RZP\Models\BankAccount\Entity::class, [
    'id' => $faker->uniqueid,
    'merchant_id' => '10000000000000',
    'entity_id'   => '10000000000000',
    'type' => 'merchant',
    'ifsc_code' => 'RZPB0000000',
    'account_number' => 10010101011,
    'beneficiary_name' => 'random_name',
    'beneficiary_address1' => 'address1',
    'beneficiary_address2' => 'address2',
    'beneficiary_address3' => 'address3',
    'beneficiary_address4' => 'address4',
    'beneficiary_city' => 'new delhi',
    'beneficiary_state' => 'DE',
    'beneficiary_country' => 'IN',
    'beneficiary_email' => $faker->email,
    'beneficiary_mobile' => 9988776655,
    'beneficiary_pin' => 100000,
]);

$factory(\RZP\Models\Card\Entity::class, [
    'id'                => $faker->uniqueid,
    'merchant_id'       => 10000000000000,
    'name'              => $faker->word,
    'network'           => 'Visa',
    'expiry_month'      => 01,
    'expiry_year'       => 2018,
    'type'              => 'debit',
    'country'           => 'IN',
    'last4'             => 1111,
    'iin'               => 411111,
    'length'            => '16',
    'issuer'            => 'hdfc',
    'international'     => false,
    'vault_token'       => 'NDExMTExMTExMTExMTExMQ==',
    'vault'             => 'tokenex',
    'trivia'            => '',
]);

$factory(\RZP\Models\Key\Entity::class, [
    'id' => '1DP5mmOlF5G5ag',
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'secret' => 'eyJpdiI6InFjMFFDMkszYzRLeU5UZ2VnajhoMEE9PSIsInZhbHVlIjoiZzY3c0Zkd0VMQkE0cjU1T3hVQXZSSzBub1h4aHJkaThBRlwvZWJwMm5wdkE9IiwibWFjIjoiZmEyZWM5MzIyODBjMmU3N2RhMmQ2ZjA2ODA3OTk5ZjI0ZTY2ZTQ3ZGNiYzJjOTE4ODc5ZWNkYzY4MGQwYTZhZiJ9',
    'expired_at' => null,
]);

$factory(\RZP\Models\Payment\Entity::class, [
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
    'contact' => $faker->randomElement(['+918199078685', '+17813924010', '+33751253819', '+919416544332', '+447706696711', '67332323', '+9613688111']),
    'notes' => null,
    'gateway' => 'hdfc',
    'email' => $faker->email,
    'auto_captured' => 0,
    'captured_at' => null,
    'transaction_id' => null,
    'created_at' => $faker->timestamp,
    'updated_at' => $faker->timestamp,
]);

$factory(\RZP\Models\Payment\Refund\Entity::class, [
    'id' => $faker->uniqueid,
    'payment_id' => 'factory:Models\Payment\Entity',
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => 100,
    'currency' => 'INR',
    'notes' => null,
    'transaction_id' => null,
]);

$factory(\RZP\Models\Pricing\Entity::class, [
    'id' => $faker->uniqueid,
    'plan_id' => '1ycviEdCgurrFI',
    'plan_name' => 'testFixturePlan',
    'feature' => 'payment',
    'payment_method' => 'card',
    'payment_method_type' => 'credit',
    'payment_network' => 'VISA',
    'payment_issuer' => 'ICIC',
    'percent_rate' => 1000,
    'fixed_rate' => 10000,
]);

$factory(\RZP\Models\Transaction\Entity::class, [
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
    'gratis' => false,
    'channel' => 'kotak'
]);

$factory(\RZP\Models\Settlement\Entity::class, [
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

$factory(\RZP\Models\Adjustment\Entity::class, [
    'id' => $faker->uniqueid,
    'merchant_id' => 'factory:Models\Merchant\Entity',
    'amount' => $faker->randomNumber,
    'currency' => 'INR',
    'channel' => 'kotak',
    'description' => $faker->string,
    'transaction_id' => 'factory:Models\Transaction\Entity',
]);

$factory(\RZP\Gateway\Hdfc\Entity::class, [
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

$factory(\RZP\Gateway\Atom\Entity::class, [
    'id' => $faker->randomNumber(6),
    'gateway_payment_id' => 'factory:Models\Payment\Entity',
    'token' => $faker->token,
    'success' => $faker->boolean,
    'callback_data' => null,
    'bank_name' => '\RZP',
    'bank_transaction_id' => $faker->randomNumber(6),
]);

$factory(\RZP\Models\Card\IIN\Entity::class, [
    'iin' => 411111,
    'category' => null,
    'network' => 'visa',
    'type' => 'credit',
    'country' => 'IN',
    'issuer' => 'SBI',
    'trivia' => $faker->sentence,
]);

$factory(\RZP\Models\Merchant\Methods\Entity::class, [
    'merchant_id' => '10000000000000',
    'card'  => '1',
    'banks' => '[]',
    'paytm' => '0',
    'netbanking' => '1',
]);

$factory(\RZP\Models\Merchant\Webhook\Entity::class, [
    'merchant_id' => '10000000000000',
    'url' => $faker->url,
    'events' => [
        'payment.authorized' => true,
    ],
    'active' => true,
]);

$factory(\RZP\Models\Address\Entity::class,
    [
        'line1'         => 'some line one',
        'line2'         => 'some line two',
        'city'          => 'Bangalore',
        'state'         => 'Karnataka',
        'zipcode'       => '560078',
        'country'       => 'in',
        'type'          => 'shipping_address',
        'primary'       => true,
        'entity_id'     => '100000customer',
        'entity_type'   => 'customer',
    ]);

$factory(\RZP\Models\Emi\Entity::class, [
    'id' => 10101010101010,
    'duration' => 9,
    'rate' => 1200,
    'bank' => 'HDFC',
    'methods' => 'card',
    'min_amount' => 500000,
]);

$factory(\RZP\Models\Order\Entity::class, [
    'id' => $faker->uniqueid,
    'merchant_id' => '10000000000000',
    'amount' => 1000000,
    'currency' => 'INR',
    'status' => 'created',
    'receipt' => $faker->uniqueid,
    'payment_capture' => false,
    'notes' => null,
    'attempts' => 0,
    'created_at' => $faker->timestamp,
    'updated_at' => $faker->timestamp,
]);

$factory(\RZP\Models\Customer\Entity::class, [
    'id' => $faker->uniqueid,
    'merchant_id' => '10000000000000',
    'name' => 'name',
    'contact' => '9988776655',
    'notes' => null,
]);

$factory(\RZP\Models\Customer\Token\Entity::class, [
    'id'          => $faker->uniqueid,
    'merchant_id' => '10000000000000',
    'customer_id' => '100000customer',
    'wallet'      => 'paytm',
    'method'      => 'wallet',
    'bank'        => null,
    'card_id'     => null,
    'recurring'   => false,
    'used_count'  => 0,
]);

$factory(\RZP\Models\Customer\AppToken\Entity::class, [
    'id' => $faker->uniqueid,
    'customer_id'  => '10000gcustomer',
    'device_token' => 'test',
    'merchant_id'  => '10000000000000'
]);

$factory(\RZP\Models\Merchant\Credits\Entity::class, [
    'id'            => $faker->uniqueid,
    'merchant_id'   => '10000000000000',
    'value'         => 150,
    'type'          => 'amount',
    'campaign'      => 'silent-ads',
]);

$factory(\RZP\Models\Batch\Entity::class, [
    'id'            => $faker->uniqueid,
    'merchant_id'   => '10000000000000',
    'status'        => 'created'
]);

$factory(\RZP\Gateway\Wallet\Base\Entity::class, [
    'id'            => '12345',
    'amount'        => 0,
    'contact'       => '9918899029',
    'email'         => 'a@b.com',
]);

$factory(\RZP\Models\Feature\Entity::class, [
    'id'                => $faker->randomNumber(1),
    'toggleable_type'   => \RZP\Models\Merchant\Entity::class
]);
