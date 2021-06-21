<?php


namespace RZP\Notifications\Onboarding;

use RZP\Notifications\Channel;
use RZP\Models\Merchant\Entity;
use RZP\Notifications\BaseHandler;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\BusinessType;

class Handler extends BaseHandler
{
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        Events::NEEDS_CLARIFICATION                  => [Channel::SMS, Channel::WHATSAPP],
        Events::UNREGISTERED_SETTLEMENTS_ENABLED     => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_SETTLEMENTS_ENABLED       => [Channel::SMS, Channel::WHATSAPP],
        Events::REGISTERED_PAYMENTS_ENABLED          => [Channel::SMS, Channel::WHATSAPP],
        Events::UNREGISTERED_PAYMENTS_ENABLED        => [Channel::SMS, Channel::WHATSAPP],
        Events::PENNY_TESTING_FAILURE                => [Channel::SMS, Channel::WHATSAPP],
        Events::ACTIVATED_MCC_PENDING                => [Channel::WHATSAPP],

        Events::PAYMENTS_LIMIT_BREACH_AFTER_L1_SUBMISSION   => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL],
        Events::PAYMENTS_BREACH_AFTER_L1_SUBMISSION_BLOCKED => [Channel::SMS, Channel::WHATSAPP, Channel::EMAIL]
    ];

    private $activationStatus;
    private $merchant;

    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->merchant = $args['merchant'];

        if(isset($args['activationStatus']) === true)
        {
            $this->activationStatus = $args['activationStatus'];
        }
    }

    public function send()
    {
        $event = $this->getEventForActivationStatus($this->activationStatus, $this->merchant);

        if(empty($event) === false)
        {
            $this->sendForEvent($event);
        }
    }

    private function getEventForActivationStatus(?string $activationStatus, Entity $merchant)
    {
        $event = null;
        $isUnregistered = BusinessType::isUnregisteredBusiness($merchant->merchantDetail->getBusinessType());
        $currentActivationStatus = $merchant->merchantDetail->getActivationStatus();

        switch ($currentActivationStatus)
        {
            case Status::ACTIVATED_MCC_PENDING:
                $event = Events::ACTIVATED_MCC_PENDING;
                break;
            case Status::NEEDS_CLARIFICATION:
                $event = Events::NEEDS_CLARIFICATION;
                break;
            case Status::ACTIVATED:
                if($isUnregistered or ($activationStatus === Status::INSTANTLY_ACTIVATED))
                {
                    $event = Events::UNREGISTERED_SETTLEMENTS_ENABLED;
                }
                else
                {
                    $event = Events::REGISTERED_SETTLEMENTS_ENABLED;
                }
                break;
            case Status::INSTANTLY_ACTIVATED:
                if($isUnregistered)
                {
                    $event = Events::UNREGISTERED_PAYMENTS_ENABLED;
                }
                else
                {
                    $event = Events::REGISTERED_PAYMENTS_ENABLED;
                }
                break;
        }

        return $event;
    }

    protected function getSupportedchannels(string $event)
    {
        if(isset(self::SUPPORTED_CHANNELS_FOR_EVENTS[$event]))
        {
            return self::SUPPORTED_CHANNELS_FOR_EVENTS[$event];
        }
        // TODO: throw exception
    }
}
