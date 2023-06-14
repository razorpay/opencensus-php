<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Jobs;
use RZP\Constants\Mode;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;


class PanStatusUpdater extends DefaultStatusUpdater
{
    /**
     * Pan constructor.
     *
     * @param MerchantEntity $merchant
     * @param Detail\Entity  $merchantDetails
     * @param string         $documentTypeStatusKey
     * @param Entity         $consumedValidation
     */
    public function __construct(MerchantEntity $merchant,
                                Detail\Entity $merchantDetails,
                                string $documentTypeStatusKey,
                                Entity $consumedValidation)
    {
        parent::__construct($merchant, $merchantDetails, $documentTypeStatusKey, $consumedValidation);
    }

   public function updateValidationStatus(): void
   {
       $this->processUpdateValidationStatus();

       if (empty(optional($this->merchantDetails->businessDetail)->getValueFromLeadScoreComponents(
           BusinessDetailConstants::GSTIN_SCORE)) === true)
       {
           $merchantDetailCore = (new MerchantDetailCore());

           $merchantDetailCore->generateLeadScoreForMerchant($this->merchant->getId(), true, false);
       }

       // If merchant is enabled with NoDocOnboarding feature then we fetch gst from verified pan and trigger BVS request.
       if(($this->merchant->isNoDocOnboardingEnabled() === true) or
           ($this->isGstVerificationEnabledLinkedAccount() === true))
       {
           $this->fetchGstAndTriggerValidationIfApplicable();
       }

       // Updates merchant context and send Segment event.
       $this->postUpdateValidationStatus();
   }

    protected function isGstVerificationEnabledLinkedAccount() : bool
    {
        if($this->merchant->isLinkedAccount() === true and
            $this->merchant->isRouteNoDocKycEnabledForParentMerchant() === true and
            Detail\BusinessType::isGstinVerificationEnableBusinessTypes($this->merchantDetails->getBusinessTypeValue()))
        {
            return true;
        }
        return false;
    }

   protected function fetchGstAndTriggerValidationIfApplicable()
   {
       $artefactType = $this->artefactType;
       $store = new StoreCore();
       $merchantDetailCore = (new MerchantDetailCore());

       $data = $store->fetchValuesFromStore($this->merchant->getId(), ConfigKey::ONBOARDING_NAMESPACE,
           [ConfigKey::NO_DOC_ONBOARDING_INFO],StoreConstants::INTERNAL);

       $noDocData = $data[ConfigKey::NO_DOC_ONBOARDING_INFO] ?? null;

       if(empty($noDocData) === true)
       {
           $noDocData = (new Detail\Core())->initializeValidationDetailsForNoDocOnboarding($this->merchantDetails);
       }

       if ($artefactType === Constant::PERSONAL_PAN)
       {
           $artefact    = Detail\Entity::PROMOTER_PAN;
           $pan = $this->merchantDetails->getPromoterPan();
           $panStatus = $this->merchantDetails->getPoiVerificationStatus();
       }
       else if ($artefactType === Constant::BUSINESS_PAN)
       {
           $artefact    = Detail\Entity::COMPANY_PAN;
           $pan = $this->merchantDetails->getPan();
           $panStatus = $this->merchantDetails->getCompanyPanVerificationStatus();
       }
       else
       {
           return;
       }

       // If pan is verified get all active gst's available with this pan and add them to redis.
       if ($panStatus === BvsValidationConstants::VERIFIED)
       {
           $gstDetailsFromPan = (new Bvs\Core())->probeGetGstDetails($pan, 'Active');

           $noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE] = array_merge($noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE], $gstDetailsFromPan);

           if (empty($gstDetailsFromPan) === false)
           {
               $fieldMap = [
                   'gstin' => [$gstDetailsFromPan]
               ];

               $dedupeResponse = $merchantDetailCore->triggerStrictDedupeForNoDocOnboarding($this->merchantDetails, $fieldMap, $noDocData, []);

               $merchantDetailCore->processDedupeResponse([DetailEntity::GSTIN], $dedupeResponse, $noDocData);
           }

           $merchantDetailCore->updateNoDocOnboardingConfig($noDocData, $store);
       }
       else
       {
           if (isset($noDocData[DEConstants::VERIFICATION][$artefact])  === true) {
               $noDocData[DEConstants::VERIFICATION][$artefact][DEConstants::RETRY_COUNT] = $noDocData[DEConstants::VERIFICATION][$artefact][DEConstants::RETRY_COUNT] + 1;

               if ( $noDocData[DEConstants::VERIFICATION][$artefact][DEConstants::RETRY_COUNT] > 1)
               {
                   $noDocData[DEConstants::VERIFICATION][$artefact][DEConstants::STATUS] = Detail\RetryStatus::FAILED;
               }
           }

           $merchantDetailCore->updateNoDocOnboardingConfig($noDocData, $store);
       }


       $this->trace->info(
           TraceCode::PAN_RETRY_STATUS,
           [
               'merchant_id'      => $this->merchant->getId(),
               'artefact_type'    => $artefactType,
               'noDocData'        => $noDocData
           ]
       );

       $isPanValidationDone = (new UpdateContextRequirements())->isNoDocPanValidationCompleted($this->merchantDetails);

       // if GST data is found then trigger GST validation else update merchant context
       if ($isPanValidationDone === true)
       {
           $this->trace->info(
               TraceCode::PAN_VALIDATION_DONE,
               [
                   'merchant_id'      => $this->merchant->getId(),
                   'artefact_type'    => $artefactType,
                   'pan'              => $pan,
                   'pan_status'       => $panStatus
               ]
           );

           if(isset($noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE]) === true and count($noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE]) > 0)
           {
               $this->triggerGstInValidationJob($noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE][0]);
           }
           else
           {
               $this->trace->info(
                   TraceCode::NO_GSTIN_FOUND_AFTER_PAN_VALIDATION_DONE,
                   [
                       'merchant_id'       => $this->merchant->getId(),
                       'artefact_type'    => $artefactType,
                       'pan'              => $pan,
                   ]
               );

               $this->postUpdateValidationStatus();
           }
       }
   }

   private function triggerGstInValidationJob(string $gst)
   {
       $this->merchantDetails->setAttribute(Detail\Entity::GSTIN, $gst);

       $merchantDetails = $this->merchantDetails;

       $this->repo->transactionOnLiveAndTest(function () use ($merchantDetails, $gst) {

           $this->trace->info(
               TraceCode::SAVE_MERCHANT_DETAILS_FOR_NO_DOC,
               [
                   'merchant_id'    => $this->merchant->getId(),
                   'artefact_type'  => 'gst',
                   'gst'            => $gst
               ]
           );

           $this->repo->merchant_detail->saveOrFail($merchantDetails);
       });

       Jobs\GstinValidation:: dispatch(Mode::LIVE, $this->merchant, $gst);
   }
}
