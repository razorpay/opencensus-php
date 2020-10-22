<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Detail\PennyTesting;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

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
            ($this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::CANCELLED_CHEQUE) !== null) and
            ($this->merchantCore->isRazorxExperimentEnable($this->merchant->getId(), RazorxTreatment::BVS_CANCELLED_CHEQUE_OCR) === true));
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

        $configName = $this->merchantDetails->isUnregisteredBusiness() ?
            Constant::CANCELLED_CHEQUE_OCR_UNREG :
            Constant::CANCELLED_CHEQUE_OCR_REG;

        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::BANK_ACCOUNT,
            Constant::CONFIG_NAME     => $configName,
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

    public function performPostProcessOperation(): void
    {
        $this->merchantDetails->setBankDetailsDocVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
