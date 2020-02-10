<?php

// this is not being used currently as the bank is sending the recon file from the same mail as that of netbanking.
// since there is mapping between email and gateway, same email cannot have two gateways mapped.
// keeping this if bank changes the recon email.
namespace RZP\Reconciliator\PaylaterIcici;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\PaylaterIcici\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconFields::RECON_FIELDS;
    }
}
