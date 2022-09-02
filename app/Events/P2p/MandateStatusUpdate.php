<?php

namespace RZP\Events\P2p;

use RZP\Models\P2p\Mandate\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

/**
 * This is the event to update the status of the mandate
 * Class MandateStatusUpdate
 *
 * @package RZP\Events\P2p
 */
class MandateStatusUpdate extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.mandate.status.update';
    }

    public function getWebhookPaylaod()
    {
        return $this->getEntity()->toArrayPublic();
    }

    public function getNotificationPayload()
    {
       return ;
    }

    public function getReminderPayload()
    {
        return;
    }

    public function postHandle()
    {
        /**
         * @var $mandate Entity
         */
        $mandate = $this->getEntity();

        (new TransactionMetric($mandate, $this->original))->pushCount();
    }

    protected function setOriginal(array $original = null)
    {
        $this->original = $original;

        return $this;
    }
}
