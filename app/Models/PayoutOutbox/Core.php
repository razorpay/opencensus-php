<?php

namespace RZP\Models\PayoutOutbox;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\PayoutOutbox\Constants as PayoutOutboxConstants;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array $input
     * @return Entity
     */
    public function create(array $input): Entity {
        $input = $this->preProcessInputForCreate($input);

        (new Validator)->validateInput('create', $input);

        $this->trace->info(
            TraceCode::CREATE_PAYOUT_OUTBOX_REQUEST,
            [
                'input'       => $input
            ]);

        $newPayoutOutbox = (new Entity)->generateId();

        $newPayoutOutbox->build($input);

        $this->repo->saveOrFail($newPayoutOutbox);

        return $newPayoutOutbox;
    }

    public function resumePayout(string $id) {
        $this->trace->info(
            TraceCode::RESUME_PAYOUT_CREATION,
            [
                'id' => $id
            ]);

        $payout = $this->repo->transaction(function () use ($id) {
            $outboxPayout = $this->repo->payout_outbox->find($id);

            $payoutInput = json_decode($outboxPayout[Entity::PAYOUT_DATA], true);

            $payout = (new Payout\Core())->createPayoutToFundAccount($payoutInput, $this->merchant);

            $this->repo->deleteOrFail($outboxPayout);

            return $payout;
        });

        return $payout;
    }

    protected function preProcessInputForCreate(array $input) : array
    {
        $input[Entity::EXPIRES_AT] = $input[Entity::EXPIRES_AT] ?? Carbon::now()->timestamp + PayoutOutboxConstants::DEFAULT_PAYOUT_OUTBOX_EXPIRY_IN_SECONDS;

        return $input;
    }
}
