<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\BvsValidation\Constants;

class BusinessPanForCA extends BaseStatusUpdater
{
    protected $entity;

    protected $bankingAccountActivationDetail;

    public function __construct(MerchantEntity $merchant,
                                string $documentTypeStatusKey,
                                string $artefactType,
                                string $consumedValidationId,
                                string $entity=E::BANKING_ACCOUNT_ACTIVATION_DETAIL)
    {
        parent::__construct($merchant, $artefactType, $consumedValidationId);

        $this->documentTypeStatusKey = $documentTypeStatusKey;

        $this->entity = $entity;

        $this->bankingAccountActivationDetail = $this->getBankingAccountActivationEntity($merchant);
    }

    protected function getBankingAccountActivationEntity(MerchantEntity $merchant)
    {
        $bankingAccount = $this->repo->banking_account->getBankingAccountOfMerchant($merchant, 'rbl');

        return $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());
    }

    public function updateValidationStatus(): void
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerIdAndOwnerType(
            $this->bankingAccountActivationDetail->getBankingAccountId(),
            Constant::BANKING_ACCOUNT,
            $this->artefactType);

        if (empty($validation) === false)
        {
            $documentValidationStatus = $this->getDocumentValidationStatus($validation);

            //
            // if $documentValidationStatus is null then don't send any metrics
            //
            if (empty($documentValidationStatus) === false)
            {
                $this->bankingAccountActivationDetail->setPanVerificationStatus($documentValidationStatus);

                $this->repo->saveOrFail($this->bankingAccountActivationDetail);

                $verificationMetrics = [
                    Constant::ARTEFACT_TYPE                     => $this->artefactType,
                    Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $documentValidationStatus
                ];

                $this->trace->count(Detail\Metric::VALIDATION_STATUS_BY_ARTEFACT_TOTAL, $verificationMetrics);
            }

            $this->trace->info(TraceCode::BANKING_ACCOUNT_BVS_PAN_VERIFICATION_STATUS, [
                'merchant_id'                  => $this->merchantDetails->getId(),
                'artefact_type'                => $this->artefactType,
                'document_verification_status' => $documentValidationStatus
            ]);
        }
    }

    public function updateStatusToPending(): void
    {
        $this->bankingAccountActivationDetail->setPanVerificationStatus(Constants::PENDING);

        $this->updateStakeholderStatusIfApplicable(Constants::PENDING);
    }
}
