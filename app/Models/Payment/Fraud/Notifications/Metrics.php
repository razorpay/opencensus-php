<?php

namespace RZP\Models\Payment\Fraud\Notifications;

class Metrics
{
    // Counters type metric names
    const FRAUD_NOTIFICATION_NEW_TICKET_CREATED       = 'fraud_notification_new_ticket_created';
    const FRAUD_NOTIFICATION_REPLY_ON_EXISTING_TICKET = 'fraud_notificationh_reply_on_existing_ticket';
    const FRAUD_NOTIFICATION_FAILED                   = 'fraud_notification_failed';
}
