<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

use RZP\Jobs;
use RZP\Constants\Mode;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\BvsValidation\Constants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\BvsValidation\Constants as ValidationConstants;



class GstInStatusUpdater extends DefaultStatusUpdater
{
    /**
     * GstIn constructor.
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
            BusinessDetailConstants::GSTIN_SCORE)) === true and
            $this->merchantDetails->getGstinVerificationStatus() === DEConstants::VERIFIED)
        {
            $merchantDetailCore = (new MerchantDetailCore());

            $merchantDetailCore->generateLeadScoreForMerchant($this->merchant->getId(), true, false);
        }

        $this->handleArtefactSignatoryValidation();

        if(($this->merchant->isNoDocOnboardingEnabled() === true) or
            ($this->merchant->isRouteNoDocKycEnabledForParentMerchant() === true))
        {
            $this->gstValidationForNoDocOnboarding();
        }
        else
        {
            $this->postUpdateValidationStatus();
        }
    }

    protected function gstValidationForNoDocOnboarding()
    {
        $store = new StoreCore();
        $data = $store->fetchValuesFromStore($this->merchant->getId(), ConfigKey::ONBOARDING_NAMESPACE,
            [ConfigKey::NO_DOC_ONBOARDING_INFO], StoreConstants::INTERNAL);

        $noDocData = $data[ConfigKey::NO_DOC_ONBOARDING_INFO] ?? null;

        if (is_null($noDocData) === true)
        {
            $this->postUpdateValidationStatus();

            return;
        }

        $currentIndex =  $noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::CURRENT_INDEX];
        if ($this->merchantDetails->getGstinVerificationStatus() === DEConstants::VERIFIED)
        {
            $this->trace->info(
                TraceCode::GSTIN_VERIFICATION_SUCCESSFUL_FOR_NO_DOC,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'artefact_type' => 'gst',
                ]
            );

            $merchantDetails = $this->merchantDetails;

            $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails) {

                $this->trace->info(
                    TraceCode::SAVE_MERCHANT_DETAILS_FOR_NO_DOC_IN_GSTIN_STATUS_UPDATER,
                    [
                        'merchant_id'   => $this->merchant->getId(),
                        'artefact_type' => 'gst'
                    ]
                );

                $this->repo->merchant_detail->saveOrFail($merchantDetails);
            });

            $this->postUpdateValidationStatus();
        }
        else
        {
            if ($currentIndex + 1 < count($noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE]))
            {
                $currentIndex = $currentIndex + 1;

                $noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::CURRENT_INDEX] = $currentIndex;

                $input = [
                    StoreConstants::NAMESPACE           => ConfigKey::ONBOARDING_NAMESPACE,
                    ConfigKey::NO_DOC_ONBOARDING_INFO   => $noDocData
                ];

                $store ->updateMerchantStore($this->merchant->getId(), $input, StoreConstants::INTERNAL);

                $nextGst = $noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE][$currentIndex];

                $this->merchantDetails->setAttribute(Detail\Entity::GSTIN, $nextGst);

                $merchantDetails = $this->merchantDetails;

                $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails) {

                    $this->trace->info(
                        TraceCode::SAVE_MERCHANT_DETAILS_FOR_NO_DOC_IN_GSTIN_STATUS_UPDATER,
                        [
                            'merchant_id'   => $this->merchant->getId(),
                            'artefact_type' => 'gst'
                        ]
                    );

                    $this->repo->merchant_detail->saveOrFail($merchantDetails);
                });

                Jobs\GstinValidation:: dispatch(Mode::LIVE, $this->merchant, $nextGst);
            }
            else
            {
                $this->trace->info(
                    TraceCode::ALL_GSTIN_VERIFICATION_FAILED_FOR_NO_DOC_MERCHANT,
                    [
                        'merchant_id'   => $this->merchant->getId(),
                        $nextGst = $noDocData[DEConstants::VERIFICATION][DetailEntity::GSTIN][DEConstants::VALUE][$currentIndex]
                    ]
                );

                $merchantDetails = $this->merchantDetails;

                $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails) {

                    $this->trace->info(
                        TraceCode::SAVE_MERCHANT_DETAILS_FOR_NO_DOC_IN_GSTIN_STATUS_UPDATER,
                        [
                            'merchant_id'   => $this->merchant->getId(),
                            'artefact_type' => 'gst'
                        ]
                    );

                    $this->repo->merchant_detail->saveOrFail($merchantDetails);
                });

                $this->postUpdateValidationStatus();
            }
        }
    }
}
