<?php

use  Carbon\Carbon;
use  RZP\Gateway\Isg\Field;

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
			Field::PRIMARY_ID                   => 'tobeFilled',
			Field::SECONDARY_ID                 => 'reference_id',
			Field::MERCHANT_PAN                 => '4403844012084006',
			Field::TRANSACTION_ID               => '1817700802564',
			Field::TRANSACTION_DATE_TIME        =>  Carbon:: now()->format('Y-m-d H:i:s'),
			Field::TRANSACTION_AMOUNT           => '100.00',
			Field::AUTH_CODE                    => 'ab3456',
			Field::RRN                          =>  random_int(111111111111,999999999999),
			Field::CONSUMER_PAN                 => 'F85DAA8B2DB1EFBEC19D1C908EAEA217CC233DBC6EBA091CBE0012671BB60010',
			Field::STATUS_CODE                  => '00',
			Field::STATUS_DESC                  => 'Transaction Approved',
		],
	],
];
