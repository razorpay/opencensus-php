<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Dispute\Reason\Network;

class DisputeReason extends Base
{
    public function create(array $attributes = [])
    {
        $defaultValues = [
           'network'             => Network::VISA,
           'gateway_code'        => '8fjf',
           'gateway_description' => 'Fraud on merchant side',
           'code'                => 'KFRER_R',
           'description'         => 'This is a serious fraud',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $disputeReason = $this->createEntity('dispute_reason', $attributes);

        return $disputeReason;
    }
}
