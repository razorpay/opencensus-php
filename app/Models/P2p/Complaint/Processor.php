<?php

namespace RZP\Models\P2p\Complaint;

use RZP\Events\P2p;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Complaint\Actions;

/**
 * Processor class for complaint
 ** @property Core $core
 */
class Processor extends Base\Processor
{
    /**
     * @param array $input
     *
     * @return array
     */
    public function incomingCallback(array $input): array
    {
        $this->initialize(Action::INCOMING_CALLBACK, $input);

        $complaintInput = $this->input->get(Entity::COMPLAINT);

        $complaintEntity = $this->core->build($complaintInput);

        $this->app['events']->dispatch(new P2p\MerchantComplaintNotification($this->context(), $complaintEntity));

        return $input;
    }

}
