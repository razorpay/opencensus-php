<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Reversal;
use RZP\Constants\Entity as EntityConstant;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetch(string $id, array $input = []): array
    {
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant, $input);

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
        $merchantId = $this->merchant->getId();

        $options = [
            Reversal\Entity::ENTITY_ID      => Entity::verifyIdAndStripSign($id),
            Reversal\Entity::ENTITY_TYPE    => EntityConstant::TRANSFER
        ];

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

        $reversal = (new Reversal\Core)->reverseForTransfer($transfer, $input, $this->merchant);

        return $reversal->toArrayPublic();
    }
}
