<?php


namespace RZP\Models\BankingAccount\Activation\Notification;

use Mail;

use Monolog\Logger;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\OpsSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\SpocSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\HubspotSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\MidOfficeManagerSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\MidOfficePocSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\SalesforceSubscriber;

class Notifier extends Base\Core
{
    /**
     * @var
     */
    protected $eventSubscribersConfig;

    public function __construct(array $eventSubscribersConfig = null)
    {
        $this->eventSubscribersConfig = $eventSubscribersConfig ?? self::getDefaultEventSubscriberConfig();

        parent::__construct();
    }

    public static function getDefaultEventSubscriberConfig()
    {
        $spocSubscriber = new SpocSubscriber();
        $opsSubscriber = new OpsSubscriber();
        $hubspotSubscriber = new HubspotSubscriber();
        $bankMidOfficeManagerSubscriber = new MidOfficeManagerSubscriber();
        $bankMidOfficePocSubscriber = new MidOfficePocSubscriber();
        $salesforceSubscriber = new SalesforceSubscriber();

        return [
            Event::STATUS_CHANGE => [
                $spocSubscriber,
                $opsSubscriber,
                $salesforceSubscriber,
                $hubspotSubscriber
            ],
            Event::SUBSTATUS_CHANGE => [
                $salesforceSubscriber,
                $hubspotSubscriber
            ],
            Event::ASSIGNEE_CHANGE => [
                $spocSubscriber,
                $opsSubscriber
            ],
            Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY => [
                $opsSubscriber
            ],
            Event::APPLICATION_RECEIVED => [
                $hubspotSubscriber
            ],
            Event::PERSONAL_DETAILS_FILLED => [
                $hubspotSubscriber
            ],
            Event::PROCESSING_DISCREPANCY_IN_DOCS => [
                $hubspotSubscriber
            ],
            Event::RM_ASSIGNED => [
                $hubspotSubscriber
            ],
            Event::BANK_PARTNER_ASSIGNED => [
                $bankMidOfficeManagerSubscriber
            ],
            Event::BANK_PARTNER_POC_ASSIGNED => [
                $bankMidOfficePocSubscriber
            ]
        ];
    }

    protected function prepareEventProperties(array $bankingAccount, string $eventName)
    {
        switch ($eventName)
        {
            case Event::STATUS_CHANGE:
                return [
                    Constants::NEW_STATUS => $bankingAccount[BankingAccount\Entity::STATUS]
                ];
            case Event::SUBSTATUS_CHANGE:
                return [
                    Constants::NEW_SUBSTATUS => $bankingAccount[BankingAccount\Entity::SUB_STATUS] ?? null
                ];
            case Event::ASSIGNEE_CHANGE:
                return [
                    Constants::NEW_ASSIGNEE_TEAM => $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Activation\Detail\Entity::ASSIGNEE_TEAM],
                    Constants::NEW_ASSIGNEE_NAME => $bankingAccount[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][BankingAccount\Activation\Detail\Entity::ASSIGNEE_NAME],
                ];
            case Event::PERSONAL_DETAILS_FILLED:
                return ['ca_form_submit' => 'TRUE'];
            case Event::APPLICATION_RECEIVED:
                return ['ca_application_received' => 'TRUE'];
            case Event::PROCESSING_DISCREPANCY_IN_DOCS:
                return ['ca_discrepancy_in_doc' => 'TRUE'];
            case Event::RM_ASSIGNED:
                return ['ca_bank_rm_assigned' => 'TRUE'];
        }

        return [];
    }

    protected function prepareEvent(array $bankingAccount, string $eventName, string $eventType, array $eventProperties)
    {
        $allEventProperties = $this->prepareEventProperties($bankingAccount, $eventName);
        $allEventProperties = array_merge($allEventProperties, $eventProperties);

        return new Event($eventName, $eventType, $allEventProperties);
    }

    public function notify(array $bankingAccount, string $eventName, string $eventType = Event::INFO, array $eventProperties = [])
    {
        try
        {
            $event = $this->prepareEvent($bankingAccount, $eventName, $eventType, $eventProperties);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_EVENT_NOTIFY,
                [
                    'event' => $event->toArray(),
                    'banking_account_id' => $bankingAccount[BankingAccount\Entity::ID]
                ]);

            $subscribers = $this->eventSubscribersConfig[$event->getName()] ?? [];

            foreach ($subscribers as $subscriber)
            {
                try
                {
                    $subscriber->update($bankingAccount, $event);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Logger::ERROR,
                        TraceCode::BANKING_ACCOUNT_EVENT_NOTIFY_FAILED,
                        [
                            'banking_account_id' => $bankingAccount[BankingAccount\Entity::ID],
                            'eventName'          => $eventName,
                            'eventType'          => $eventType,
                            'eventProperties'    => $eventProperties,
                            'subscriberType'     => $subscriber->getSubscriberName(),
                        ]);
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::BANKING_ACCOUNT_EVENT_NOTIFY_FAILED,
                [
                    'banking_account_id' => $bankingAccount[BankingAccount\Entity::ID],
                    'eventName'          => $eventName,
                    'eventType'          => $eventType,
                    'eventProperties'    => $eventProperties
                ]);
        }

    }
}
