<?php

namespace RZP\Models\Merchant\Product\Util;

class PaymentMethodsRequestHandler
{

    public function handleRequest(array $request)
    {
        $transformedRequest = [];
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::WALLET, 'pg.wallet.'));
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::UPI, 'pg.upi.'));
        $transformedRequest = array_merge($transformedRequest, self::getPaylaterInstruments($request, Constants::PAYLATER, 'pg.paylater.'));
        $transformedRequest = array_merge($transformedRequest, self::getNetbankingInstruments($request));

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
}
