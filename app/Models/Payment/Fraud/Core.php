<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Services\Shield;
use RZP\Models\Merchant\Fraud\BulkNotification;
use RZP\Models\Merchant\Fraud\BulkNotification\Constants;

class Core extends Base\Core
{
    public function notifyFraud($fraudEntity)
    {
        $payment = $this->repo->payment->findOrFailPublic($fraudEntity->getPaymentId());

        $fraudRowResult = BulkNotification\Processor::getFraudNotificationRowData($payment, $fraudEntity);

        if (in_array($fraudEntity->getReportedBy(), Constants::CARD_NETWORK_SOURCES, true) === true)
        {
            $isCardNetworkRequest = true;
            $fraudRowResult[Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION] = Constants::SOURCE_BANK;
        }
        else
        {
            $isCardNetworkRequest = false;
            $fraudRowResult[Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION] = Constants::SOURCE_CYBERCELL;
        }

        (new BulkNotification\Freshdesk(new BulkNotification\Entity(), null))->notifySingle([$fraudRowResult], $payment->getMerchantId(), $isCardNetworkRequest);
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

            $event = $this->app['diag']->trackPaymentFraudEvent(EventCode::PAYMENT_FRAUD_CREATED, $fraudEntity);

            (new Shield($this->app))->enqueueShieldEvent($event);

            return [true, $fraudEntity];
        }
    }
}
