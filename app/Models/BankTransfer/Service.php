<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
USE RZP\Models\Currency;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends Base\Service
{
    protected $core;

    protected $cache;

    protected $bankAccount;

    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->cache = $this->app['redis'];
    }

    public function validate(array $input): array
    {
        $this->trace->info(
            TraceCode::ECOLLECT_VALIDATION_REQUEST,
            $input
        );

        $data = $this->validateReceiver($input);

        return $data;
    }

    public function pay(array $input): array
    {
        $this->trace->info(
            TraceCode::ECOLLECT_PAY_REQUEST,
            $input
        );

        $this->validator->validateInput('pay', $input);

        return [
            'success'        => true,
            'message'        => null,
            'transaction_id' => $input['transaction_id'],
        ];
    }

    protected function uniqueUtrCheck(array $input, array & $data)
    {
        if($data['valid'] === true)
        {
            $key = 'ecollect' . $this->mode . $input['transaction_id'];

            $cachedData = $this->cache->get($key);

            if (is_null($cachedData) === false)
            {
                $this->trace->warning(
                    TraceCode::ECOLLECT_VALIDATION_DUPLICATE_UTR,
                    [
                        'cached_data'   => json_decode($cachedData, true),
                        'received_data' => $input,
                    ]
                );

                $data['valid']   = false;
                $data['message'] = 'Duplicate UTR received';
            }
            else
            {
                $this->cache->set($key, json_encode($input));
            }
        }
    }

    protected function validateReceiver($input)
    {
        if ($this->mode === Mode::TEST)
        {
            $data = $this->validateTestMode($input);
        }
        else
        {
            $data = $this->validateLiveMode($input);
        }

        return $data;
    }

    protected function validateLiveMode($input)
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

        $data['transaction_id'] = $input['transaction_id'];

        return $data;
    }

    protected function validateUniqueUtr(Entity $bankTransfer)
    {
        $transactionId = $bankTransfer->getTransactionId();

        $duplicateBankTransfer = $this->repo->bank_transfer
                                      ->findByTransactionId($transactionId);

        if ($duplicateBankTransfer === null)
        {
            return true;
        }

        $this->trace->warning(
            TraceCode::ECOLLECT_VALIDATION_DUPLICATE_UTR,
            [
                'existing_transfer' => $duplicateBankTransfer->toArrayPublic(),
                'received_utr'      => $bankTransfer->getTransactionId(),
            ]
        );

        return false;
    }

    protected function validateTestMode(array $input)
    {
        $this->validator->validateInput('validate', $input);

        $data = [
            'valid'          => true,
            'message'        => null,
        ];

        if ((substr($input['payee_account'], 0, 3) !== 'RZP') and
            (substr($input['payee_account'], 0, 6) !== 'RAZORP'))
        {
            $data = [
                'valid'          => false,
                'message'        => 'Invalid account number',
            ];
        }

        $data['transaction_id'] = $input['transaction_id'];

        $this->uniqueUtrCheck($input, $data);

        return $data;
    }

    protected function checkAccount($bankTransfer)
    {
        $accountNumber = $bankTransfer->getPayeeAccount();

        $ifscCode = $bankTransfer->getPayeeIfsc();

        $receiver = $this->repo->receiver
                         ->getValidVirtualBankAccountFromNumber($accountNumber, $ifscCode);

        if (is_null($receiver) === true)
        {
            return false;
        }

        $this->merchant = $receiver->account->merchant;

        return true;
    }

    protected function bankTransferPaymentArray(array $input)
    {
        $paymentArray = $this->defaultBankTransferPaymentArray();

        $paymentArray[Payment\Entity::AMOUNT] = $input['amount'];

        return $paymentArray;
    }

    protected function defaultBankTransferPaymentArray()
    {
        return [
            Payment\Entity::CURRENCY => Currency\Currency::INR,
            Payment\Entity::METHOD   => Payment\Method::BANK_TRANSFER,
        ];
    }
}
