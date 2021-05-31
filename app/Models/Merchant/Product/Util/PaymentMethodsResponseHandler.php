<?php

namespace RZP\Models\Merchant\Product\Util;


class PaymentMethodsResponseHandler
{

    public static function handleResponse(array $response)
    {
        $finalResponse = [];
        foreach (array(Constants::ACTIVATED, Constants::REQUESTED) as $status)
        {
            $transformedResponse = [Constants::PAYMENT_METHODS => self::extractResponse($response, $status)];
            array_push($finalResponse, $transformedResponse);
        }

        return $finalResponse;
    }

    /**
     * @param array $response
     * @param string $category
     * @return array
     */
    private static function getLeafValuesOfInstrument(array $response, string $category): array
    {
        $instruments = [];
        foreach ($response as $row) {
            $instrument = explode(".", $row["instrument"]);
            array_push($instruments, $instrument[count($instrument)-1]);
        }
        return $instruments;
    }

    /**
     * @param array $response
     * @param string $status
     * @return \array[][]
     */
    private static function extractResponse(array $response, string $status): array
    {
        $transformedResponse = [];

        $netbankingResponse = self::getNetbankingResponse($response, $status);

        if($netbankingResponse[Constants::ENABLED] === true)
        {
            $transformedResponse[Constants::NETBANKING] = $netbankingResponse;
        }

        foreach (array(Constants::WALLET, Constants::PAYLATER, Constants::UPI) as $type)
        {
            $instrument = self::getLeafResponse($response, $status, $type);
            if(count($instrument) > 0)
            {
                $transformedResponse[$type] = [
                    Constants::ENABLED => true,
                    Constants::INSTRUMENT => $instrument
                ];
            }
        }


        return $transformedResponse;
    }

    /**
     * @param array $response
     * @param string $status
     * @return array[]
     */
    private static function getNetbankingResponse(array $response, string $status): array
    {
        $netbankingResponse = [
            Constants::ENABLED => false,
            Constants::INSTRUMENT => [],
        ];

        $enabled = false;
        foreach (array(Constants::RETAIL, Constants::CORPORATE) as $category)
        {
            // netbanking retail
            list($r_enabled, $transformedResponse )= self::prepareBankingResponse($response, $status, $category);

            if($r_enabled === true)
            {
                $enabled = true;
                array_push($netbankingResponse[Constants::INSTRUMENT], (object)$transformedResponse);
            }
        }

        if($enabled)
        {
           $netbankingResponse[Constants::ENABLED] = true;
        }

        return $netbankingResponse;
    }

    /**
     * @param array $response
     * @param string $status
     * @param string $category
     * @return array[]
     */
    private static function prepareBankingResponse(array $response, string $status, string $category): array
    {
        $transformedResponse = self::extractBankingResponse($response, $status, $category);

        $categoryResponse = [
            Constants::TYPE => $category
        ];

        $instruments = self::getLeafValuesOfInstrument($transformedResponse, Constants::NETBANKING);
        $bankCodes = BankCodes::getBankcodesFromInstruments($instruments);

        $categoryResponse[Constants::BANK] = $bankCodes;

        return array(count($bankCodes) > 0, $categoryResponse);
    }

    /**
     * @param array $response
     * @param string $status
     * @param string $category
     * @return array
     */
    private static function extractBankingResponse(array $response, string $status, string $category): array
    {
        return array_filter($response, function ($row) use ($category, $status) {
            $instrument = explode(".", $row["instrument"]);
            return $instrument[1] == Constants::NETBANKING && $instrument[2] == $category && $row[Constants::STATUS] == $status;
        });
    }

    /**
     * @param array $response
     * @param string $status
     * @param string $type
     * @return array
     */
    private static function getLeafResponse(array $response, string $status, string $type): array
    {
        $instruments = array_filter($response, function ($row) use ($type, $status) {
            $instrument = explode(".", $row["instrument"]);
            return $instrument[1] == $type && $row[Constants::STATUS] == $status;
        });

        $instruments = self::getLeafValuesOfInstrument($instruments, $type);
        return $instruments;
    }

}
