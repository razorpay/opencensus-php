<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
USE RZP\Models\Currency;
use RZP\Trace\TraceCode;
use Razorpay\IFSC;

class Service extends Base\Service
{
    protected $validator;

    protected $receiver;

    const DEFAULT_BANK_TRANSFER_ARRAY = [
        Payment\Entity::CURRENCY => Currency\Currency::INR,
        Payment\Entity::METHOD   => Payment\Method::BANK_TRANSFER,
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

    public function pay(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PAY_REQUEST,
            $input
        );

        $this->validator->validateInput('create', $input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::UTR]);

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
                TraceCode::BANK_TRANSFER_UNEXPECTED_PAY_NOTIFY,
                [
                    'input' => $input,
                ]
            );
        }

        return [
            'success'        => true,
            'message'        => null,
            Entity::UTR      => $input[Entity::UTR],
        ];
    }

    protected function customerBankAccountInput(Entity $bankTransfer)
    {
        $ifsc = $bankTransfer->getPayerIfsc();

        $res = (new IFSC\Client)->lookupIFSC($ifsc);

        $addressChunks = str_split($res->address, 30);

        $accountDetails = [
            BankAccount\Entity::ACCOUNT_NUMBER       => $bankTransfer->getPayerAccount(),
            BankAccount\Entity::BENEFICIARY_NAME     => 'dummy value',
            BankAccount\Entity::BENEFICIARY_EMAIL    => 'dummy value',
            BankAccount\Entity::BENEFICIARY_MOBILE   => $res->contact,
            BankAccount\Entity::IFSC_CODE            => $bankTransfer->getPayerIfsc(),
            BankAccount\Entity::BENEFICIARY_PIN      => 'dummy value',
            BankAccount\Entity::BENEFICIARY_CITY     => $res->city,
            BankAccount\Entity::BENEFICIARY_STATE    => $res->state,
        ];

        foreach ($addressChunks as $index => $chunk)
        {
            $accountDetails['beneficiary_address' . ($index + 1)] = $chunk;
        }
    }

    protected function validateVirtualAccount(array $input): array
    {
        $this->validator->validateInput('create', $input);

        $bankTransfer = $this->core->create($input);

        $uniqueUtr = $this->validateUniqueUtr($bankTransfer);

        $expected = $this->isTransferExpected($bankTransfer);

        if (($expected === true) and ($uniqueUtr === true))
        {
            $this->setMerchant();

            $paymentInput = $this->bankTransferPaymentArray($input);

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

        return $this->repo->virtual_account
                    ->getActiveVirtualAccountFromBankAccountId($bankAccountId);
    }

    protected function getBankAccountFromNumber(string $accountNumber)
    {
        $bankAccount = $this->repo->bank_account
                            ->findFirstBankAccountByAccountNumber($accountNumber);

        return $bankAccount;
    }

    protected function bankTransferPaymentArray(array $input): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment\Entity::AMOUNT] = $input['amount'];

        return $paymentArray;
    }
}
