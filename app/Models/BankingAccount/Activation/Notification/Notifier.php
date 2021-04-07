<?php


namespace RZP\Models\BankingAccount\Activation\Notification;

use Mail;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\OpsSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\SpocSubscriber;
use RZP\Models\BankingAccount\Activation\Notification\Subscriber\HubspotSubscriber;

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

        return [
            Event::STATUS_CHANGE => [
                $spocSubscriber,
                $opsSubscriber,
                $hubspotSubscriber
            ],
            Event::SUBSTATUS_CHANGE => [
                $hubspotSubscriber
            ],
            Event::ASSIGNEE_CHANGE => [
                $spocSubscriber,
                $opsSubscriber
            ],
            Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY => [
                $opsSubscriber
            ]
        ];
    }

    protected function prepareEventProperties(BankingAccount\Entity $bankingAccount, string $eventName)
    {
        switch ($eventName)
        {
            case Event::STATUS_CHANGE:
                return [
                    Constants::NEW_STATUS => $bankingAccount->getStatus()
                ];
            case Event::SUBSTATUS_CHANGE:
                return [
                    Constants::NEW_SUBSTATUS => $bankingAccount->getSubStatus()
                ];
            case Event::ASSIGNEE_CHANGE:
                return [
                    Constants::NEW_ASSIGNEE_TEAM => $bankingAccount->bankingAccountActivationDetails->getAssigneeTeam(),
                    Constants::NEW_ASSIGNEE_NAME => $bankingAccount->bankingAccountActivationDetails->getAssigneeName(),
                ];
        }

        return [];
    }

    protected function prepareEvent(BankingAccount\Entity $bankingAccount, string $eventName, string $eventType, array $eventProperties)
    {
        $allEventProperties = $this->prepareEventProperties($bankingAccount, $eventName);
        $allEventProperties = array_merge($allEventProperties, $eventProperties);

        return new Event($eventName, $eventType, $allEventProperties);
    }

    public function notify(BankingAccount\Entity $bankingAccount, string $eventName, string $eventType = Event::INFO, array $eventProperties = [])
    {
        try
        {
            $event = $this->prepareEvent($bankingAccount, $eventName, $eventType, $eventProperties);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_EVENT_NOTIFY,
                [
                    'event' => $event->toArray(),
                    'banking_account_id' => $bankingAccount->getId()
                ]);

            $subscribers = $this->eventSubscribersConfig[$event->getName()] ?? [];

            foreach ($subscribers as $subscriber)
            {
                $subscriber->update($bankingAccount, $event);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_EVENT_NOTIFY_FAILED,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'eventName'          => $eventName,
                    'eventType'          => $eventType,
                    'eventProperties'    => $eventProperties
                ]);
        }

    }
}
