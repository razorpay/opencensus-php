<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\Entity as MerchantEntity;

class BankAccount extends DefaultStatusUpdater
{
    /**
     * BankAccount constructor.
     *
     * @param MerchantEntity $merchant
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant, Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS, $consumedValidation);
    }

    public function getUpdatedActivationStatus(): string
    {
        return (new Detail\Core())->getApplicableActivationStatus($this->merchantDetails);
    }
}
