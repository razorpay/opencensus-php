<?php

namespace RZP\Gateway\Isg;

use RZP\Gateway\Base;
use RZP\Models\Terminal\Repository as Terminal;

class Repository extends Base\Repository
{
	protected $entity = 'isg';

	public function findTeminalByGatewayMpan(string $mpan, string $gateway)
	{
		return (new Terminal())->findByGatewayMpan($mpan, $gateway);
	}
}
