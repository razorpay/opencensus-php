<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Product\Util;
use RZP\Trace\TraceCode;


class PaymentMethodsValidator extends Base\Validator
{
    protected static $paymentMethodsRules = [
        Util\Constants::NETBANKING => 'sometimes|array',
        Util\Constants::WALLET => 'sometimes|array',
        Util\Constants::PAYLATER => 'sometimes|array',
        Util\Constants::UPI => 'sometimes|array',
        Util\Constants::EMI => 'sometimes|array',
    ];

    protected static $netbankingInstrumentRules = [
        Util\Constants::TYPE => 'required|in:retail,corporate',
        Util\Constants::BANK => 'required|array'
    ];

    protected static $emiInstrumentRules = [
        Util\Constants::TYPE =>     'required|in:cardless_emi,card_emi',
        Util\Constants::PARTNER =>  'required|array'
    ];

    protected static $instrumentRules = [
        Util\Constants::INSTRUMENT => 'sometimes|array'
    ];

    protected static $walletNameRules = [
      'walletName' => 'required|string|min:1'
    ];

    protected static $upiKeyRules = [
        'upiKey' => 'required|string|min:1'
    ];

    protected static $paylaterRules = [
        'paylater' => 'required|string|min:1'
    ];

    protected static $paymentMethodsValidators = [
        'netbanking',
        'wallet',
        'upi',
        'paylater',
        'emi'
    ];

    protected function validateNetbanking(array $input)
    {
        if(isset($input[Util\Constants::NETBANKING]) === false)
        {
            return;
        }

        $netbankingInput = $input[Util\Constants::NETBANKING];

        $this->validateInput('instrument', ['instrument' => $netbankingInput] );

        $this->validateNetbankingInstrument($netbankingInput);
    }

    private function validateNetbankingInstrument(array $input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        foreach ($input[Util\Constants::INSTRUMENT] as $item) {

            $this->validateInput('netbankingInstrument', $item);

            $this->validateBank($item[Util\Constants::BANK], $item[Util\Constants::TYPE]);
        }
    }

    protected function validateBank($input, $type)
    {
        foreach ($input as $bank)
        {
            if(in_array($bank, Util\BankCodes::BANKS, TRUE) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BANK_INSTRUMENT_INVALID, $bank);
            }

            // TODO : Map & validate type
        }
    }

    protected function validateWallet($input)
    {
        if(isset($input[Util\Constants::WALLET]) === false)
        {
            return;
        }

        $walletInput = $input[Util\Constants::WALLET];

        $this->validateInput('instrument', $walletInput);

        $this->validateWalletInstrument($walletInput);
    }

    private function validateWalletInstrument(array $input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $walletCodes = $input[Util\Constants::INSTRUMENT];

        foreach ($walletCodes as $code)
        {
            $this->validateInput('walletName', ['walletName' => $code]);

            if(in_array(strtolower($code), Util\Constants::$wallets, true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WALLET_INSTRUMENT_INVALID);
            }

        }
    }

    protected function validateUpi($input)
    {
        if(isset($input[Util\Constants::UPI]) === false)
        {
            return;
        }

        $upiInput = $input[Util\Constants::UPI];

        $this->validateInput('instrument', $upiInput);

        $this->validateUpiInstrument($upiInput);
    }

    protected function validateUpiInstrument($input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $walletCodes = $input[Util\Constants::INSTRUMENT];

        foreach ($walletCodes as $code)
        {
            $this->validateInput('upiKey', ['upiKey' => $code]);

            if(in_array(strtolower($code), Util\Constants::$upiCodes, true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UPI_INSTRUMENT_INVALID);
            }

        }
    }

    protected function validatePaylater($input)
    {
        if(isset($input[Util\Constants::PAYLATER]) === false)
        {
            return;
        }

        $upiInput = $input[Util\Constants::PAYLATER];

        $this->validateInput('instrument', $upiInput);

        $this->validatePaylaterInstrument($upiInput);
    }

    protected function validatePaylaterInstrument($input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $paylaterCodes = $input[Util\Constants::INSTRUMENT];

        foreach ($paylaterCodes as $code)
        {
            $this->validateInput('paylater', ['paylater' => $code]);

            if(in_array(strtolower($code), Util\Constants::$paylaterCodes, true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYLATER_INSTRUMENT_INVALID);
            }

        }
    }

    protected function validateEmi(array $input)
    {
        if(isset($input[Util\Constants::EMI]) === false)
        {
            return;
        }

        $emiInput = $input[Util\Constants::EMI];

        $this->validateInput('instrument', $emiInput);

        $this->validateEmiInstrument($emiInput);
    }

    private function validateEmiInstrument(array $input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        foreach ($input[Util\Constants::INSTRUMENT] as $item) {
            $this->validateInput('emiInstrument', $item);

            $this->validateEmiPartner($item[Util\Constants::PARTNER], $item[Util\Constants::TYPE]);
        }
    }

    protected function validateEmiPartner($input, $type)
    {
        $partners = ($type === Util\Constants::CARD_EMI) ? Util\Constants::$cardEmiCodes : Util\Constants::$cardlessEmiCodes;

        foreach ($input as $code)
        {
            if(in_array($code, $partners, TRUE) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EMI_INSTRUMENT_INVALID);
            }
        }
    }
}
