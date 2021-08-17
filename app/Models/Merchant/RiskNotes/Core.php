<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function createRiskNote($merchantId, $input)
    {
        $adminId = $this->app['basicauth']->getAdmin()->getId();

        $input = [
            Entity::NOTE          => $input[Entity::NOTE],
            Entity::ADMIN_ID      => $adminId,
            Entity::MERCHANT_ID   => $merchantId,
        ];

        $riskNote = (new Entity)->build($input);

        // will throw an error if no merchant found.
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $riskNote->merchant()->associate($merchant);

        $this->repo->merchant_risk_note->saveOrFail($riskNote);

        return $riskNote;
    }

    public function getAll($merchantId, $input)
    {
        $riskNotes = $this->repo->merchant_risk_note->fetch($input, $merchantId);

        return $riskNotes;
    }

    public function delete(string $merchantId, string $id)
    {
        $adminId = $this->app['basicauth']->getAdmin()->getId();

        $riskNote = $this->repo->merchant_risk_note->findByIdAndMerchantId($id, $merchantId);

        $riskNote = $riskNote->first();

        $riskNote->setDeletedBy($adminId);

        $riskNote->setDeletedAt();

        // using saveOrFail instead of deleteOrFail as we have to set DELETED_BY also
        $this->repo->merchant_risk_note->saveOrFail($riskNote);
    }
}
