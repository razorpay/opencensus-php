<?php

namespace RZP\Models\Dispute;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input, string $paymentId): array
    {
        $payment = $this->repo->payment->findByPublicId($paymentId);

        (new Validator)->validateInputBeforeBuild($input);

        $reason = $this->repo->dispute_reason->findOrFail($input[Entity::REASON_ID]);

        $dispute = $this->core()->create($payment, $reason, $input);

        return $dispute->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $dispute = $this->repo->dispute->findByPublicId($id);

        $dispute = $this->core()->update($dispute, $input);

        return $dispute->toArrayPublic();
    }
}
