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
     * @param Detail\Entity  $merchantDetail
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetail,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant,$merchantDetail, Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS, $consumedValidation);
    }

    public function getUpdatedActivationStatus(): string
    {
        return (new Detail\Core())->getApplicableActivationStatus($this->merchantDetails);
    }
}
