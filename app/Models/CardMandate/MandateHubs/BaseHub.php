<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\CardMandate;

abstract class BaseHub extends Base\Core
{
    abstract public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate;
    abstract public function CancelMandate(CardMandate\Entity $cardMandate): ?Mandate;
    abstract public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment);
    abstract public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment);
    abstract public function getRedirectResponseIfApplicable(CardMandate\Entity $cardMandate, Payment\Entity $payment);
    abstract public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [], $forceCard = true);
    abstract public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification;
    abstract public function updateTokenisedCardTokenInMandate(CardMandate\Entity $cardMandate, $input);
}
