<?php

namespace RZP\Models\Merchant\Product\Util;


class PaymentMethodsResponseHandler
{

    public static function handleResponse(array $response)
    {
        $finalResponse = [];
        foreach (array(Constants::ACTIVATED, Constants::REQUESTED) as $status)
        {
            $result = self::buildResponse($response, $status);

            $transformedResponse = [];

            if (!empty($result))
            {
                $transformedResponse = [Constants::PAYMENT_METHODS => self::buildResponse($response, $status)];
            }
            array_push($finalResponse, $transformedResponse);
        }

        return $finalResponse;
    }

    /**
     * @param array $response
     * @param string $status
     * @return \array[][]
     */
    private static function buildResponse(array $response, string $status): array
    {
        $transformedResponse = [];

        $netbankingResponse = self::getNetbankingResponse($response, $status);

        if($netbankingResponse[Constants::ENABLED] === true)
        {
            $transformedResponse[Constants::NETBANKING] = $netbankingResponse;
        }

        $cardsResponse = self::getCardsResponse($response, $status);

        if ($cardsResponse[Constants::ENABLED] === true)
        {
            $transformedResponse[Constants::CARDS] = $cardsResponse;
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

        $emiResponse = self::getEmiResponse($response, $status);

        if($emiResponse[Constants::ENABLED] == true)
        {
            $transformedResponse[Constants::EMI] = $emiResponse;
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

        $instruments = self::getLeafValues($transformedResponse);
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
            return $instrument[1] === Constants::NETBANKING && $instrument[2] === $category && $row[Constants::STATUS] === $status;
        });
    }

    private static function getEmiResponse($response, $status)
    {
        $emiResponse = [
            Constants::ENABLED => false,
            Constants::INSTRUMENT => [],
        ];

        $emiInstruments = array_filter($response, function ($row) use ($status) {
            $instrument = explode(".", $row["instrument"]);
            return $instrument[1] == Constants::EMI && $row[Constants::STATUS] == $status;
        });

        $cardlessInstruments = array_filter($emiInstruments, function ($row) use ($status) {
            $instrument = explode(".", $row["instrument"]);
            return $instrument[1] == Constants::EMI && $row[Constants::STATUS] == $status && $instrument[2] == Constants::CARDLESS_EMI;
        });

        $cardInstruments = array_diff_key($emiInstruments, $cardlessInstruments);

        $emiInstruments = [
            Constants::CARDLESS_EMI => $cardlessInstruments,
            Constants::CARD_EMI     => $cardInstruments
        ];

        foreach ($emiInstruments as $key => $value)
        {
            $instruments = self::getLeafValues($value);
            if(count($instruments) > 0)
            {
                $subResponse = [
                    Constants::TYPE => $key,
                    Constants::PARTNER => $instruments
                ];

                array_push($emiResponse[Constants::INSTRUMENT], $subResponse);
            }
        }

        $emiResponse[Constants::ENABLED] = count($emiResponse[Constants::INSTRUMENT]) > 0;

        return $emiResponse;

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
            return $instrument[1] === $type && $row[Constants::STATUS] === $status;
        });

        $instruments = self::getLeafValues($instruments);
        return array_values(array_unique($instruments));
    }

        /**
     * @param array $response
     * @param string $category
     * @return array
     */
    private static function getLeafValues(array $response): array
    {
        $instruments = [];
        foreach ($response as $row) {
            $instrument = explode(".", $row["instrument"]);
            array_push($instruments, $instrument[count($instrument)-1]);
        }
        return $instruments;
    }

    private static function getCardsResponse(array $response, string $status)
    {
        $cardsResponse = [
            Constants::ENABLED => false,
            Constants::INSTRUMENT => [],
        ];

        $enabled = false;
        foreach (Constants::$cardNetworks as $issuer)
        {
            $types = self::extractCardsResponse($response, $status, $issuer);

            if (count($types) > 0)
            {
                $enabled = true;
                array_push($cardsResponse[Constants::INSTRUMENT], [
                    Constants::ISSUER => $issuer,
                    Constants::TYPE   => $types
                ]);
            }
        }

        $cardsResponse[Constants::ENABLED] = $enabled;

        return $cardsResponse;
    }

    private static function extractCardsResponse(array $response, string $status, $issuer)
    {
        $instruments = array_filter($response, function ($row) use ($issuer, $status) {
            $instrument = explode(".", $row["instrument"]);
            return $instrument[1] === Constants::CARDS && $instrument[3] === $issuer && $row[Constants::STATUS] === $status;
        });

        $types = [];

        foreach ($instruments as $row)
        {
            $instrument = explode(".", $row["instrument"]);
            array_push($types, $instrument[2]);
        }

        return $types;
    }
}
