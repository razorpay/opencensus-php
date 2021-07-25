<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Transaction\Core;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\Entity;
use Illuminate\Queue\SerializesModels;
use RZP\Models\P2p\Base\Libraries\Context;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

class TransactionCompleted extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.transaction.completed';
    }

    public function getWebhookPaylaod()
    {
        return $this->getEntity()->toArrayPartner();
    }

    public function getNotificationPayload()
    {
        return;
    }

    public function getReminderPayload()
    {
        $firstTransaction = (new Core)->getFirstTransactionWithStatusAndFlow(
            [Status::PENDING, Status::COMPLETED], Flow::DEBIT
        );

        $transaction = $this->getEntity();

        if ($firstTransaction === null)
        {
            return;
        }

        if ($transaction->getId() === $firstTransaction->getId())
        {
            $device = $this->context->getDevice();

            return [
                'params' => [
                    'handle' => $this->context->getHandle()->getCode(),
                    'entity' => $device->getP2pEntityName(),
                    'id'     => $device->getId(),
                    'action' => Device\Action::DEVICE_COOLDOWN_COMPLETED,
                ],
                'entity' => [
                    'id'            => $transaction->getId(),
                    'type'          => $transaction->getEntityName(),
                ],
                'reminder_data' => [
                    'created_at' => $transaction->getCreatedAt(),
                ]
            ];
        }
    }

    public function postHandle()
    {
        /**
         * @var $transaction Entity
         */
        $transaction = $this->getEntity();

        (new TransactionMetric($transaction, $this->original))->pushCount();
    }

    protected function setOriginal(array $original = null)
    {
        $this->original = $original;

        return $this;
    }
}
