<?php

namespace RZP\Services\Settlements;

use RZP\Exception\RuntimeException;
use Carbon\Carbon;

/**
 * class for handling all requests from merchant dashboard
 */
class MerchantDashboard extends Base
{
    //********************* All endpoints for merchant dashboard are configured here ***************************//
    const MERCHANT_DASHBOARD_MERCHANT_CONFIG_GET   = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/GetConfigForDashboard';

    /**
     * Merchant Config Service Get for Merchant dashboard
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantDashboardConfigGet(array $input) : array
    {
        return $this->makeRequest(self::MERCHANT_DASHBOARD_MERCHANT_CONFIG_GET, $input, self::SERVICE_MERCHANT_DASHBOARD);
    }


    /**
     * @param array $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function getNextSettlementAmount(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::GET_NEXT_SETTLEMENT_AMOUNT, $input, self::SERVICE_MERCHANT_DASHBOARD);
    }

    /**
     * Settlement Timeline Modal
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function settlementTimelineModalGet(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::GET_SETTLEMENT_TIMELINE_MODAL, $input, self::SERVICE_MERCHANT_DASHBOARD, $mode);
    }

    /**
     * Fetch Settlement Source Transaction
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function getSettlementSourceTransaction(array $input)
    {
        return $this->makeRequest( self::GET_SETTLEMENT_SOURCE_TRANSACTIONS, $input, self::SERVICE_MERCHANT_DASHBOARD);
    }

    public function getSettlementForTransaction(array $input)
    {
        return $this->makeRequest(self::GET_SETTLEMENT_FOR_TRANSACTIONS, $input, self::SERVICE_MERCHANT_DASHBOARD);
    }


    /*
     * getHolidaysForYearAndCountry method calls settlements service
     * to get the list of holidays for a given country & year
     * the response format here is the one expected by getHolidayListForYear API
     * */
    public function getHolidaysForYearAndCountry(int $year, $countryCode, string $timezone)
    {
        $input = [
            'country_code' => $countryCode,
            'year' => $year,
            'include_weekends' => false,
        ];

        $response = $this->makeRequest(self::GET_HOLIDAYS_FOR_YEAR_AND_COUNTRY, $input, self::SERVICE_MERCHANT_DASHBOARD);

        if (!array_key_exists("holidays", $response)) {
            throw new RuntimeException(
                'Unexpected response received from settlements service.',
                [
                    'response_body' => $response,
                ]);
        }

        $holidays = $response["holidays"];
        $holidayDetails = [];

        foreach ($holidays as $month => $monthlyHolidays)
        {
            if (!array_key_exists("details", $monthlyHolidays)) {
                continue;
            }

            foreach ($monthlyHolidays["details"] as $day => $details)
            {
                $date = Carbon::createFromDate($year, $month, $day, $timezone);

                $holidayDetails[] = [
                    'date'        => $date->format('d/m/Y'),
                    'description' => $details["description"],
                ];
            }
        }
        return [
            $year => $holidayDetails
        ];
    }
}
