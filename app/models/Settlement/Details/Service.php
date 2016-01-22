<?php

namespace Models\Settlement\Details;

use Models\Base;
use Models\Settlement;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function getSettlementDetails($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $merchant = $this->merchant;

        $setlDetails = $this->repo->getSettlementDetails($id, $merchant);

        return $setlDetails->toArrayPublic();
    }

    public function postSettlementDetailsForOldTxns($input)
    {
        $data = (new Core)->postSettlementDetailsForOldTxns($input);
        
        return $data;
    }
}