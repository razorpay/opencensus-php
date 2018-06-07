<?php

use  Carbon\Carbon;
use  RZP\Gateway\Isg\ResponseField;

return [

	'createVirtualAccount' => [
		'url'     => '/virtual_accounts',
		'method'  => 'post',
		'content' => [
			'receiver_types' => 'qr_code'
		],
	],

	'testQrPaymentProcess' => [
		'url'     => '/payment/callback/bharatqr/isg',
		'method'  => 'post',
		'content' => [
			ResponseField::PRIMARY_ID                   => 'tobeFilled',
			ResponseField::SECONDARY_ID                 => 'reference_id',
			ResponseField::MERCHANT_PAN                 => '4287346823986423',
			ResponseField::TRANSACTION_ID               => 'abcde12345678910',
			ResponseField::TRANSACTION_DATE_TIME        =>  Carbon:: now()->format('Y-m-d H:i:s'),
			ResponseField::TRANSACTION_AMOUNT           => '100',
			ResponseField::AUTH_CODE                    => 'ab3456',
			ResponseField::RRN                          =>  random_int(111111111111,999999999999),
			ResponseField::CONSUMER_PAN                 => '4126989019190088',
			ResponseField::STATUS_CODE                  => '00',
			ResponseField::STATUS_DESC                  => 'Transaction Approved',
		],
	],
];
