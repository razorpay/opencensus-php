<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use Illuminate\Support\Facades\Bus;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\PennyTesting;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

use RZP\Models\Merchant\BvsValidation;
class CancelledChequeOcr extends Base
{

    protected const CANCELLED_CHEQUE_DOC_INDEX = '1';

    /**
     * Checks condition that we can trigger validation or not
     *
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return (
            ($this->merchantDetails->getBankDetailsDocVerificationStatus() === BvsValidationConstants::PENDING) and
            ($this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::CANCELLED_CHEQUE) !== null));
    }

    /**
     * Note: $accountHolderNames should have personal pan name first and then Business_pan name
     * because cancelled check ocr rules expect names in this order only.
     *
     * @return array
     */
    public function getRequestPayload(): array
    {
        $accountHolderNames = (new PennyTesting())->getAllowedMerchantAttributesDetails($this->merchantDetails);

        $accountHolderNames = array_values($accountHolderNames);

        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::BANK_ACCOUNT,
            Constant::CONFIG_NAME     => $this->getConfigName(),
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::ACCOUNT_NUMBER       => $this->merchantDetails->getBankAccountNumber(),
                Constant::IFSC                 => $this->merchantDetails->getBankBranchIfsc(),
                Constant::ACCOUNT_HOLDER_NAMES => $accountHolderNames,
            ],
            Constant::PROOFS          => [
                self::CANCELLED_CHEQUE_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::CANCELLED_CHEQUE),
                ],
            ]
        ];

        return $payload;
    }

    protected function getConfigName()
    {
        if ($this->merchantDetails->isUnregisteredBusiness())
        {
            return Constant::CANCELLED_CHEQUE_OCR_PERSONAL_PAN;
        }

        switch ($this->merchantDetails->getBusinessType())
        {
            case BusinessType::PRIVATE_LIMITED:
            case BusinessType::PUBLIC_LIMITED:
            case BusinessType::LLP:
            case BusinessType::PARTNERSHIP:
            case BusinessType::HUF:
                return Constant::CANCELLED_CHEQUE_OCR_BUSINESS_PAN;

            default:
                return Constant::CANCELLED_CHEQUE_OCR_BUSINESS_OR_PROMOTER_PAN;
        }
    }

    public function performPostProcessOperation(BvsValidation\Entity $validation): void
    {
        $this->merchantDetails->setBankDetailsDocVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
