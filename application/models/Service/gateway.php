<?php

namespace Service;

use \ERR;
use DomainObject\Transaction as TransactionDO;
use DataMapper\Transaction as TransactionDB;

class Gateway
{
	public function process(TransactionDO $txn_do)
	{
		return true;
	}
}