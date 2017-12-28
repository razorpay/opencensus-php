<?php

namespace RZP\Models\Dispute;

use Request;
use RZP\Models\Base;
use RZP\Models\Dispute\File\Entity as DisputeFileEntity;

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
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

        if ($this->auth->isAdminAuth())
        {
            $dispute = $this->core()->update($dispute, $input);

            return $dispute->toArrayAdmin();
        }
        else if (($this->auth->isPrivateAuth() === true) or
            ($this->auth->isProxyAuth() === true))
        {
            return $this->updateForMerchant($dispute, $input);
        }
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

    protected function updateForMerchant(Entity $dispute, array $input): array
    {
        $response = [];

        $files = [];

        $fileCore = new File\Core();

        if (array_key_exists(DisputeFileEntity::FILES, $input) === true)
        {
            $files = $input[DisputeFileEntity::FILES];

            $files = $fileCore->checkFileInput($files);

            unset($input[DisputeFileEntity::FILES]);
        }

        if (empty($input) === false)
        {
            $dispute = $this->core()->updateForMerchant($dispute, $input);
        }

        if (empty($files) === false )
        {
            $response['files'] = $fileCore->uploadFiles($dispute, $files);
        }

        $response['dispute'] = $dispute->toArrayPublic();

        return $response;
    }
}
