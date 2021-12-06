<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class NeedsClarification extends Base {

    protected $clarificationData;

    public function __construct(Merchant\Entity $merchant, $clarificationData)
    {
        $this->clarificationData = $clarificationData;
        parent::__construct($merchant);
    }

    public function getVerificationNotes()
    {
        return $this->clarificationData;
    }

    public function getVerificationStatus()
    {
       return Constant::NEEDS_CLARIFICATION;
    }
}
