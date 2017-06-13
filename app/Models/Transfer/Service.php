<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetch(string $id): array
    {
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        return $transfer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $transfers = $this->repo->transfer->fetch($input, $merchantId);

        return $transfers->toArrayPublic();
    }

    public function fetchReversalsOfTransfer(string $id): array
    {
        $options = [Reversal\Entity::TRANSFER_ID => $id];

        $merchantId = $this->merchant->getId();

        $reversals = $this->repo->reversal->fetch($options, $merchantId);

        return $reversals->toArrayPublic();
    }

    public function create(array $input): array
    {
        $transfer = $this->core->createForMerchant($input, $this->merchant);

        return $transfer->toArrayPublic();
    }

    public function edit(string $id, array $input) : array
    {
        $this->trace->info(
            TraceCode::TRANSFER_EDIT_REQUEST,
            [
                'transfer_id' => $id,
                'input'       => $input,
            ]);

        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        $transfer = $this->core->edit($transfer, $input);

        return $transfer->toArrayPublic();
    }

    public function reverse(string $id, array $input) : array
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $id,
                'input'       => $input
            ]);

        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant);

        $reversal = (new Reversal\Core)->reverse($transfer, $input, $this->merchant);

        return $reversal->toArrayPublic();
    }
}
