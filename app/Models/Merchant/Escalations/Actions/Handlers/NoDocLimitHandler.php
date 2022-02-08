<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Models\Merchant\AccountV2\Core as AccV2Core;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\NeedsClarification\Core;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class NoDocLimitHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        try
        {
            $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $action, $params)
            {
                $accountV2Core = new AccV2Core();

                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $merchantDetails = (new DetailCore())->getMerchantDetails($merchant);

                $merchant->setHoldFunds(true);

                $merchant->setHoldFundsReason(Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH);

                $merchant->deactivate();

                $this->trace->info(
                    TraceCode::DISABLE_PAYMENTS_AND_HOLD_FUNDS_DUE_TO_ESCALATION,
                    [
                        'merchant_id'       => $merchantId,
                        'hold_funds_reason' => Constants::HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH
                    ]
                );

                $kycClarificationReasons = (new Core())->composeNeedsClarificationForNoDocLimitBreach($merchant);

                $updatedKycClarificationReasons = (new DetailCore())->getUpdatedKycClarificationReasons($kycClarificationReasons, $merchantId, DetailConstants::SYSTEM);

                if(empty($updatedKycClarificationReasons) === false)
                {
                    $merchantDetails->setKycClarificationReasons($updatedKycClarificationReasons);
                }

                $activationStatusData = [
                    DetailEntity::ACTIVATION_STATUS => Status::NEEDS_CLARIFICATION
                ];

                (new DetailCore())->updateActivationStatus($merchant, $activationStatusData, $merchant);

                if ($merchantDetails->getActivationStatus() !== Status::NEEDS_CLARIFICATION)
                {
                    throw new LogicException('activation status not changed to NC after GMV limit breach for merchant with id ' . $merchantId);
                }

                $accountV2Core->removeNoDocOnboardingFeature($merchantId);

                $accountV2Core->addNoDocLimitBreachedTag($merchant);

                $this->trace->info(
                    TraceCode::NO_DOC_ONBOARDING_ESCALATION_SUCCESS,
                    [
                        'merchant_id'   => $merchantId,
                        'milestone'     => $params['milestone'] ?? null,
                        'threshold'     => $params['threshold'] ?? null
                    ]
                );
            });
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::NO_DOC_ONBOARDING_ESCALATION_FAILURE,
                [
                    'reason'        => 'something went wrong while handling no-doc onboarding escalation',
                    'trace'         => $e->getMessage(),
                    'merchant_id'   => $merchantId,
                    'milestone'     => $params['milestone'] ?? null,
                    'threshold'     => $params['threshold'] ?? null
                ]
            );

            throw $e;
        }
    }
}
