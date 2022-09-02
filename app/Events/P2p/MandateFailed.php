<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Mandate\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

/**
 * This is the method to update the mandate status to be failed
 * Class MandateFailed
 *
 * @package RZP\Events\P2p
 */
class MandateFailed extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.mandate.failed';
    }

    public function getWebhookPaylaod()
    {
        return $this->getEntity()->toArrayPublic();
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
