<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
USE RZP\Models\Currency\Currency;
use RZP\Models\VirtualAccount\Provider;
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

        $this->provider = $this->auth->getInternalApp();

        $this->ip = $this->app['request']->getRealClientIp();

        $this->validateProviderIp($this->provider, $this->ip);
    }

    public function validate(array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_VALIDATION_REQUEST,
            $input
        );

        $bankTransfer = $this->core->create($input);

        if (($this->isUtrUnique($bankTransfer) === true) and
            ($this->isTransferExpected($bankTransfer) === true))
        {
            $bankTransfer->setExpected(true);

            $this->setMerchant();

            $this->validateExpectedBankTransfer($bankTransfer);

            $this->trace->info(
                TraceCode::BANK_TRANSFER_VALIDATION_SUCCESSFUL,
                $bankTransfer->toArrayPublic()
            );
        }
        else
        {
            $bankTransfer->setExpected(false);
        }

        $this->repo->saveOrFail($bankTransfer);

        $data = [
            'valid'          => true,
            'message'        => null,
            'transaction_id' => $bankTransfer->getUtr(),
        ];

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
            if ($bankTransfer->isNotified() === false)
            {
                $bankTransfer->setNotified(true);

                $this->repo->saveOrFail($bankTransfer);
            }
        }
        else
        {
            $this->trace->error(
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

    protected function validateExpectedBankTransfer(Entity $bankTransfer)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $this->repo->transaction(function() use ($bankTransfer, $paymentProcessor)
        {
            $paymentInput = $this->bankTransferPaymentArray($bankTransfer);

            $payment = $paymentProcessor->processBankTransferPayment($paymentInput);

            $bankTransfer->payment()->associate($payment);

            $bankTransfer->merchant()->associate($this->merchant);

            $this->updateVirtualAccount($bankTransfer);

            $this->app['events']->fire('api.payment.captured', [$payment]);
        });
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
                'message'           => 'Duplicate UTR received',
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
            $this->trace->info(
                TraceCode::BANK_TRANSFER_VALIDATION_FAILED,
                [
                    'message'      => 'Invalid account number',
                    'bankTransfer' => $bankTransfer->toArrayPublic(),
                ]
            );

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
        $bankCode = Provider::getBankCode($this->provider);

        $bankAccount = $this->repo->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode);

        return $bankAccount;
    }

    protected function bankTransferPaymentArray(Entity $bankTransfer): array
    {
        $paymentArray = self::DEFAULT_BANK_TRANSFER_ARRAY;

        $paymentArray[Payment::AMOUNT] = $bankTransfer->getAmount();

        return $paymentArray;
    }

    protected function validateProviderIp(string $app, string $ip)
    {
        if (Provider::validateIp($app, $ip) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
}
