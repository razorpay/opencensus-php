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

    public function migrateOldAdjustments($file): array
    {
        return $this->core()->migrateOldAdjustments($file);
    }

    public function createReason(array $input): array
    {
        $reason = (new Reason\Core)->create($input);

        return $reason->toArrayPublic();
    }

    public function uploadFiles(string $id, array $files): array
    {
        return (new File\Core)->uploadFiles($id, $files);
    }
}
