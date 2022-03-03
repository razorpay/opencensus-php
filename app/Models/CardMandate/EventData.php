<?php

namespace RZP\Models\CardMandate;

class EventData {
    protected $eventData;

    function __construct()
    {
        $this->eventData = [];
    }

    public function withCardMandate($cardMandate) : EventData
    {
        if ($cardMandate !== null)
        {
            $this->eventData = array_merge($this->eventData, [
                'mandate_id'     => $cardMandate->getId(),
                'mandate_hub'    => $cardMandate->getMandateHub(),
                'mandate_status' => $cardMandate->getStatus(),
            ]);
        }

        return $this;
    }

    public function withCardMandateNotification($cardMandateNotification) : EventData
    {
        if ($cardMandateNotification !== null)
        {
            $this->eventData = array_merge($this->eventData, [
                'notification_id'          => $cardMandateNotification->getId(),
                'invoice_id'               => $cardMandateNotification->getNotificationId(),
                'notification_status'      => $cardMandateNotification->getStatus(),
                'notification_reminder_id' => $cardMandateNotification->getReminderId(),
            ]);
        }

        return $this;
    }

    public function toArray() : array
    {
        return $this->eventData;
    }
}
