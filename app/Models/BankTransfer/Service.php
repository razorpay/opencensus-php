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

        $data = $this->validateReceiver($input);

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
            $this->repo->transaction(function() use ($bankTransfer)
            {
                $this->setAssociatedEntities($bankTransfer);

                $paymentProcessor = new PaymentProcessor($this->merchant);

                $paymentId = $bankTransfer->payment->getId();

                $paymentProcessor->processBankTransferPayment($paymentId);

                $this->markReceiverUsed($bankTransfer);

                $this->app['events']->fire('api.account.credited', [$bankTransfer]);
            });
        }

        return [
            'success'        => true,
            'message'        => null,
            Entity::UTR      => $input[Entity::UTR],
        ];
    }

    protected function setAssociatedEntities(Entity $bankTransfer)
    {
        $this->merchant = $bankTransfer->merchant;

        // $this->customer = (new Customer\Core)->createLocalCustomer([], $this->merchant);

        // $bankAccountInput = $this->customerBankAccountInput($bankTransfer);

        // $this->customerAccount = (new BankAccount\Core)->addOrUpdateBankAccountForCustomer([], $this->customer);
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

    protected function validateReceiver(array $input): array
    {
        $this->validator->validateInput('create', $input);

        $bankTransfer = $this->core->create($input);

        $uniqueUtr = $this->validateUniqueUtr($bankTransfer);

        $expected = $this->transferExpected($bankTransfer);

        if (($expected === true) and ($uniqueUtr === true))
        {
            $paymentInput = $this->bankTransferPaymentArray($input);

            $paymentProcessor = new PaymentProcessor($this->merchant);

            $payment = $paymentProcessor->processBankTransfer($paymentInput);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($this->merchant);

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

    protected function transferExpected(Entity $bankTransfer): bool
    {
        $this->receiver = $this->getReceiverFromBankTransfer($bankTransfer);

        if ($this->receiver === null)
        {
            return false;
        }

        $this->merchant = $this->receiver->account->merchant;

        return true;
    }

    protected function markReceiverUsed(Entity $bankTransfer)
    {
        $this->receiver = $this->getReceiverFromBankTransfer($bankTransfer);

        if ($this->receiver->isSingleUse() === true)
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
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment\Entity::AMOUNT] = $input['amount'];

        return $paymentArray;
    }
}
