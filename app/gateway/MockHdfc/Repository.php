<?php

namespace Gateway\MockHdfc;

use EE\Exception;
use Gateway\Hdfc;
use Models\Base;

class Repository extends Base\Repository
{
    public function __construct()
    {
        $this->repo = __NAMESPACE__.'\MprGenerator';

        parent::__construct();
    }

    public function getUnreportedTransactions()
    {
        $repo = $this->repo;

        // return $repo::where('mpr_generated', '=', 0)
        //             ->orderBy('merchant_trackid')->get();

        return $repo::all();
    }
}
