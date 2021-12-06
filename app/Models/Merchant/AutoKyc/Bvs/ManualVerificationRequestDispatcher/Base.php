<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

abstract class Base implements ManualVerificationRequestDispatcher
{

    protected $merchantDetails;

    protected $merchant;

    protected $bvsCore;

    protected $merchant_id;

    public function __construct(Merchant\Entity $merchant)
    {

        $this->bvsCore = new AutoKyc\Bvs\Core();

        $this->merchant = $merchant;

        $this->merchant_id = $merchant->getId();

        $this->merchantDetails = $merchant->merchantDetail;
    }

    /**
     * Generates specific payload for various actions, like
     * activating merchant, ask for clarification etc.
     *
     * @return array
     */
    public function getRequestPayload(): array {
        return [
            Constant::ARTEFACT_TYPE   => Constant::COMMON,
            Constant::CONFIG_NAME     => Constant::COMMON_MANUAL_VERIFICATION,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::DATA => [
                    Constant::MANUAL_VERIFICATION_STATUS => $this->getVerificationStatus(),
                    Constant::NOTES => $this->getVerificationNotes(),
                    Constant::MERCHANT_DATA => $this->getRequiredMerchantDetails()
                ],
            ],
        ];
    }

    /**
     * Get notes specific to this verification, like clarification data
     * for needs clarification request
     *
     */
    public abstract function getVerificationNotes();

    /**
     * Get verification status of merchant like: Activated, needs clarification etc.
     *
     */
    public abstract function getVerificationStatus();

    /**
     *
     * Returns merchant data snapshot to store on what data
     * manual verification event was done.
     *
     * @return array
     */
    public function getRequiredMerchantDetails() : array {
        $uploadedDocuments = [];
        $merchantDocuments = $this->merchant->merchantDocuments;

        foreach ($merchantDocuments as $merchantDocument) {
            $uploadedDocuments[$merchantDocument->getDocumentType()] = $merchantDocument->getFileStoreId();
        }

        $kycDetails = [
            Constant::CIN                => $this->merchantDetails->getCompanyCin(),
            Constant::GSTIN              => $this->merchantDetails->getGstin(),
            Constant::PAN_NUMBER         => $this->merchantDetails->getPan(),
            Constant::PAN_NAME           => $this->merchantDetails->getPanName(),
            Constant::PROMOTER_PAN       => $this->merchantDetails->getPromoterPan(),
            Constant::PROMOTER_PAN_NAME  => $this->merchantDetails->getPromoterPanName()
        ];

        $bankAccountDetails = [];
        if($this->merchantDetails->hasBankAccountDetails()){
            $bankAccountDetails[Constant::IFSC] = $this->merchantDetails->getBankBranchIfsc();
            $bankAccountDetails[Constant::BANK_ACCOUNT_NUMBER] = $this->merchantDetails->getBankAccountNumber();
        }

        return [
            Constant::DOCUMENTS => $uploadedDocuments,
            Constant::KYC_DETAILS => $kycDetails,
            Constant::BANK_ACCOUNT => $bankAccountDetails
        ];
    }

    /**
     * Triggers bvs validation request, currently only purpose of this to inform BVS
     * and not to perform any actual validation.
     */
    public function triggerBVSRequest(): void
    {
        $payload = $this->getRequestPayload();
        $this->bvsCore->verify($this->merchant_id, $payload);
    }
}
