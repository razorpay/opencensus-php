<?php

namespace RZP\Models\FundTransfer\NodalBase;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;


class NodalBase extends Base\Core
{
    protected $mode = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function getTransferMode($amount)
    {
        return Carbon::now(Timezone::IST)->hour < 16 ? (($amount >= 200000) ? 'R' : 'N') : 'N';
    }

}
