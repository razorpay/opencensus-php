<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;


class Activated extends Base {

    protected $actionID;

    public function __construct(Merchant\Entity $merchant, $actionID)
    {
        $this->actionID = $actionID;
        parent::__construct($merchant);
    }

    public function getVerificationNotes()
    {
        return [
            Constant::WORKFLOW_ACTION_ID => $this->actionID
        ];
    }

    public function getVerificationStatus()
    {
        return Constant::ACTIVATED;
    }
}
