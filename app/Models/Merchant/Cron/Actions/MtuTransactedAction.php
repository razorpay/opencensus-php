<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\Merchant\M2MReferral\Service as M2MService;
use RZP\Models\Merchant\Escalations\Core as EscalationCore;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\Escalations\Constants as EscalationConstants;
use RZP\Constants\HyperTrace;

class MtuTransactedAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        return Tracer::inSpan([
            'name' => HyperTrace::MTU_TRANSACTED_ACTION_EXECUTE
        ], function () use ($data) {

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_CRON_STARTED, [
                'cron_type' => 'mtu-transacted',
                'args' => $this->args
            ]);

            if (empty($data) === true)
            {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_CRON_SKIPPED, [
                    'cron_type' => 'mtu-transacted',
                    'reason' => 'empty_data'
                ]);
                return new ActionDto(Constants::SKIPPED);
            }

            $collectorData = $data["mtu_transacted_merchants"]; // since data collector is an array

            $merchantIdList = $collectorData->getData();

            if (count($merchantIdList) === 0)
            {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_CRON_SKIPPED, [
                    'cron_type' => 'mtu-transacted',
                    'reason' => 'empty_merchant_list'
                ]);
                return new ActionDto(Constants::SKIPPED);
            }

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_MERCHANT_PROCESSING, [
                'cron_type' => 'mtu-transacted',
                'merchant_count' => count($merchantIdList),
                'merchant_ids' => $merchantIdList
            ]);

            $successCount = 0;

            foreach ($merchantIdList as $merchantId)
            {
                try
                {
                    Tracer::inSpan([
                        'name' => HyperTrace::MTU_TRANSACTED_ACTION_PROCESS_MERCHANT,
                        'attributes' => ['merchant_id' => $merchantId]
                    ], function () use ($merchantId) {
                        $this->pushSegmentEvent($merchantId);
                    });

                    $successCount++;
                }
                catch (\Throwable $ex)
                {
                    $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                        'args'        => $this->args,
                        'merchant_id' => $merchantId
                    ]);
                }
            }

            Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_SEND_SEGMENT_BATCH
            ], function () {
                $this->app['segment-analytics']->buildRequestAndSend();
            });

            if ($successCount === 0)
            {
                $status = Constants::FAIL;
            }
            else
            {
                $status = ($successCount < count($merchantIdList)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
            }

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_CRON_FINISHED, [
                'cron_type' => 'mtu-transacted',
                'status' => $status,
                'success_count' => $successCount,
                'total_count' => count($merchantIdList)
            ]);

            return new ActionDto($status);
        });
    }

    private function pushSegmentEvent($merchantId)
    {
        return Tracer::inSpan([
            'name' => HyperTrace::MTU_TRANSACTED_ACTION_PUSH_SEGMENT_EVENT,
            'attributes' => ['merchant_id' => $merchantId]
        ], function () use ($merchantId) {

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_MERCHANT_PROCESSING, [
                'merchant_id' => $merchantId,
                'step' => 'push_segment_event_start'
            ]);

            $merchant = Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_FETCH_MERCHANT
            ], function () use ($merchantId) {
                return $this->repo->merchant->findOrFailPublic($merchantId);
            });

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_MERCHANT_FETCHED, [
                'merchant_id' => $merchantId,
                'step' => 'merchant_fetched',
                'activation_status' => $merchant->merchantDetail->getActivationStatus()
            ]);

            $merchantsTransaction = Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_FETCH_FIRST_TRANSACTION
            ], function () use ($merchantId) {
                return $this->repo->transaction->fetchFirstTransactionDetails($merchantId);
            });

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_TRANSACTION_FETCHED, [
                'merchant_id' => $merchantId,
                'step' => 'transaction_details_fetched',
                'transaction_amount' => $merchantsTransaction['amount'],
                'transaction_created_at' => $merchantsTransaction['created_at']
            ]);

            $previousActivationStatus = Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_FETCH_PREVIOUS_ACTIVATION_STATUS
            ], function () use ($merchant) {
                return $this->repo->state->getPreviousActivationStatus($merchant->getId());
            });

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_ACTIVATION_STATUS_FETCHED, [
                'merchant_id' => $merchantId,
                'step' => 'previous_activation_status_fetched',
                'previous_status' => $previousActivationStatus['name']
            ]);

            $referralCode = Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_GET_REFERRAL_CODE
            ], function () use ($merchant) {
                return (new M2MService())->getReferralCodeIfApplicable($merchant);
            });

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_REFERRAL_CODE_FETCHED, [
                'merchant_id' => $merchantId,
                'step' => 'referral_code_fetched',
                'has_referral_code' => ($referralCode !== null)
            ]);

            $properties = [
                'mtu'                         => true,
                'first_transaction_timestamp' => $merchantsTransaction['created_at'],
                'activation_status'           => $merchant->merchantDetail->getActivationStatus(),
                'previous_activation_status'  => $previousActivationStatus['name'],
                'is_m2m_referral'             => ($referralCode == null ? false : true),
                '$referralCode'               => $referralCode,
                'amount'                      => $merchantsTransaction['amount'],
                'easyOnboarding'              => $merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING)
            ];

            $userDeviceDetail = Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_FETCH_USER_DEVICE_DETAIL
            ], function () use ($merchantId) {
                return $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchantId);
            });

            if (empty($userDeviceDetail) === false)
            {
                $properties['signup_source'] = $userDeviceDetail->getSignupSource();
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_USER_DEVICE_FETCHED, [
                    'merchant_id' => $merchantId,
                    'step' => 'signup_source_added',
                    'signup_source' => $userDeviceDetail->getSignupSource()
                ]);
            }

            Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_PUSH_TO_SEGMENT_ANALYTICS
            ], function () use ($merchant, $properties, $merchantsTransaction, $merchantId) {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_SEGMENT_PUSH_START, [
                    'merchant_id' => $merchantId,
                    'step' => 'segment_analytics_push_start',
                    'properties_count' => count($properties)
                ]);

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $merchant, $properties, SegmentEvent::MTU_TRANSACTED, $merchantsTransaction['created_at']);

                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_SEGMENT_PUSH_COMPLETED, [
                    'merchant_id' => $merchantId,
                    'step' => 'segment_analytics_push_completed'
                ]);
            });

            Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_APPLY_MTU_COUPON
            ], function () use ($merchant, $merchantId) {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_COUPON_APPLY_START, [
                    'merchant_id' => $merchantId,
                    'step' => 'apply_mtu_coupon_start'
                ]);

                (new EscalationCore())->applyMtuCouponIfEligible($merchant);

                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_COUPON_APPLY_COMPLETED, [
                    'merchant_id' => $merchantId,
                    'step' => 'apply_mtu_coupon_completed'
                ]);
            });

            Tracer::inSpan([
                'name' => HyperTrace::MTU_TRANSACTED_ACTION_ENABLE_FTUX_DASHBOARD
            ], function () use ($merchant, $merchantId) {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_FTUX_KEYS_START, [
                    'merchant_id' => $merchantId,
                    'step' => 'enable_ftux_dashboard_start'
                ]);

                $this->enableFtuxDashboardKeys($merchant->getId());

                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_FTUX_KEYS_COMPLETED, [
                    'merchant_id' => $merchantId,
                    'step' => 'enable_ftux_dashboard_completed'
                ]);
            });

            if (in_array($merchant->merchantDetail->getActivationStatus(),
                         [
                             DetailStatus::INSTANTLY_ACTIVATED,
                             DetailStatus::UNDER_REVIEW,
                             DetailStatus::ACTIVATED_MCC_PENDING
                         ]) === true)
            {
                Tracer::inSpan([
                    'name' => HyperTrace::MTU_TRANSACTED_ACTION_WEBSITE_COMPLIANCE_CHECK
                ], function () use ($merchant, $merchantId) {

                    $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_COMPLIANCE_START, [
                        'merchant_id' => $merchantId,
                        'step' => 'website_compliance_check_start',
                        'activation_status' => $merchant->merchantDetail->getActivationStatus()
                    ]);

                    $isWebsiteSectionsApplicable = Tracer::inSpan([
                        'name' => HyperTrace::MTU_TRANSACTED_ACTION_CHECK_WEBSITE_SECTIONS_APPLICABLE
                    ], function () use ($merchant) {
                        return (new WebsiteService())->isWebsiteSectionsApplicable($merchant);
                    });

                    if ($isWebsiteSectionsApplicable === true)
                    {
                        $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_COMPLIANCE_START, [
                            'merchant_id' => $merchantId,
                            'type'     => 'website_Adherence_applicable',
                            'args'     => $this->args,
                        ]);

                        $websiteDetail = Tracer::inSpan([
                            'name' => HyperTrace::MTU_TRANSACTED_ACTION_FETCH_WEBSITE_DETAILS
                        ], function () use ($merchantId) {
                            return $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantId);
                        });

                        if (empty(optional($websiteDetail)->getStatus()) === true)
                        {
                            Tracer::inSpan([
                                'name' => HyperTrace::MTU_TRANSACTED_ACTION_SEND_WEBSITE_ADHERENCE_NOTIFICATION
                            ], function () use ($merchant, $merchantId) {

                                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_COMPLIANCE_START, [
                                    'merchant_id' => $merchantId,
                                    'type'     => 'website_adherence_communication',
                                    'args'     => $this->args,
                                ]);

                                $args = [
                                    EscalationConstants::MERCHANT => $merchant,
                                    "params"                      => [
                                        "complianceUrl" => 'https://dashboard.razorpay.com/app/website-app-details']
                                ];

                                (new OnboardingNotificationHandler($args))
                                    ->sendEventNotificationForMerchant($merchantId, Events::WEBSITE_ADHERENCE_HARD_NUDGE);

                                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_NOTIFICATION_SENT, [
                                    'merchant_id' => $merchantId,
                                    'step' => 'website_adherence_notification_sent'
                                ]);
                            });
                        }
                        else
                        {
                            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_DETAILS_EXIST, [
                                'merchant_id' => $merchantId,
                                'step' => 'website_details_already_exist',
                                'website_status' => optional($websiteDetail)->getStatus()
                            ]);
                        }
                    }
                    else
                    {
                        $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_SECTIONS_NOT_APPLICABLE, [
                            'merchant_id' => $merchantId,
                            'step' => 'website_sections_not_applicable'
                        ]);
                    }
                });
            }
            else
            {
                $this->app['trace']->info(TraceCode::MTU_TRANSACTED_WEBSITE_COMPLIANCE_SKIPPED, [
                    'merchant_id' => $merchantId,
                    'step' => 'website_compliance_skipped',
                    'activation_status' => $merchant->merchantDetail->getActivationStatus()
                ]);
            }

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_MERCHANT_PROCESSING, [
                'merchant_id' => $merchantId,
                'step' => 'push_segment_event_completed'
            ]);
        });
    }

    private function enableFtuxDashboardKeys($merchantId)
    {
        return Tracer::inSpan([
            'name' => HyperTrace::MTU_TRANSACTED_ACTION_ENABLE_FTUX_DASHBOARD_KEYS
        ], function () use ($merchantId) {

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_FTUX_KEYS_START, [
                'merchant_id' => $merchantId,
                'step' => 'enable_ftux_keys_start',
                'keys_to_enable' => [
                    'SHOW_FTUX_FINAL_SCREEN' => true,
                    'SHOW_FIRST_PAYMENT_BANNER' => true
                ]
            ]);

            (new Merchant\Store\Core)->updateMerchantStore($merchantId, [
                Store\Constants::NAMESPACE                  => Store\ConfigKey::ONBOARDING_NAMESPACE,
                Store\ConfigKey::SHOW_FTUX_FINAL_SCREEN     => true,
                Store\ConfigKey::SHOW_FIRST_PAYMENT_BANNER  => true
            ]);

            $this->app['trace']->info(TraceCode::MTU_TRANSACTED_FTUX_KEYS_COMPLETED, [
                'merchant_id' => $merchantId,
                'step' => 'enable_ftux_keys_completed'
            ]);
        });
    }
}
