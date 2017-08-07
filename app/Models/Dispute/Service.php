<?php

namespace RZP\Models\Dispute;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input, string $paymentId): array
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $reason = $this->repo->dispute_reason->findOrFail($input[Entity::REASON_ID]);

        $dispute = $this->core()->create($input, $payment, $reason);

        return $dispute->toArrayPublic();
    }
}
