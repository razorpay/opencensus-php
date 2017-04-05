<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Models\Payment;
USE RZP\Models\Currency;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $validator;

    protected $receiver;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;
    }

    public function validate(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_VALIDATION_REQUEST,
            $input
        );

        $data = $this->validateReceiver($input);

        return $data;
    }

    public function pay(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PAY_REQUEST,
            $input
        );

        $this->validator->validateInput('pay', $input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::UTR]);

        if ($bankTransfer !== null)
        {
            $this->merchant = $bankTransfer->payment->merchant;

            $paymentProcessor = new Payment\Processor\Processor($this->merchant);

            $paymentId = $bankTransfer->payment->getId();

            $paymentProcessor->processBankTransferPayment($paymentId);

            $this->markReceiverUsed($bankTransfer);
        }

        return [
            'success'        => true,
            'message'        => null,
            Entity::UTR      => $input[Entity::UTR],
        ];
    }

    protected function validateReceiver(array $input): array
    {
        $this->validator->validateInput('validate', $input);

        $bankTransfer = $this->core->create($input);

        $uniqueUtr = $this->validateUniqueUtr($bankTransfer);

        $expected = $this->checkAccount($bankTransfer);

        if (($expected === true) and
            ($uniqueUtr === true))
        {
            $paymentInput = $this->bankTransferPaymentArray($input);

            $paymentProcessor = new Payment\Processor\Processor($this->merchant);

            $payment = $paymentProcessor->processBankTransfer($paymentInput);

            $bankTransfer->payment()->associate($payment);

            $data = [
                'valid'          => true,
                'message'        => null,
            ];

            $this->repo->saveOrFail($bankTransfer);
        }
        else
        {
            $data['valid'] = false;

            if ($expected === false)
            {
                $data['message'] = 'Invalid account number';
            }
            else if ($uniqueUtr === false)
            {
                $data['message'] = 'Duplicate UTR received';
            }
        }

        $data[Entity::UTR] = $input[Entity::UTR];

        return $data;
    }

    protected function validateUniqueUtr(Entity $bankTransfer): bool
    {
        $utr = $bankTransfer->getUtr();

        $duplicateBankTransfer = $this->repo->bank_transfer
                                      ->findByUtr($utr);

        if ($duplicateBankTransfer === null)
        {
            return true;
        }

        $this->trace->warning(
            TraceCode::BANK_TRANSFER_VALIDATION_DUPLICATE_UTR,
            [
                'existing_transfer' => $duplicateBankTransfer->toArrayPublic(),
                'received_utr'      => $bankTransfer->getUtr(),
            ]
        );

        return false;
    }

    protected function checkAccount(Entity $bankTransfer): bool
    {
        $this->receiver = $this->getReceiverFromBankTransfer($bankTransfer);

        if (is_null($this->receiver) === true)
        {
            return false;
        }

        $this->merchant = $this->receiver->account->merchant;

        return true;
    }

    protected function markReceiverUsed(Entity $bankTransfer)
    {
        $this->receiver = $this->getReceiverFromBankTransfer($bankTransfer);

        if ($this->receiver->isOneTimeUse() === true)
        {
            $this->receiver->setValid(false);

            $this->repo->saveOrFail($this->receiver);
        }
    }

    protected function getReceiverFromBankTransfer(Entity $bankTransfer)
    {
        $accountNumber = $bankTransfer->getPayeeAccount();

        $ifscCode = $bankTransfer->getPayeeIfsc();

        return $this->repo->receiver
                    ->getValidVirtualBankAccountFromNumber($accountNumber, $ifscCode);
    }

    protected function bankTransferPaymentArray(array $input): array
    {
        $paymentArray = $this->defaultBankTransferPaymentArray();

        $paymentArray[Payment\Entity::AMOUNT] = $input['amount'];

        return $paymentArray;
    }

    protected function defaultBankTransferPaymentArray(): array
    {
        return [
            Payment\Entity::CURRENCY => Currency\Currency::INR,
            Payment\Entity::METHOD   => Payment\Method::BANK_TRANSFER,
        ];
    }
}
