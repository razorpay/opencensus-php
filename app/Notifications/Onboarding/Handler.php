<?php


namespace RZP\Notifications\Onboarding;

use RZP\Trace\TraceCode;
use RZP\Notifications\Channel;
use RZP\Notifications\Factory;
use RZP\Models\Merchant\Entity;
use RZP\Exception\LogicException;
use RZP\Notifications\BaseHandler;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\Detail\BusinessType;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::NEEDS_CLARIFICATION                         => [Channel::SMS, Channel::WHATSAPP],
        Events::UNREGISTERED_SETTLEMENTS_ENABLED            => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_SETTLEMENTS_ENABLED              => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_PAYMENTS_ENABLED                 => [Channel::SMS, Channel::WHATSAPP],
        Events::UNREGISTERED_PAYMENTS_ENABLED               => [Channel::SMS, Channel::WHATSAPP],
        Events::PENNY_TESTING_FAILURE                       => [Channel::SMS, Channel::WHATSAPP],
        Events::ACTIVATED_MCC_PENDING                       => [Channel::WHATSAPP],
        Events::ACTIVATED_MCC_PENDING_SUCCESS               => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_ACTION_REQUIRED       => [Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_SOFT_LIMIT_BREACH     => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::ACTIVATED_MCC_PENDING_HARD_LIMIT_BREACH     => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FUNDS_ON_HOLD                               => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FUNDS_ON_HOLD_REMINDER                      => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::L1_NOT_SUBMITTED_IN_1_DAY                   => [Channel::SMS, Channel::WHATSAPP],
        Events::L1_NOT_SUBMITTED_IN_1_HOUR                  => [Channel::SMS, Channel::WHATSAPP],
        Events::L2_BANK_DETAILS_NOT_SUBMITTED_IN_1_HOUR     => [Channel::SMS, Channel::WHATSAPP],
        Events::L2_AADHAR_DETAILS_NOT_SUBMITTED_IN_1_HOUR   => [Channel::SMS, Channel::WHATSAPP],
        Events::PAYMENTS_ENABLED                            => [Channel::SMS, Channel::WHATSAPP],
        Events::ONBOARDING_VERIFY_EMAIL                     => [Channel::SMS, Channel::WHATSAPP],
        Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::FIRST_PAYMENT_OFFER                         => [Channel::SMS, Channel::WHATSAPP],
        Events::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED      => [Channel::SMS, Channel::WHATSAPP],
        Events::SIGNUP_STARTED_NOTIFY                       => [Channel::SMS, Channel::WHATSAPP],
    ];

    private $activationStatus;

    private $merchant;

    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->merchant = $args['merchant'];

        if (isset($args['activationStatus']) === true)
        {
            $this->activationStatus = $args['activationStatus'];
        }
    }

    public function send()
    {
        $events = $this->getEventForActivationStatus($this->activationStatus, $this->merchant);

        $notificationBlocked = (new PartnerCore())->isSubMerchantNotificationBlocked($this->merchant->id);
        foreach ($events as $event)
        {
            if (empty($event) === false and $notificationBlocked === false)
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
     *
     */
    public function sendEventNotificationForMerchant(string $merchantId, string $event)
    {
        $success = true;

        try
        {
            $notificationBlocked = (new PartnerCore())->isSubMerchantNotificationBlocked($merchantId);

            if ($notificationBlocked === false)
            {
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

    private function getEventForActivationStatus(?string $activationStatus, Entity $merchant)
    {
        $events                  = [];
        $isUnregistered          = BusinessType::isUnregisteredBusiness($merchant->merchantDetail->getBusinessType());
        $currentActivationStatus = $merchant->merchantDetail->getActivationStatus();

        switch ($currentActivationStatus)
        {
            case Status::ACTIVATED_MCC_PENDING:
                array_push($events, Events::ACTIVATED_MCC_PENDING_SUCCESS);
                array_push($events, Events::ACTIVATED_MCC_PENDING_ACTION_REQUIRED);
                break;
            case Status::NEEDS_CLARIFICATION:
                array_push($events, Events::NEEDS_CLARIFICATION);
                break;
            case Status::ACTIVATED:
                if ($isUnregistered or ($activationStatus === Status::INSTANTLY_ACTIVATED))
                {
                    array_push($events, Events::UNREGISTERED_SETTLEMENTS_ENABLED);
                }
                else
                {
                    array_push($events, Events::REGISTERED_SETTLEMENTS_ENABLED);
                }
                break;
            case Status::INSTANTLY_ACTIVATED:
                    array_push($events, Events::PAYMENTS_ENABLED);
                break;
        }

        return $events;
    }

    protected function getSupportedchannels(string $event)
    {
        if (isset(self::SUPPORTED_CHANNELS_FOR_EVENTS[$event]))
        {
            return self::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
        }
        // TODO: throw exception
    }
}
