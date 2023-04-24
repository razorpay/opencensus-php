<?php


namespace RZP\Models\BankingAccount\Activation\Notification\Subscriber;

use App;
use Carbon\Carbon;
use Mail;

use RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Services\HubspotClient;
use RZP\Models\BankingAccount\Status;
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

    protected function getStatusChangeProperties(Event $event, array $bankingAccount): array
    {
        if ($this->getChannelName($event) === BankingAccount\Entity::Neostone)
        {
            $properties = $event->getProperties();

            $status = $properties[Constants::NEW_STATUS];

            switch ($status)
            {
                case Status::PICKED:

                    $properties += ['ca_rzp_processing' => 'TRUE'];

                    break;
                case Status::INITIATED:

                    // If stayed in this state for more than 2 days then doc not picked up mail will be sent
                    $properties += ['ca_doc_not_picked_up' => 'TRUE'];

                    break;
                case Status::PROCESSING:

                    $properties += ['ca_bank_processing' => 'TRUE'];

                    break;
                case Status::PROCESSED:

                    $properties += [
                        'ca_account_opened' => 'TRUE',
                        'ca_account_number' => $bankingAccount[BankingAccount\Entity::ACCOUNT_NUMBER],
                        'ca_ifsc_code'      => $bankingAccount[BankingAccount\Entity::ACCOUNT_IFSC]
                    ];

                    break;
                case Status::ACTIVATED:

                    $properties += ['ca_account_activated' => 'TRUE'];

                    break;
                case Status::ARCHIVED:

                    $properties += ['ca_account_archived' => 'TRUE'];

                    break;
                case Status::REJECTED:

                    $properties += ['ca_account_rejected' => 'TRUE'];

                    break;
            }

            return $properties;
        }
        else
        {
            $status = BankingAccount\Status::transformFromInternalToExternal($event->getProperties()[Constants::NEW_STATUS]);
            $statusChangeDatePropertyName = snake_case($status . " Date");

            return [
                self::CA_ONBOARDING_STATUS          => $status,
                // Hubspot has this requirement that date fields are set to UTC midnight of that date.
                $statusChangeDatePropertyName       => Carbon::today()->hour(0)->getTimestamp() * 1000
            ];
        }
    }

    protected function pushEventToHubspot(array $bankingAccount, Event $event)
    {
        $eventProperties = [];

        switch ($event->getName())
        {
            case Event::STATUS_CHANGE:
                $eventProperties += $this->getStatusChangeProperties($event, $bankingAccount);
                break;
            case Event::SUBSTATUS_CHANGE:
                $subStatus = BankingAccount\Status::transformSubStatusFromInternalToExternal($event->getProperties()[Constants::NEW_SUBSTATUS]);
                $eventProperties += [
                    self::CA_ONBOARDING_SUBSTATUS       => $subStatus,
                ];
                break;
            case Event::PERSONAL_DETAILS_FILLED:
            case Event::APPLICATION_RECEIVED:
            case Event::PROCESSING_DISCREPANCY_IN_DOCS:
            case Event::RM_ASSIGNED:
                $eventProperties += $event->getProperties();
                break;
        }

        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findByPublicId($bankingAccount[BankingAccount\Entity::MERCHANT_ID]);

        $this->hubspotService->trackHubspotEvent(
            $merchant->getEmail(),
            $eventProperties);
    }

    public function update(array $bankingAccount, Event $event)
    {
        $this->pushEventToHubspot($bankingAccount, $event);
    }

    /**
     * @param Event $event
     *
     * @return mixed|null
     */
    protected function getChannelName(Event $event)
    {
        $properties = $event->getProperties();
        if (array_key_exists('ca_channel', $properties) === true)
        {
            return $properties['ca_channel'];
        }

        return null;
    }
}
