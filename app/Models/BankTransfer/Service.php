<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
USE RZP\Models\Currency\Currency;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Service extends Base\Service
{
    protected $validator;

    protected $virtualAccount;

    protected $input;

    const DEFAULT_BANK_TRANSFER_ARRAY = [
        Payment::CURRENCY => Currency::INR,
        Payment::METHOD   => Method::BANK_TRANSFER,
    ];

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

        $data = $this->validateVirtualAccount($input);

        return $data;
    }

    public function notify(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_NOTIFY_REQUEST,
            $input
        );

        $this->validator->validateInput('notify', $input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::REQ_UTR]);

        if ($bankTransfer !== null)
        {
            $this->setVirtualAccount($bankTransfer);

            $this->setMerchant();

            $paymentProcessor = new PaymentProcessor($this->merchant);

            $this->repo->transaction(function() use ($bankTransfer, $paymentProcessor)
            {
                $paymentId = $bankTransfer->payment->getId();

                $paymentProcessor->processBankTransferPayment($paymentId);

                $this->updateVirtualAccount($bankTransfer);
            });

            $this->app['events']->fire('api.virtual_account.credited', [$bankTransfer]);
        }
        else
        {
            $this->trace->critical(
                TraceCode::BANK_TRANSFER_UNEXPECTED_NOTIFY,
                [
                    'input' => $input,
                ]
            );
        }

        return [
            'success'        => true,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR],
        ];
    }

    protected function validateVirtualAccount(array $input): array
    {
        $bankTransfer = $this->core->create($input);

        $uniqueUtr = $this->isUtrUnique($bankTransfer);

        $expected = $this->isTransferExpected($bankTransfer);

        if (($expected === true) and ($uniqueUtr === true))
        {
            $this->setMerchant();

            $paymentInput = $this->bankTransferPaymentArray($bankTransfer);

            $paymentProcessor = new PaymentProcessor($this->merchant);

            $payment = $paymentProcessor->processBankTransferValidation($paymentInput);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($this->merchant);

            $data = [
                'valid'          => true,
                'message'        => null,
            ];
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

        $this->repo->saveOrFail($bankTransfer);

        $data[Entity::REQ_UTR] = $bankTransfer->getUtr();

        return $data;
    }

    protected function isUtrUnique(Entity $bankTransfer): bool
    {
        $utr = $bankTransfer->getUtr();

        $duplicateBankTransfer = $this->repo->bank_transfer
                                      ->findByUtr($utr);

        if ($duplicateBankTransfer === null)
        {
            return true;
        }

        $this->trace->error(
            TraceCode::BANK_TRANSFER_VALIDATION_DUPLICATE_UTR,
            [
                'existing_transfer' => $duplicateBankTransfer->toArrayPublic(),
                'received_utr'      => $bankTransfer->getUtr(),
            ]
        );

        return false;
    }

    protected function isTransferExpected(Entity $bankTransfer): bool
    {
        $this->setVirtualAccount($bankTransfer);

        if ($this->virtualAccount === null)
        {
            return false;
        }

        return true;
    }

    protected function setVirtualAccount(Entity $bankTransfer)
    {
        $this->virtualAccount = $this->getVirtualAccountFromBankTransfer($bankTransfer);
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    protected function updateVirtualAccount(Entity $bankTransfer)
    {
        $this->virtualAccount->incrementAmountPaid($bankTransfer->getAmount());

        $this->virtualAccount->incrementAmountReceived($bankTransfer->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function getVirtualAccountFromBankTransfer(Entity $bankTransfer)
    {
        $accountNumber = $bankTransfer->getPayeeAccount();

        // $ifscCode = $bankTransfer->getPayeeIfsc();

        $bankAccount = $this->getBankAccountFromNumber($accountNumber);

        if (count($bankAccount) === 0)
        {
            return null;
        }

        $bankAccountId = $bankAccount->getId();

        $virtualAccount = $this->repo->virtual_account
                               ->getActiveVirtualAccountFromBankAccountId($bankAccountId);

        return $virtualAccount;
    }

    protected function getBankAccountFromNumber(string $accountNumber)
    {
        $bankAccount = $this->repo->bank_account
                            ->findFirstBankAccountByAccountNumber($accountNumber);

        return $bankAccount;
    }

    protected function bankTransferPaymentArray(Entity $bankTransfer): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment::AMOUNT] = $bankTransfer->getAmount();

        return $paymentArray;
    }
}
