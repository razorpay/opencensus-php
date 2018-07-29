<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as EntityConstant;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetch(string $id, array $input): array
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

    public function fetchLaReversalsOfTransfer(string $transferId): array
    {
        $transferId = Entity::verifyIdAndStripSign($transferId);

        $merchantId = $this->merchant->getId();

        $reversals = $this->repo->reversal->fetchLaReversalsOfTransfer($transferId, $merchantId);

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

    public function fetchLaTransfer(string $id): array
    {
        $this->validateLinkedAccount();

        $merchantId = $this->merchant->getId();

        Transfer\Entity::verifyIdAndStripSign($id);

        $relations = ['transfer', 'transfer.recipientSettlement'];

        $payment = $this->repo->payment->findByTransferIdAndMerchant($id, $merchantId, $relations);

        return $this->createTransferResponseFromPayment($payment);
    }

    public function fetchLaTransfers(array $input)
    {
        $this->validateLinkedAccount();

        $merchantId = $this->merchant->getId();

        $input['expand'] = ['transfer', 'transfer.recipient_settlement'];

        // fetching by payments fetch to handle notes search.
        $payments = $this->repo->payment->fetch($input, $merchantId);

        $transfersResponse = [
            "count"  => count($payments),
            "entity" => "collection",
            "items"  => $this->createResponse($payments),
        ];

        return $transfersResponse;
    }

    private function createResponse($payments)
    {
        $transfers = [];
        foreach ($payments as $payment)
        {
            $transferData = $this->createTransferResponseFromPayment($payment);

            $transfers[] = $transferData;
        }

        return $transfers;
    }

    private function createTransferResponseFromPayment($payment)
    {
        $result = $payment->toArrayPublic();

        $transferData = $result[Payment\Entity::TRANSFER];
        $transferData[Transfer\Entity::NOTES] = $result[Payment\Entity::NOTES];

        return $transferData;
    }

    protected function validateLinkedAccount()
    {
        $merchant = $this->merchant;

        if ((empty($merchant) === true) or
            ($merchant->isLinkedAccount() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT);
        }
    }
}
