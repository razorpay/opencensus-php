<?php

namespace RZP\Gateway\Isg;

use RZP\Gateway\Base;
use RZP\Models\Terminal\Entity as Terminal;

class Repository extends Base\Repository
{
	protected $entity = 'isg';

	public function findTeminalByGatewayMpan(string $mpan, string $gateway)
	{
		$this->entity = 'terminal';

		return $this->newQuery()
			->where(Terminal::GATEWAY, '=', $gateway)
			->where(function ($query) use ($mpan)
			{
				$query->where(Terminal::VISA_MPAN, '=', $mpan)
					->orWhere(Terminal::MC_MPAN, '=', $mpan)
					->orWhere(Terminal::RUPAY_MPAN, '=', $mpan);
			})
			->first();
	}
}
