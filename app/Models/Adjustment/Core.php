<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    public function createAdjustment(array $input, $merchant): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        $adj = (new Adjustment\Entity)->build($input);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        return $this->transaction([$this, 'createAdjInTransaction'], $adj, $merchant);
    }

    public function createFeesAdjustment(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::FEE_ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        (new Validator)->validateInput('fee_adjustment', $input);

        // Create input for adjustment
        $adjInput = $input;

        $amount = $adjInput[Entity::AMOUNT] ?? 0;

        $tax =  $adjInput[MerchantInvoice\Entity::TAX] ?? 0;

        $adjInput[Entity::AMOUNT] = $amount + $tax;

        $adj = (new Adjustment\Entity)->build($adjInput);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        // 1. Create adjustment
        // 2. Create Invoice entity for adjustment
        $adjustment = $this->repo->transaction(function() use ($adj, $merchant, $input)
        {
            $adjustment = $this->createAdjInTransaction($adj, $merchant);

            (new Merchant\Invoice\Core)->createAdjustmentInvoiceEntity($adj, $input);

            return $adjustment;
        });

        return $adjustment;
    }

    public function createDisputeAdjustment(array $input, Dispute\Entity $dispute): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_ADJUSTMENT_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $dispute->getMerchantId()
            ]);

        $adjustment = $this->createAdjustment($input, $dispute->merchant);

        $adjustment->entity()->associate($dispute);

        $this->repo->saveOrFail($adjustment);

        return $adjustment;
    }

    protected function createAdjInTransaction($adj, $merchant): Entity
    {
        $this->repo->assertTransactionActive();

        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($adj);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }
}
