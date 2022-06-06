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
        Util\Constants::WALLET     => 'sometimes|array',
        Util\Constants::PAYLATER   => 'sometimes|array',
        Util\Constants::UPI        => 'sometimes|array',
        Util\Constants::EMI        => 'sometimes|array',
        Util\Constants::CARDS      => 'sometimes|array'
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

    protected static $cardsInstrumentRules = [
        Util\Constants::ISSUER => 'required|string|filled',
        Util\Constants::TYPE   => 'required|array'
    ];

    protected static $paymentMethodsValidators = [
        'netbanking',
        'wallet',
        'upi',
        'paylater',
        'emi',
        'cards'
    ];

    protected static $paymentMethodUpdateRules = [
        Util\Constants::NETBANKING => 'required_without_all:wallet,paylater,upi,emi,cards|array',
        Util\Constants::WALLET     => 'required_without_all:netbanking,paylater,upi,emi,cards|array',
        Util\Constants::PAYLATER   => 'required_without_all:netbanking,wallet,upi,emi,cards|array',
        Util\Constants::UPI        => 'required_without_all:netbanking,wallet,paylater,emi,cards|array',
        Util\Constants::EMI        => 'required_without_all:netbanking,wallet,paylater,upi,cards|array',
        Util\Constants::CARDS      => 'required_without_all:netbanking,wallet,paylater,upi,emi|array'
    ];

    protected static $paymentMethodUpdateValidators = [
        'netbanking_update',
        'wallet_update',
        'upi_update',
        'paylater_update',
        'emi_update',
        'cards_update'
    ];

    protected static $netbankingUpdateInstrumentRules = [
        Util\Constants::TYPE => 'required|in:retail,corporate',
        Util\Constants::BANK => 'required|string'
    ];

    protected static $cardsUpdateInstrumentRules = [
        Util\Constants::ISSUER => 'required|string|filled',
        Util\Constants::TYPE   => 'required|in:domestic'
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
            $this->validateBankCode($bank);

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
            $this->validateWalletCode($code);
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
            $this->validateUpiKey($code);
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
            $this->validatePaylaterCode($code);
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

        foreach ($input[Util\Constants::INSTRUMENT] as $item)
        {
            $this->validateEmiInstrumentWithPartnerAndType($item);
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

    protected function validateCards(array $input)
    {
        if(isset($input[Util\Constants::CARDS]) === false)
        {
            return;
        }

        $cardsInput = $input[Util\Constants::CARDS];

        $this->validateInput('instrument', ['instrument' => $cardsInput] );

        $this->validateCardsInstrument($cardsInput);
    }

    protected function validateCardsInstrument(array $input)
    {
        if(isset($input[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        foreach ($input[Util\Constants::INSTRUMENT] as $item) {

            $this->validateInput('cardsInstrument', $item);
        }
    }

    protected function validateNetbankingUpdate(array $input)
    {
        if(isset($input[Util\Constants::NETBANKING]) === false)
        {
            return;
        }

        $netbankingInput = $input[Util\Constants::NETBANKING];

        if (isset($netbankingInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $instrument = $netbankingInput[Util\Constants::INSTRUMENT];

        $this->validateInput('netbankingUpdateInstrument', $instrument);

        $this->validateBankCode($instrument[Util\Constants::BANK]);
    }

    protected function validateWalletUpdate(array $input)
    {
        if(isset($input[Util\Constants::WALLET]) === false)
        {
            return;
        }

        $walletInput = $input[Util\Constants::WALLET];

        if(isset($walletInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $walletCode = $walletInput[Util\Constants::INSTRUMENT];

        $this->validateWalletCode($walletCode);
    }

    protected function validateUpiUpdate(array $input)
    {
        if(isset($input[Util\Constants::UPI]) === false)
        {
            return;
        }

        $upiInput = $input[Util\Constants::UPI];

        if(isset($upiInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $upiKey = $upiInput[Util\Constants::INSTRUMENT];

        $this->validateUpiKey($upiKey);
    }

    protected function validatePaylaterUpdate(array $input)
    {
        if(isset($input[Util\Constants::PAYLATER]) === false)
        {
            return;
        }

        $paylaterInput = $input[Util\Constants::PAYLATER];

        if(isset($paylaterInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $code = $paylaterInput[Util\Constants::INSTRUMENT];

        $this->validatePaylaterCode($code);
    }

    protected function validateEmiUpdate(array $input)
    {
        if(isset($input[Util\Constants::EMI]) === false)
        {
            return;
        }

        $emiInput = $input[Util\Constants::EMI];

        if(isset($emiInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $instrument = $emiInput[Util\Constants::INSTRUMENT];

        $this->validateEmiInstrumentWithPartnerAndType($instrument);
    }

    protected function validateCardsUpdate(array $input)
    {
        if(isset($input[Util\Constants::CARDS]) === false)
        {
            return;
        }

        $cardsInput = $input[Util\Constants::CARDS];

        if(isset($cardsInput[Util\Constants::INSTRUMENT]) === false)
        {
            return;
        }

        $instrument = $cardsInput[Util\Constants::INSTRUMENT];

        $this->validateInput('cardsUpdateInstrument', $instrument);

        $this->validateCardNetwork($instrument[Util\Constants::ISSUER]);
    }

    private function validateBankCode(string $bank)
    {
        if(in_array($bank, Util\BankCodes::BANKS, TRUE) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BANK_INSTRUMENT_INVALID, $bank);
        }
    }

    private function validateWalletCode(string $code)
    {
        $this->validateInput('walletName', ['walletName' => $code]);

        if(in_array(strtolower($code), Util\Constants::$wallets, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WALLET_INSTRUMENT_INVALID);
        }
    }

    private function validateEmiInstrumentWithPartnerAndType(array $input)
    {
        $this->validateInput('emiInstrument', $input);

        $this->validateEmiPartner($input[Util\Constants::PARTNER], $input[Util\Constants::TYPE]);
    }

    private function validateUpiKey(string $code)
    {
        $this->validateInput('upiKey', ['upiKey' => $code]);

        if(in_array(strtolower($code), Util\Constants::$upiCodes, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UPI_INSTRUMENT_INVALID);
        }
    }

    private function validatePaylaterCode(string $code)
    {
        $this->validateInput('paylater', ['paylater' => $code]);

        if(in_array(strtolower($code), Util\Constants::$paylaterCodes, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYLATER_INSTRUMENT_INVALID);
        }
    }

    private function validateCardNetwork(string $network)
    {
        if(in_array(strtolower($network), Util\Constants::$cardNetworks, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CARD_INSTRUMENT_INVALID);
        }
    }
}
