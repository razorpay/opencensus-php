<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Transaction\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

class TransactionFailed extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.transaction.failed';
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
        return;
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
