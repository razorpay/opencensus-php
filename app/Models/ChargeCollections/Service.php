<?php

namespace RZP\Models\ChargeCollections;

use RZP\Trace\TraceCode;

use RZP\Models\Base;
use RZP\Models\Transaction;
use RZP\Models\ChargeCollections\ProductCharge\Entity;

class Service extends Base\Service
{
    public function createInternalTransaction(array $input)
    {
        $this->trace->info(TraceCode::CHARGE_COLLECTIONS_TRANSACTION_CREATE_REQUEST, $input);

        (new Validator)->validateInput('create_internal_transaction', $input);

        app('request.ctx')->setLedgerDualWriteFlow(true);

        $entityId = $input[Constants::ENTITY_ID];

        return $this->app['api.mutex']->acquireAndRelease($entityId, function () use ($input) {

            return $this->repo->transaction(function () use ($input) {

                $entityId = $input[Constants::ENTITY_ID];
                $merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);
                $txn = $this->repo->transaction->findByEntityId($entityId, $merchant);

                // if transaction already exists, return
                if (isset($txn) === true)
                {
                    return $txn;
                }

                $charge = new Entity;

                $chargeInput = [
                    Entity::ID          => $entityId,
                    Entity::BASE_AMOUNT => $input[Constants::AMOUNT],
                    Entity::CURRENCY    => $input[Constants::CURRENCY],
                    Entity::AMOUNT      => $input[Constants::AMOUNT],
                    Entity::MERCHANT_ID => $input[Constants::MERCHANT_ID],
                    Entity::IS_REVERSAL => $input[Constants::IS_REVERSAL],
                    Entity::TAX         => $input[Constants::TAX],
                ];
                $charge->fill($chargeInput);
                $charge->merchant()->associate($merchant);

                $txnCore = new Transaction\Core;

                list($txn, $feeSplit) = $txnCore->createTransactionForSource($charge, $input[Constants::JOURNAL_ID]);

                $this->repo->saveOrFail($txn);

                $txnCore->saveFeeDetails($txn, $feeSplit);

                $this->trace->info(TraceCode::CHARGE_COLLECTIONS_TRANSACTION_CREATED,
                    [
                        'charge_id'         => $charge->getId(),
                        'transaction_id'    => $txn->getId(),
                    ]);

                return $txn;
            });
        });
    }
}
