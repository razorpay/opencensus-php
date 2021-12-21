<?php

namespace RZP\Models\Merchant\Product\Requirements;

class PaymentLinksRequirementService extends PaymentProductsBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isNonTerminalStatusApplicable() {
        return true;
    }
}
