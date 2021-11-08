<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\BankingAccount\Activation\Detail;
use RZP\Models\Merchant as Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class PersonalPanForBankingAccount extends Base
{
    protected $bankingDetail;

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, Detail\Entity $bankingDetail = null)
    {
        parent::__construct($merchant, $merchantDetails);

        $this->bankingDetail = $bankingDetail;
    }

    public function canTriggerValidation(): bool
    {
        return $this->bankingDetail->getPanVerificationStatus() === BvsValidationConstants::PENDING;
    }

    public function getRequestPayload(): array
    {
        $payload = [
            Constant::PLATFORM                => Constant::RX,
            Constant::ARTEFACT_TYPE           => Constant::PERSONAL_PAN,
            Constant::CONFIG_NAME             => Constant::PERSONAL_PAN,
            Constant::VALIDATION_UNIT         => BvsValidationConstants::IDENTIFIER,
            Constant::CUSTOM_CALLBACK_HANDLER => 'updateValidationStatusForBankingAccount',
            Constant::OWNER_ID                => $this->bankingDetail->getBankingAccountId(),
            Constant::DETAILS                 => [
                Constant::PAN_NUMBER => $this->bankingDetail->getBusinessPan(),
                Constant::NAME       => $this->bankingDetail->getMerchantPocName(),
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->bankingDetail->setPanVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
