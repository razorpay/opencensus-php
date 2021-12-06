<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class Rejected extends Base {

    protected $rejectionReasons;
    protected $actionId;

    public function __construct(Merchant\Entity $merchant, $actionId, $rejectionReasons)
    {
        $this->rejectionReasons = $rejectionReasons;
        $this->actionId = $actionId;
        parent::__construct($merchant);
    }

    public function getVerificationNotes()
    {
       return [
           Constant::WORKFLOW_ACTION_ID => $this->actionId,
           Constant::REJECTION_REASONS => $this->rejectionReasons
       ];
    }

    public function getVerificationStatus()
    {
        return Constant::REJECTED;
    }
}
