<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\BvsValidation\Constants;

class PersonalPanForCA extends BaseStatusUpdater
{
    protected $entity;

    protected $bankingAccountActivationDetail;

    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                string $documentTypeStatusKey,
                                BvsValidation\Entity $validation,
                                string $entity = E::BANKING_ACCOUNT_ACTIVATION_DETAIL)
    {
        parent::__construct($merchant,$merchantDetails, $validation);

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

                $this->FireHubspotEvent($documentValidationStatus);

                $this->fireSegmentEvent($documentValidationStatus);

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

    protected function fireSegmentEvent(string $documentValidationStatus)
    {
        if ($documentValidationStatus === Constants::FAILED) {
            return; // No event push required if BVS call fails.
        }

        $properties = [];
        if ($documentValidationStatus === Constants::VERIFIED) {
            $properties["document_verification_status"] = Constants::VERIFIED;
        } else if ($documentValidationStatus === Constants::INCORRECT_DETAILS
            or $documentValidationStatus === Constants::NOT_MATCHED) {
            $properties["document_verification_status"] = Constants::FAILED;
        }

        $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH, [
            'eventName' => SegmentEvent::BANKING_ACCOUNT_DOCUMENT_VERIFICATION_STATUS,
            'properties' => $properties
        ]);

        $this->app['x-segment']->pushIdentifyAndTrackEvent($this->merchant, $properties,
            SegmentEvent::BANKING_ACCOUNT_DOCUMENT_VERIFICATION_STATUS);
    }

    protected function FireHubspotEvent(string $documentValidationStatus)
    {
        $this->trace->info(TraceCode::NEOSTONE_HUBSPOT_REQUEST, [
            'merchant_id'                  => $this->merchantDetails->getId(),
            'document_verification_status' => $documentValidationStatus
        ]);

        if ($documentValidationStatus === Constants::VERIFIED)
        {
            $merchantEmail = $this->merchant->getEmail();

            $payload = ['ca_pan_validation_failed' => 'FALSE'];

            $this->app->hubspot->trackHubspotEvent($merchantEmail, $payload);
        }
        else
        {
            if ($documentValidationStatus === Constants::INCORRECT_DETAILS or $documentValidationStatus === Constants::NOT_MATCHED)
            {
                $merchantEmail = $this->merchant->getEmail();

                $payload = ['ca_pan_validation_failed' => 'TRUE'];

                $this->app->hubspot->trackHubspotEvent($merchantEmail, $payload);
            }
        }
    }
}
