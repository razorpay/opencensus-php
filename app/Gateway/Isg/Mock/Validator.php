<?php

namespace RZP\Gateway\Isg\Mock;

use RZP\Base;
use RZP\Gateway\Isg\RequestField;


class Validator extends Base\Validator
{
	protected static $verifyRules = [
		RequestField::TRANSACTION_ID              => 'sometimes|alpha_num|size:16',
		RequestField::PRIMARY_ID                  => 'required|alpha_num',
		RequestField::TERMINAL_ID                 => 'required|numeric',
		RequestField::MERCHANT_PAN                => 'sometimes|numeric|size:16',
		RequestField::TRANSACTION_DATE            => 'required|string|date_format:Ymd',
		RequestField::TRANSACTION_AMOUNT          => 'required|string',
	];
}
