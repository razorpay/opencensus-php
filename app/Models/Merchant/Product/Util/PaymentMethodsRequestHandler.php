<?php

namespace RZP\Models\Merchant\Product\Util;

class PaymentMethodsRequestHandler
{

    public function handleRequest(array $request, bool $isExperimentEnabled = false)
    {
        if ($isExperimentEnabled)
        {
            if (self::isConfigProvidedForPaymentMethod($request, Constants::NETBANKING))
            {
                return array(self::getNetbankingInstrument($request[Constants::NETBANKING][Constants::INSTRUMENT]));
            }

            if (self::isConfigProvidedForPaymentMethod($request, Constants::CARDS))
            {
                return array(self::getCardInstrument($request[Constants::CARDS][Constants::INSTRUMENT]));
            }

            foreach (array(Constants::WALLET, Constants::UPI, Constants::PAYLATER) as $method)
            {
                if (self::isConfigProvidedForPaymentMethod($request, $method))
                {
                    return array(self::getPaylaterInstrument($request, $method));
                }
            }

            return [];
        }

        $transformedRequest = [];
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::WALLET, 'pg.wallet.'));
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::UPI, 'pg.upi.'));
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::PAYLATER, 'pg.paylater.'));
        $transformedRequest = array_merge($transformedRequest, self::getNetbankingInstruments($request));
        $transformedRequest = array_merge($transformedRequest, self::getCardInstruments($request));

        return $transformedRequest;
    }

    private static function getWalletInstruments(array $request)
    {
        $requests = [];

        if(array_key_exists(Constants::WALLET, $request ) === false)
        {
            return $requests;
        }

        if(array_key_exists(Constants::INSTRUMENT, $request[Constants::WALLET] ) === false)
        {
            return $requests;
        }

        foreach ($request[Constants::WALLET][Constants::INSTRUMENT] as $instrument)
        {
            array_push($requests, 'pg.wallet.'.studly_case($instrument));
        }

        return $requests;
    }

    private static function getUpiInstruments(array $request)
    {
        $requests = [];

        if(array_key_exists(Constants::UPI, $request) === false)
        {
            return $requests;
        }

        if(array_key_exists(Constants::INSTRUMENT, $request[Constants::UPI] ) === false)
        {
            return $requests;
        }

        foreach ($request[Constants::UPI][Constants::INSTRUMENT] as $instrument)
        {
            array_push($requests, 'pg.upi.'.studly_case($instrument));
        }

        return $requests;
    }

    private static function getPaylaterInstruments(array $request, $type, $prefix)
    {
        $requests = [];

        if(array_key_exists($type, $request) === false)
        {
            return $requests;
        }

        if(array_key_exists(Constants::INSTRUMENT, $request[$type] ) === false)
        {
            return $requests;
        }

        foreach ($request[$type][Constants::INSTRUMENT] as $instrument)
        {
            array_push($requests, $prefix.studly_case($instrument));
        }

        return $requests;
    }


    private static function getNetbankingInstruments(array $request)
    {
        $requests = [];

        if(array_key_exists(Constants::NETBANKING, $request) === false)
        {
            return $requests;
        }

        if(array_key_exists(Constants::INSTRUMENT, $request[Constants::NETBANKING]) === false)
        {
            return $requests;
        }

        foreach ($request[Constants::NETBANKING][Constants::INSTRUMENT] as $instrument)
        {
            if(array_key_exists(Constants::BANK, $instrument) === false)
            {
                continue;
            }

            if($instrument[Constants::TYPE] == Constants::RETAIL)
            {
                $requests = array_merge($requests, self::transformBankingInstruments($instrument[Constants::BANK], Constants::RETAIL));
            }
            else if($instrument[Constants::TYPE] == Constants::CORPORATE)
            {
                $requests = array_merge($requests, self::transformBankingInstruments($instrument[Constants::BANK], Constants::CORPORATE));
            }
        }

        return $requests;
    }

    /**
     * @param $instrument
     * @param string $type
     * @return array
     */
    private static function transformBankingInstruments($instrument, string $type): array
    {
        $prefix = "pg.netbanking.";
        $response = [];
        foreach ($instrument as $bank) {
            array_push($response, $prefix . $type . '.' . self::getBankingInstrument($bank, $type));
        }

        return $response;
    }

    /**
     * @param $bank
     * @param $type
     * @return string
     */
    private static function getBankingInstrument($bank, $type): string
    {
        return (new BankCodes())->getInstrumentFromBankcode(strtolower($bank), $type);
    }

    private static function getCardInstruments(array $request)
    {
        $requests = [];

        if(array_key_exists(Constants::CARDS, $request) === false ||
           array_key_exists(Constants::INSTRUMENT, $request[Constants::CARDS]) === false)
        {
            return $requests;
        }

        foreach ($request[Constants::CARDS][Constants::INSTRUMENT] as $instrument)
        {
            if(array_key_exists(Constants::ISSUER, $instrument) === false ||
               array_key_exists(Constants::TYPE, $instrument) === false)
            {
                continue;
            }

            $requests = array_merge($requests, self::transformCardInstruments($instrument[Constants::ISSUER], $instrument[Constants::TYPE]));
        }

        return $requests;
    }

    private static function transformCardInstruments(string $issuer, array $types) : array
    {
        $prefix = Constants::$paymentMethodInstrumentPrefix[Constants::CARDS];
        $response = [];
        foreach ($types as $type) {
            array_push($response, $prefix . $type . '.' . $issuer);
        }

        return $response;
    }

    private static function isConfigProvidedForPaymentMethod(array $request, string $paymentMethod) : bool
    {
        if(array_key_exists($paymentMethod, $request) === false)
        {
            return false;
        }

        if(array_key_exists(Constants::INSTRUMENT, $request[$paymentMethod]) === false)
        {
            return false;
        }

        return true;
    }

    private static function getNetbankingInstrument(array $instrument) : string
    {
        $type = $instrument[Constants::TYPE];

        $bank = $instrument[Constants::BANK];

        $prefix = Constants::$paymentMethodInstrumentPrefix[Constants::NETBANKING];

        return  $prefix . $type . '.' . self::getBankingInstrument($bank, $type);
    }

    private static function getPaylaterInstrument(array $request, string $type)
    {
        $instrument = $request[$type][Constants::INSTRUMENT];

        $prefix = Constants::$paymentMethodInstrumentPrefix[$type];

        return $prefix.studly_case($instrument);
    }

    private static function getCardInstrument(array $instrument)
    {
        $issuer = $instrument[Constants::ISSUER];

        $type = $instrument[Constants::TYPE];

        $prefix = Constants::$paymentMethodInstrumentPrefix[Constants::CARDS];

        return  $prefix . $type . '.' . $issuer;
    }
}
