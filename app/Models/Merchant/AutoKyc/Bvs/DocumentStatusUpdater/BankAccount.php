<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Entity as MerchantEntity;

class BankAccount extends DefaultStatusUpdater
{
    /**
     * BankAccount constructor.
     *
     * @param MerchantEntity $merchant
     * @param string         $artefactType
     * @param string         $consumedValidationId
     */
    public function __construct(MerchantEntity $merchant,
                                string $artefactType,
                                string $consumedValidationId)
    {
        parent::__construct($merchant, Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS, $artefactType, $consumedValidationId);
    }

    public function getUpdatedActivationStatus(): string
    {
        return (new Detail\Core())->getApplicableActivationStatus($this->merchantDetails);
    }
}
