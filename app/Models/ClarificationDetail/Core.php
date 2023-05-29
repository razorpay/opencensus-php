<?php

namespace RZP\Models\ClarificationDetail;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Notifications\Onboarding\Events as NCEvents;
use RZP\Models\ClarificationDetail\Core as ClarDetailCore;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\ClarificationDetail\Service as ClarDetailService;
use RZP\Models\Merchant\Escalations\Constants as EscalationsConstant;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class Core extends Base\Core
{
    const CLARIFICATION_DETAIL_CREATE_MUTEX_PREFIX = 'api_clarification_detail_create_';

    public function createClarificationDetailAdmin($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        /* Sample input
        {
           "clarification_reasons": [
               {
                   "group_name": "bank_details",
                   "field_details": {
                       "bank_account_number": "1234567891",
                       "bank_account_name": "test",
                       "bank_branch_ifsc": "icic0001231",
                       "cancelled_cheque": null
                   },
                   "comment": {
                     "type": "predefined",
                     "text": "bank_account_change_request_for_prop_ngo_trust"
                   }
               }
           ],
           "old_clarification_reasons": {
               "issue_fields": "bank_account_number,bank_account_name,bank_branch_ifsc",
               "kyc_clarification_reasons": {
                   "clarification_reasons": {
                       "bank_account_number": [
                           {
                               "reason_type": "predefined",
                               "field_value": "123456780",
                               "reason_code": "bank_account_change_request_for_unregistered"
                           }
                       ],
                       "bank_account_name": [
                           {
                               "reason_type": "predefined",
                               "field_value": "Ajay Kumar Brahm",
                               "reason_code": "bank_account_change_request_for_unregistered"
                           }
                       ],
                       "bank_branch_ifsc": [
                           {
                               "reason_type": "predefined",
                               "field_value": "SBIN0000202",
                               "reason_code": "bank_account_change_request_for_unregistered"
                           }
                       ]
                   },
                   "additional_reasons": {}
               }
           }
        }
        */
        foreach ($input as $entry)
        {
            $entry = array_merge($entry,
                                 [
                                     Entity::MERCHANT_ID  => $merchantId,
                                     Entity::MESSAGE_FROM => 'admin',
                                     Entity::STATUS       => 'needs_clarification',
                                     Entity::METADATA     => [
                                         Constants::ADMIN_EMAIL => $this->app['basicauth']->getAdmin()->getEmail(),
                                         Constants::NC_COUNT    => $this->getNcCount($merchant) + 1
                                     ]
                                 ]
            );

            $mutexResource = self::CLARIFICATION_DETAIL_CREATE_MUTEX_PREFIX . $merchantId;

            $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($entry) {

                $clarificationDetail = new Entity;

                $clarificationDetail->generateId();

                $this->trace->info(TraceCode::MERCHANT_CREATE_CLARIFICATION_DETAILS, $entry);

                $clarificationDetail->build($entry);

                $this->repo->clarification_detail->saveOrFail($clarificationDetail);

                return $clarificationDetail;
            });
        }

    }

    public function hasClarificationDetails($merchantId): bool
    {
        return $this->repo->clarification_detail->hasClarificationDetailsForMerchantId($merchantId) === true;
    }

    public function getClarificationDetail($merchantId): array
    {
        $clarificationDetails = $this->repo->clarification_detail->getByMerchantId($merchantId);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $response = [];

        foreach ($clarificationDetails as $clarificationDetail)
        {
            $groupName = $clarificationDetail->getGroupName();

            $response[$groupName]['comments'][] = $clarificationDetail->getCommentData($clarificationDetail);

            //fields should have latest row's fields and $clarificationDetails result is ordered by desc
            if (isset($response[$groupName]['fields']) === false)
            {
                $response[$groupName]['fields'] = $clarificationDetail->getFields();
            }
        }

        foreach ($response as &$group)
        {
            $array_column2 = array_column($group['comments'], Entity::CREATED_AT);

            array_multisort($array_column2, SORT_DESC, $group['comments']);

            $group['status'] = $group['comments'][0]['status'];

            $group[Constants::NC_COUNT] = $group['comments'][0][Constants::NC_COUNT];
        }

        $response['nc_count'] = $this->getNcCount($merchant);


        $lastestNc = $this->getLatestNc($merchant);

        if (empty($lastestNc) === false)
        {
            $response['nc_submission_date'] = $lastestNc['created_at'];
        }

        return ['clarification_details' => $response];
    }

    public function createMerchantClarificationDetails($merchantId, $input)
    {
        $mutexResource = self::CLARIFICATION_DETAIL_CREATE_MUTEX_PREFIX . $merchantId;

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function() use ($input, $merchantId) {

            $clarificationDetail = new Entity;

            $clarificationDetail->generateId();

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $input[Entity::METADATA] = [
                Constants::NC_COUNT => $this->getNcCount($merchant)
            ];

            $this->trace->info(TraceCode::MERCHANT_CREATE_CLARIFICATION_DETAILS, $input);

            $clarificationDetail->build($input);

            $this->repo->clarification_detail->saveOrFail($clarificationDetail);

            return $clarificationDetail;
        });
    }

    public function getNcCount($merchant)
    {
        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, $statusChangeLogs->toArray());

        return (new Detail\Core)->getStatusChangeCount($statusChangeLogs, Detail\Status::NEEDS_CLARIFICATION);
    }


    public function getLatestNc($merchant)
    {
        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, $statusChangeLogs->toArray());

        return (new Detail\Core)->getLatestStatusChange($statusChangeLogs, Detail\Status::NEEDS_CLARIFICATION);
    }


    public function getCommunicationParams($merchantId)
    {
        $params = [];

        $clarifications = $this->repo->clarification_detail->getByMerchantIdAndStatus($merchantId, Constants::NEEDS_CLARIFICATION);

        foreach ($clarifications as $clarification)
        {

            $groupName = $clarification->getGroupName();

            $groupName = ucwords(str_replace("_", " ", $groupName));

            if (isset($params[$groupName]) === false or
                empty($params[$groupName]) === true)
            {
                $params[$groupName] = $clarification->getAdminComment();
            }

        }

        $this->trace->info(TraceCode::CLARIFICATION_DETAILS_COMMUNICATION_PARAMS, $params);

        return $params;
    }

    public function sendReminderNotification($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $clarificationDetails = (new ClarDetailCore)->getCommunicationParams($merchantId);

        if ($this->hasClarificationDetails($merchantId) === true)
        {
            $args = [
                EscalationsConstant::MERCHANT => $merchant,
                MConstants::PARAMS            => [
                    'clarification_details' => $clarificationDetails,
                    'ncSubmissionDate'      => Carbon::createFromTimestamp(
                        Carbon::now()
                              ->addDays(7)
                              ->getTimestamp(), Timezone::IST)->isoFormat('MMM Do YYYY')
                ]
            ];

            $event = $this->getEventForNcRevampReminderNotification($merchant);

            (new OnboardingNotificationHandler($args))
                ->sendEventNotificationForMerchant($merchantId, $event);
        }
    }


    protected function getEventForNcRevampReminderNotification(Merchant\Entity $merchant)
    {
        $event = '';

        $ncCount = $this->getNcCount($merchant);

        if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === false)
        {
            $event = ($ncCount <= 1) ?
                NCEvents::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER :
                NCEvents::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER;
        }

        else
        {
            if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === true)
            {
                $event = ($ncCount <= 1) ?
                    NCEvents::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER :
                    NCEvents::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER;
            }
            else
            {
                if ($merchant->isActivated() === false)
                {
                    if ((new Detail\Core())->blockMerchantActivations($merchant) === false)
                    {
                        $event = ($ncCount <= 1) ?
                            NCEvents::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER :
                            NCEvents::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER;
                    }
                    else
                    {
                        $event = ($ncCount <= 1) ?
                            NCEvents::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER :
                            NCEvents::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER;
                    }
                }
            }
        }

        return $event;

    }

    public function getSegmentEventParams($merchant)
    {
        try
        {
            $properties = [];

            $isEligibleForNCRevamp = (new ClarDetailService())->isEligibleForRevampNC($merchant->getId());

            if($isEligibleForNCRevamp === true)
            {
                $properties['merchantId'] = $merchant->getId();

                $properties['nc_count'] = $this->getNcCount($merchant);

                $clarificationDetails = $this->repo->clarification_detail->getByMerchantIdAndStatusFromReplica($merchant->getId(), Constants::NEEDS_CLARIFICATION);

                foreach ($clarificationDetails as $clarificationDetail)
                {
                    $params = [];

                    $groupName = $clarificationDetail->getGroupName();

                    $params['admin_email'] = $clarificationDetail->getAdminEmail();

                    $params['admin_comment'] = $clarificationDetail->getAdminComment();

                    $properties[$groupName] = $params;
                }
            }

            $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH, [
                'merchant_id' => $merchant->getId(),
                'nc_fields'   => $properties
            ]);

            return $properties;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH_FAILURE,[
                'merchant_id' => $merchant->getId(),
                'Error Message' => $e->getMessage()
            ]);
        }
    }

    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::MERCHANT_ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::MERCHANT_ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === Merchant\Constants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                $clarificationDetail = $this->repo->clarification_detail->find($data[Entity::ID]);

                if (empty($clarificationDetail) === true)
                {
                    $clarificationDetail = new Entity;

                    $clarificationDetail->generateId();

                    $this->trace->info(TraceCode::MERCHANT_CREATE_CLARIFICATION_DETAILS, $data);

                    $clarificationDetail->build($data);

                    $this->repo->clarification_detail->saveOrFail($clarificationDetail);

                    foreach ($clarificationDetail->getFields() as $fieldName)
                    {
                        $clarificationReasons[$fieldName] = [
                            [
                                "reason_type" => $data[Entity::COMMENT_DATA][Constants::TYPE],
                                "reason_code" => $data[Entity::COMMENT_DATA][Constants::TEXT],
                                "from"        => $data[Entity::MESSAGE_FROM],
                                "is_current"  => true,
                                "nc_count"    => $data[Entity::METADATA][Constants::NC_COUNT]
                            ]
                        ];
                    }

                    if (empty($clarificationReasons) === false)
                    {
                        $activationInput = [
                            "kyc_clarification_reasons" => [
                                "clarification_reasons" => $clarificationReasons
                            ]
                        ];

                        $kycClarificationReasons = (new Merchant\Detail\Core)->getUpdatedKycClarificationReasons($activationInput, $data[Entity::MERCHANT_ID], $data[Entity::MESSAGE_FROM]);

                        if (empty($kycClarificationReasons) === false)
                        {
                            $merchantDetails = $this->repo->merchant_detail->findByPublicId($data[Entity::MERCHANT_ID]);

                            $merchantDetails->setKycClarificationReasons($kycClarificationReasons);

                            $this->repo->saveOrFail($merchantDetails);

                        }
                    }
                }
                else
                {
                    $this->trace->info(TraceCode::MERCHANT_CREATE_CLARIFICATION_DETAILS, $data);

                    $clarificationDetail->edit($data);

                    $this->repo->clarification_detail->saveOrFail($clarificationDetail);

                }
            }
        }
    }
}
