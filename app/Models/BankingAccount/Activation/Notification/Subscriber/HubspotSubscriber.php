<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use App;
use Carbon\Carbon;
use Mail;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Services\HubspotClient;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Constants;


class HubspotSubscriber extends Base
{
    protected $name = 'hubspot';

    /** @var HubspotClient $hubspotClient */
    protected $hubspotService;

    // hubspot properties
    const CA_ONBOARDING_STATUS = 'ca_onboarding_status';
    const CA_ONBOARDING_SUBSTATUS = 'ca_onboarding_substatus';

    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->hubspotService = $app->hubspot;

        parent::__construct();
    }

    protected function getStatusChangeProperties(Event $event)
    {
        $status = BankingAccount\Status::transformFromInternalToExternal($event->getProperties()[Constants::NEW_STATUS]);
        $statusChangeDatePropertyName = snake_case($status . " Date");

        return [
            self::CA_ONBOARDING_STATUS          => $status,
            // Hubspot has this requirement that date fields are set to UTC midnight of that date.
            $statusChangeDatePropertyName       => Carbon::today()->hour(0)->getTimestamp() * 1000
        ];
    }

    protected function pushEventToHubspot(BankingAccount\Entity $bankingAccount, Event $event)
    {
        $eventProperties = [];

        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                $eventProperties += $this->getStatusChangeProperties($event);
                break;
            case Event::SUBSTATUS_CHANGE:
                $subStatus = BankingAccount\Status::transformSubStatusFromInternalToExternal($event->getProperties()[Constants::NEW_SUBSTATUS]);
                $eventProperties += [
                    self::CA_ONBOARDING_SUBSTATUS       => $subStatus,
                ];
                break;
        }

        $this->hubspotService->trackHubspotEvent(
            $bankingAccount->merchant->getEmail(),
            $eventProperties);
    }

    public function update(BankingAccount\Entity $bankingAccount, Event $event)
    {
        $this->pushEventToHubspot($bankingAccount, $event);
    }
}
