<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\CardMandate;

abstract class BaseHub extends Base\Core
{
    abstract public function RegisterMandate(Payment\Entity $payment): Mandate;
    abstract public function CancelMandate(CardMandate\Entity $cardMandate): Mandate;
    abstract public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment);
    abstract public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment);
    abstract public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, Payment\Entity $payment, $debitTime): Notification;
}
