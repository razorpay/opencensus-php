<?php

namespace RZP\Notifications\Onboarding;

use App;
use Carbon\Carbon;

use RZP\Constants\Entity as E;
use RZP\Events\Event;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Notifications\Channel;
use RZP\Models\Merchant\Entity;
use RZP\Notifications\BaseHandler;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MCore;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\AccessMap\Repository as AccessMapRepo;
use RZP\Models\ClarificationDetail\Core as ClarificationDetailsCore;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [

        Events::NEEDS_CLARIFICATION                           => [Channel::SMS, Channel::WHATSAPP],
        Events::EDD_PENDING                                   => [Channel::EMAIL],
        Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::NC_COUNT_1_PAYMENTS_NOT_LIVE                  => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE     => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_PAYMENTS_NOT_LIVE                  => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER       => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER   => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                    => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER       => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER   => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                    => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_1_ONBOARDING_PAUSE                            => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_ONBOARDING_PAUSE                            => [Channel::EMAIL, Channel::WHATSAPP],
        Events::NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => [Channel::EMAIL, Channel::WHATSAPP],

        Events::UNREGISTERED_SETTLEMENTS_ENABLED                     => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_SETTLEMENTS_ENABLED                       => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_PAYMENTS_ENABLED                          => [Channel::SMS, Channel::WHATSAPP],
        Events::UNREGISTERED_PAYMENTS_ENABLED                        => [Channel::SMS, Channel::WHATSAPP],
        Events::PENNY_TESTING_FAILURE                                => [Channel::SMS, Channel::WHATSAPP],
        Events::ACTIVATED_MCC_PENDING                                => [Channel::WHATSAPP],
        Events::ACTIVATED_MCC_PENDING_SUCCESS                        => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_ACTION_REQUIRED                => [Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH              => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH              => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FUNDS_ON_HOLD                                        => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FUNDS_ON_HOLD_REMINDER                               => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::DOWNLOAD_MERCHANT_WEBSITE_SECTION                    => [Channel::EMAIL, Channel::WHATSAPP],
        Events::WEBSITE_SECTION_PUBLISHED                            => [Channel::EMAIL, Channel::WHATSAPP],
        Events::WEBSITE_ADHERENCE_SOFT_NUDGE                         => [Channel::EMAIL, Channel::WHATSAPP],
        Events::WEBSITE_ADHERENCE_GRACE_PERIOD_REMINDER              => [Channel::EMAIL],
        Events::WEBSITE_ADHERENCE_HARD_NUDGE                         => [Channel::EMAIL, Channel::WHATSAPP, Channel::SMS],
        Events::L1_NOT_SUBMITTED_IN_1_DAY                            => [Channel::SMS, Channel::WHATSAPP],
        Events::L1_NOT_SUBMITTED_IN_1_HOUR                           => [Channel::SMS, Channel::WHATSAPP],
        Events::L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR              => [Channel::SMS, Channel::WHATSAPP],
        Events::L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR            => [Channel::SMS, Channel::WHATSAPP],
        Events::PAYMENTS_ENABLED                                     => [Channel::SMS, Channel::WHATSAPP],
        Events::ONBOARDING_VERIFY_EMAIL                              => [Channel::SMS, Channel::WHATSAPP],
        Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION            => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED          => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FIRST_PAYMENT_OFFER                                  => [Channel::WHATSAPP],
        Events::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED               => [Channel::SMS, Channel::WHATSAPP],
        Events::SIGNUP_STARTED_NOTIFY                                => [Channel::SMS, Channel::WHATSAPP],

        // partner submerchant email events
        Events::PARTNER_ADDED_SUBMERCHANT                            => [Channel::SMS, Channel::WHATSAPP],
        Events::PARTNER_ADDED_SUBMERCHANT_FOR_X                      => [Channel::SMS, Channel::WHATSAPP],
        Events::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS    => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PARTNER_SUBMERCHANT_KYC_ACCESS_APPROVED              => [Channel::SMS, Channel::WHATSAPP],
        Events::PARTNER_SUBMERCHANT_KYC_ACCESS_REJECTED              => [Channel::SMS, Channel::WHATSAPP],
        Events::PARTNER_SUBMERCHANT_NEEDS_CLARIFICATION              => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED                 => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED   => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],

        Events::PARTNER_SUBMERCHANT_NC_COUNT_ONBOARDING_PAUSE               => [Channel::EMAIL, Channel::WHATSAPP],
        Events::PARTNER_SUBMERCHANT_NC_COUNT_PAYMENTS_LIVE_SETTLEMENTS_LIVE => [Channel::EMAIL, Channel::WHATSAPP],
        Events::PARTNER_SUBMERCHANT_NC_COUNT_PAYMENTS_NOT_LIVE              => [Channel::EMAIL, Channel::WHATSAPP],

        Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE       => [Channel::EMAIL, Channel::SMS],
        Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE   => [Channel::EMAIL, Channel::SMS],
        Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_NOT_LIVE                    => [Channel::EMAIL, Channel::SMS],
        Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_ONBOARDING_PAUSE                     => [Channel::EMAIL, Channel::SMS],

        // in person events
        Events::IN_PERSON_MERCHANT_UNDER_REVIEW_WITH_DEVICE     => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_UNDER_REVIEW_WITHOUT_DEVICE  => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_ACTIVATED_WITH_DEVICE        => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_KYC_QUALIFIED_WITH_DEVICE    => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_KYC_QUALIFIED_WITHOUT_DEVICE => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_REJECTED_WITH_DEVICE         => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_REJECTED_WITHOUT_DEVICE      => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_NC_COUNT_1_WITH_DEVICE       => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_NC_COUNT_1_WITHOUT_DEVICE    => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_NC_COUNT_2_WITH_DEVICE       => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],
        Events::IN_PERSON_MERCHANT_NC_COUNT_2_WITHOUT_DEVICE    => [Channel::EMAIL,Channel::SMS,Channel::WHATSAPP],

        Events::OMNI_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE        => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE        => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE    => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE    => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_PAYMENTS_NOT_LIVE                     => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_NOT_LIVE                     => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_ONBOARDING_PAUSE                      => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_ONBOARDING_PAUSE                      => [Channel::EMAIL],

        Events::OMNI_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE_REMINDER     => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE_REMINDER => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_PAYMENTS_NOT_LIVE_REMINDER                  => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_PAYMENTS_NOT_LIVE_REMINDER                  => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_1_ONBOARDING_PAUSE_REMINDER                   => [Channel::EMAIL],
        Events::OMNI_NC_COUNT_2_ONBOARDING_PAUSE_REMINDER                   => [Channel::EMAIL],
    ];
    private $activationStatus;
    private $posActivationStatus;

    private $merchant;

    public function __construct(array $args, array $files = null)
    {
        parent::__construct($args, $files);

        $this->merchant = $args['merchant'];

        if (isset($args['activationStatus']) === true)
        {
            $this->activationStatus = $args['activationStatus'];
        }

        if (isset($args['posActivationStatus']) === true)
        {
            $this->posActivationStatus = $args['posActivationStatus'];
        }

    }

    public function send()
    {
        $events = $this->getEventForActivationStatus($this->activationStatus, $this->merchant);

        if ((new PartnerCore())->isSubMerchantNotificationBlocked($this->merchant->id) === true)
        {
            return;
        }

        foreach ($events as $event)
        {
            if (empty($event) === false)
            {
                $this->sendForEvent($event);
            }
        }
    }

    public function sendInPersonNotifications()
    {
        $events = $this->getInPersonEventForActivationStatus($this->posActivationStatus, $this->merchant);

        foreach ($events as $event)
        {
            if (empty($event) === false)
            {
                $this->sendForEvent($event);
            }
        }
    }

    /**
     * This method is responsible for sending notification through various channels
     * depending on the event.
     *
     * @param string $merchantId
     * @param string $event
     * @return bool
     */
    public function sendEventNotificationForMerchant(string $merchantId, string $event): bool
    {
        $success = true;

        try
        {
            $notificationBlocked = (new PartnerCore())->isSubMerchantNotificationBlocked($merchantId);

            if ($notificationBlocked === false)
            {
                self::updateNCUrlIfApplicable($merchantId, $event, $this->args);

                $this->sendForEvent($event);
            }
        }
        catch (\Exception $e)
        {
            $success = false;

            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'merchant' => $merchantId,
                'type'     => 'sendNotification',
                'error'    => $e->getMessage(),
                'event'    => $event
            ]);
        }

        return $success;
    }

    private function getNCCommunicationEvent(Entity $merchant): array
    {
        $events = [];

        $doesV3Exist = (new ClarificationDetailsCore)->hasClarificationDetails($merchant->getId());

        $statusChangeLogs = (new MCore)->getActivationStatusChangeLog($merchant);

        $ncCount = (new DetailCore)->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        $clarificationDetails = [];

        if ($doesV3Exist === true)
        {
            $clarificationDetails = (new ClarificationDetailsCore)->getCommunicationParams($merchant->getId());

            if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === false)
            {
                $events[] = ($ncCount <= 1) ? Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE : Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_LIVE;

                $events[] = Events::PARTNER_SUBMERCHANT_NC_COUNT_PAYMENTS_LIVE_SETTLEMENTS_LIVE;

            }
            else
            {
                if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === true)
                {
                    $events[] = ($ncCount <= 1) ? Events::NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE : Events::NC_COUNT_2_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE;

                    $events[] = Events::PARTNER_SUBMERCHANT_NC_COUNT_PAYMENTS_NOT_LIVE;
                }
                else
                {
                    if ($merchant->isActivated() === false)
                    {
                        if ((new DetailCore())->blockMerchantActivations($merchant) === false)
                        {
                            $events[] = ($ncCount <= 1) ? Events::NC_COUNT_1_PAYMENTS_NOT_LIVE : Events::NC_COUNT_2_PAYMENTS_NOT_LIVE;

                            $events[] = Events::PARTNER_SUBMERCHANT_NC_COUNT_PAYMENTS_NOT_LIVE;
                        }
                        else
                        {
                            // merchant new communications while onboarding is paused
                            $events[] = ($ncCount <= 1) ? Events::NC_COUNT_1_ONBOARDING_PAUSE : Events::NC_COUNT_2_ONBOARDING_PAUSE;

                            $events[] = Events::PARTNER_SUBMERCHANT_NC_COUNT_ONBOARDING_PAUSE;
                        }

                    }
                }
            }
        }
        else
        {
            $events[] = Events::NEEDS_CLARIFICATION;
        }

        $this->args[MConstants::PARAMS]['clarification_details'] = $clarificationDetails;

        $this->args[MConstants::PARAMS]['ncSubmissionDate'] = Carbon::createFromTimestamp(
            Carbon::now()
                  ->addDays(7)
                  ->getTimestamp(), Timezone::IST)->isoFormat('MMM Do YYYY');

        $this->args[MConstants::PARAMS][Constants::NC_URL] = self::getUrlForNCEvent($merchant);

        return $events;
    }

    private function getNCCommunicationEventForPOSPartner(Entity $merchant): array
    {
        $events = [];

        if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === false)
        {
            $events[] = Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_LIVE;
        }
        else
        {
            if ($merchant->isActivated() === true and $merchant->isFundsOnHold() === true)
            {
                $events[] = Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_LIVE_SETTLEMENTS_NOT_LIVE;
            }
            else
            {
                if ($merchant->isActivated() === false)
                {
                    if ((new DetailCore())->blockMerchantActivations($merchant) === false)
                    {
                        $events[] = Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_PAYMENTS_NOT_LIVE;
                    }
                    else
                    {
                        // merchant new communications while onboarding is paused
                        $events[] = Events::PARTNER_SUBMERCHANT_POS_NC_COUNT_1_ONBOARDING_PAUSE;
                    }
                }
            }
        }

        return $events;
    }

    private function getInPersonNCCommunicationEvent(Entity $merchant, Bool $isDeviceOrdered): array
    {
        $events = [];

        $doesV3Exist = (new ClarificationDetailsCore)->hasClarificationDetails($merchant->getId());

        $statusChangeLogs = (new MCore)->getActivationStatusChangeLog($merchant);

        $ncCount = (new DetailCore)->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        $clarificationDetails = [];

        if ($doesV3Exist === true)
        {
            $clarificationDetails = (new ClarificationDetailsCore)->getCommunicationParams($merchant->getId());

            if ($isDeviceOrdered == true)
            {
                $events[] = ($ncCount <= 1) ? Events::IN_PERSON_MERCHANT_NC_COUNT_1_WITH_DEVICE : Events::IN_PERSON_MERCHANT_NC_COUNT_2_WITH_DEVICE;
            }
            else
            {
                $events[] = ($ncCount <= 1) ? Events::IN_PERSON_MERCHANT_NC_COUNT_1_WITHOUT_DEVICE : Events::IN_PERSON_MERCHANT_NC_COUNT_2_WITHOUT_DEVICE;
            }

            $posPartnerEvents = $this->getNCCommunicationEventForPOSPartner($merchant);
            $events = array_merge($events, $posPartnerEvents);
        }
        else
        {
            $events[] = Events::NEEDS_CLARIFICATION;
        }

        $this->args[MConstants::PARAMS]['clarification_details'] = $clarificationDetails;

        $this->args[MConstants::PARAMS]['ncSubmissionDate'] = Carbon::createFromTimestamp(
            Carbon::now()
                ->addDays(7)
                ->getTimestamp(), Timezone::IST)->isoFormat('MMM Do YYYY');

        $this->args[MConstants::PARAMS][Constants::NC_URL] = self::getUrlForNCEvent($merchant);

        return $events;
    }

    private function getEventForActivationStatus(?string $activationStatus, Entity $merchant): array
    {
        $events                     = [];
        $isUnregistered             = BusinessType::isUnregisteredBusiness($merchant->merchantDetail->getBusinessType());
        $currentActivationStatus    = $merchant->merchantDetail->getActivationStatus();
        $isEasyEligibleOnboarding   = $merchant->isSignupCampaignAnyOf(DetailConstants::EASY_ELIGIBLE_SIGNUP_CAMPAIGNS);

        switch ($currentActivationStatus)
        {
            case Status::ACTIVATED_MCC_PENDING:
                $events = [
                    Events::ACTIVATED_MCC_PENDING_SUCCESS,
                    Events::ACTIVATED_MCC_PENDING_ACTION_REQUIRED,
                    Events::PARTNER_SUBMERCHANT_ACTIVATED_MCC_PENDING_SUCCESS
                ];

                break;

            case Status::EDD_PENDING:
                $events = [
                    Events::EDD_PENDING
                ];

                break;

            case Status::NEEDS_CLARIFICATION:
                $events = $this->getNCCommunicationEvent($merchant);

                break;

            case Status::ACTIVATED:
                if ($isEasyEligibleOnboarding === true)
                {
                    break;
                }
                else if ($isUnregistered or ($activationStatus === Status::INSTANTLY_ACTIVATED))
                {
                    $events = [
                        Events::UNREGISTERED_SETTLEMENTS_ENABLED,
                        Events::PARTNER_SUBMERCHANT_UNREGISTERED_SETTLEMENTS_ENABLED
                    ];
                }
                else
                {
                    $events = [
                        Events::REGISTERED_SETTLEMENTS_ENABLED,
                        Events::PARTNER_SUBMERCHANT_REGISTERED_SETTLEMENTS_ENABLED
                    ];
                }

                break;

            case Status::INSTANTLY_ACTIVATED:
                if ($isEasyEligibleOnboarding === false)
                {
                    $events = [
                        Events::PAYMENTS_ENABLED,
                        Events::PARTNER_SUBMERCHANT_PAYMENTS_ENABLED
                    ];
                }

                break;
        }

        return $events;
    }

    private function getInPersonEventForActivationStatus(?string $posActivationStatus, Entity $merchant): array
    {
        $events                     = [];
        $isDeviceOrdered            = $this->getDeviceOrderedFlag($merchant);
        $currentActivationStatus = $merchant->merchantDetail->getActivationStatus();

        switch ($posActivationStatus)
        {
            case Status::NEEDS_CLARIFICATION:

                // If online is also in NC, the comms would be sent via PG flow.
                if ($currentActivationStatus !== Status::NEEDS_CLARIFICATION)
                {
                    $events = $this->getInPersonNCCommunicationEvent($merchant, $isDeviceOrdered);
                }

                break;
            case Status::ACTIVATED:
                $events = [
                    Events::IN_PERSON_MERCHANT_ACTIVATED_WITH_DEVICE,
                ];
                break;

            case Status::UNDER_REVIEW:
                if ($isDeviceOrdered === true)
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_UNDER_REVIEW_WITH_DEVICE,
                    ];
                }
                else
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_UNDER_REVIEW_WITHOUT_DEVICE,
                    ];
                }
                break;

            case Status::KYC_QUALIFIED_STB:
                if ($isDeviceOrdered === true)
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_KYC_QUALIFIED_WITH_DEVICE,
                    ];
                }
                else
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_KYC_QUALIFIED_WITHOUT_DEVICE,
                    ];
                }
                break;

            case Status::REJECTED:
                if ($isDeviceOrdered === true)
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_REJECTED_WITH_DEVICE,
                    ];
                }
                else
                {
                    $events = [
                        Events::IN_PERSON_MERCHANT_REJECTED_WITHOUT_DEVICE,
                    ];
                }
                break;
        }

        return $events;
    }

    private function getDeviceOrderedFlag(Entity $merchant): Bool
    {
        $input['merchant_id']     = $merchant->getId();

        $deviceDetailsResponse    = $this->app['MerchantOnboardingProxyController']->handlePGOSProxyRequests(
            'merchant_pos_fetch_all_order',$input, $merchant, true);

        if (empty($deviceDetailsResponse['order_list']) === true)
        {
            return false;
        }

        return true;
    }

    protected function getSupportedChannels(string $event)
    {
        if (isset(self::SUPPORTED_CHANNELS_FOR_EVENTS[$event]))
        {
            return self::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
        }
        // TODO: throw exception
    }

    public static function getUrlForNCEvent(Entity $merchant): string
    {
        return env('EASY_DASHBOARD_URL') . '/' . self::getUrlPathForNCEvent($merchant);
    }

    public static function getUrlPathForNCEvent(Entity $merchant): string
    {
        $isOnboardedViaPhantom = $merchant->isSignupCampaign(DDConstants::PHANTOM_ONBOARDING);

        $ncUrlPath = 'onboarding/needs-clarification';

        $appId = null;
        $partnerId = null;
        $partnerType = null;

        // if the merchant is onboarded via Phantom flow then update the NC url accordingly
        if ($isOnboardedViaPhantom === true)
        {
            $accessMap = (new AccessMapRepo())->getByMerchantId($merchant->getId(), true);

            if (empty($accessMap) === false)
            {
                $partner = optional($accessMap)->entityOwner;

                if (empty($partner) === false)
                {
                    $partnerType = $partner->getPartnerType();

                    if ($partnerType === MerchantConstants::AGGREGATOR)
                    {
                        $partnerId = $partner->getId();

                        $ncUrlPath =  'sub-merchant/onboarding/needs-clarification?partnerId=' . $partnerId;
                    }
                    else if ($partnerType === MerchantConstants::PURE_PLATFORM)
                    {
                        $appId = $accessMap->getEntityId();

                        $ncUrlPath =  'sub-merchant/onboarding/needs-clarification?applicationId=' . $appId;
                    }
                }
            }
        }

        $app = App::getFacadeRoot();

        $app['trace']->info(
            TraceCode::FETCH_NC_URL_FOR_MERCHANT,
            [
                'merchant_id'       => $merchant->getId(),
                'partner_id'        => $partnerId,
                'partner_app_id'    => $appId,
                'partner_type'      => $partnerType,
                'nc_url_path'       => $ncUrlPath
            ]
        );

        return $ncUrlPath;
    }

    // all the events related to needs_clarification activation status should have 'NC_' prefix
    public static function isNCEvent(string $event): bool
    {
        if (str_starts_with($event, Events::NC_EVENTS_PREFIX) === true)
        {
            return true;
        }

        return false;
    }

    public static function updateNCUrlIfApplicable(string $merchantId, string $event, array & $args, bool $shortenUrl = false)
    {
        if (self::isNCEvent($event) !== true)
        {
            return;
        }

        $app = App::getFacadeRoot();

        $ncUrl = $args[MConstants::PARAMS][Constants::NC_URL] ?? null;

        if (empty($ncUrl) === true)
        {
            $merchant = $app['repo']->merchant->findOrFail($merchantId);

            $ncUrl = self::getUrlForNCEvent($merchant);
        }

        if ($shortenUrl === true)
        {
            $ncUrl = $app['elfin']->shorten($ncUrl);
        }

        $args[MConstants::PARAMS][Constants::NC_URL] = $ncUrl;
    }
}
