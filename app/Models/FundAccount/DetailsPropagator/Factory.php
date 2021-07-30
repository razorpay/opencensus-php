<?php

namespace RZP\Models\FundAccount\DetailsPropagator;

class Factory
{

    public static function getSubscribers(): array
    {
        return [new VendorPaymentDetailsPropagator()];
    }

}
