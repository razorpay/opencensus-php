<?php

namespace RZP\Gateway\Isg;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
	const MERCHANT_REFERENCE                    = 'merchant_reference';
	const SECONDARY_ID                          = 'secondary_id';
	const MERCHANT_PAN                          = 'merchant_pan';
	const TRANSACTION_ID                        = 'transaction_id';
	const TRANSACTION_DATE_TIME                 = 'transaction_date_time';
	const TRANSACTION_AMOUNT                    = 'transaction_amount';
	const AUTH_CODE                             = 'auth_code';
	const RRN                                   = 'reference_no';
	const TIP_AMOUNT                            = 'tip_amount';
	const CONSUMER_PAN                          = 'consumer_pan';
	const STATUS_CODE                           = 'status_code';
	const STATUS_DESC                           = 'status_desc';
	const TRANSACTION_DATE                      = 'transaction_date';
	const NOTIFICATION_REF_NO                   = 'notification_ref_no';

	protected $entity = 'isg';

	protected $fillable = [
		self::MERCHANT_REFERENCE,
		self::SECONDARY_ID,
		self::MERCHANT_PAN,
		self::TRANSACTION_ID,
		self::TRANSACTION_DATE_TIME,
		self::TRANSACTION_AMOUNT,
		self::AUTH_CODE,
		self::RRN,
		self::TIP_AMOUNT,
		self::CONSUMER_PAN,
		self::STATUS_DESC,
		self::STATUS_CODE,
		self::TRANSACTION_DATE,
	];

	public function setAmount($amount)
	{
		$this->setAttribute(self::TRANSACTION_AMOUNT, $amount);
	}
}
