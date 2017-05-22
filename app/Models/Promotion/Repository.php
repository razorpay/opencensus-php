<?php

namespace RZP\Models\Promotion;

use DB;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
	protected $entity = 'promotion';
}
