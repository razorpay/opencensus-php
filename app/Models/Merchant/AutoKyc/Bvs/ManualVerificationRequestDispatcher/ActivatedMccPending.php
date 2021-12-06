<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class ActivatedMccPending extends Base {

    public function getVerificationNotes()
    {
        return null;
    }

    public function getVerificationStatus()
    {
        return Constant::ACTIVATED_MCC_PENDING;
    }
}
