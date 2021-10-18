<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function createRiskNote($merchantId, $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $input = [
            Entity::NOTE          => $input[Entity::NOTE],
        ];

        $riskNote = (new Entity)->build($input);

        $riskNote->merchant()->associate($merchant);

        $riskNote->admin()->associate($admin);

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
        $admin = $this->app['basicauth']->getAdmin();

        $riskNote = $this->repo->merchant_risk_note->findByIdAndMerchantId($id, $merchantId);

        $riskNote->deletedByAdmin()->associate($admin);

        $riskNote->setDeletedAt();

        // using saveOrFail instead of deleteOrFail as we have to set DELETED_BY also
        $this->repo->merchant_risk_note->saveOrFail($riskNote);
    }
}
