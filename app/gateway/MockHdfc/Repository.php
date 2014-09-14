<?php

namespace Gateway\MockHdfc;

use EE\Exception;
use Gateway\Hdfc;
use Models\Base;

class Repository extends Base\Repository
{
    protected $primaryKey = 'merchant_trackid';

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

        return $repo::where('mpr_generated', '=', 0)->get();
    }

    public function setMprGeneratedTrue($trackids)
    {
        $repo = $this->repo;

        $repo::whereIn('mpr_generated', $trackids)->update(array('mpr_generated' => 1));
    }
}
