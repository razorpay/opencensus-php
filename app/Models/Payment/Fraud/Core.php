<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Models\Base;
use RZP\Models\Merchant\Fraud\BulkNotification;

class Core extends Base\Core
{
    public function notifyFraud($fraudEntity)
    {
        $payment = $this->repo->payment->findOrFailPublic($fraudEntity->getPaymentId());

        $fraudRowResult = BulkNotification\Processor::getFraudNotificationRowData($payment, $fraudEntity);

        (new BulkNotification\Freshdesk(new BulkNotification\Entity(), null))->notifySingle([$fraudRowResult], $payment->getMerchantId());
    }

    public function createOrUpdateFraudEntity($input): array
    {
        (new Validator())->validateInput('create_or_update_entity', $input);

        $paymentId = $input[Entity::PAYMENT_ID];

        $reportedBy = $input[Entity::REPORTED_BY];

        $fraudEntity = $this->repo->payment_fraud->fetch([
            Entity::PAYMENT_ID  => $input[Entity::PAYMENT_ID],
            Entity::REPORTED_BY => $input[Entity::REPORTED_BY],
        ])->first();

        if (isset($fraudEntity) === true)
        {
            $this->repo->payment_fraud->update($paymentId, $reportedBy, $input);

            return [false, $fraudEntity->refresh()];
        }
        else
        {
            $fraudEntity = (new Entity)->build($input);

            $this->repo->payment_fraud->saveOrFail($fraudEntity);

            return [true, $fraudEntity];
        }
    }
}
