<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Monolog\Logger;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Services\Scrooge;
use RZP\Models\Payment\Refund\Constants;

class Aubl extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AUSF;

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds): array
    {
        return $data;
    }

    public function createFile($data)
    {

    }

    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        return [
            Constants::SCROOGE_QUERY => [
                Constants::SCROOGE_REFUNDS => [
                    Constants::SCROOGE_GATEWAY    => static::GATEWAY,
                    Constants::SCROOGE_CREATED_AT => [
                        Constants::SCROOGE_GTE => $from,
                        Constants::SCROOGE_LTE => $to,
                    ],
                    Constants::SCROOGE_BASE_AMOUNT => [
                        Constants::SCROOGE_GT => 0,
                    ],
                    Constants::SCROOGE_PROCESSED_SOURCE => 'GATEWAY_API'
                ],
            ],
            Constants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];
    }

    protected function getRefundsFromScrooge(array $input): array
    {
        $returnData = [];

        $fetchFromScrooge = true;

        $skip = 0;

        do
        {
            $input[Constants::SCROOGE_SKIP] = $skip;

            try
            {
                $response = $this->app['scrooge']->getRefunds($input);

                $code = $response[Constants::RESPONSE_CODE];

                if (in_array($code, Scrooge::RESPONSE_SUCCESS_CODES, true) === true)
                {
                    $data = $response[Constants::RESPONSE_BODY][Constants::RESPONSE_DATA];

                    if (empty($data) === false)
                    {
                        foreach ($data as $value)
                        {
                            $returnData[] = $value;
                        }

                        if (count($data) < $this->fetchFromScroogeCount)
                        {
                            // Data is complete
                            $fetchFromScrooge = false;
                        }
                        else
                        {
                            $skip += $this->fetchFromScroogeCount;
                        }
                    }
                    else
                    {
                        // Data is complete
                        $fetchFromScrooge = false;
                    }
                }
                else
                {
                    return [[], false];
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::SCROOGE_FETCH_FILE_BASED_REFUNDS_FAILED,
                    [
                        'input' => $input,
                        'id'    => $this->gatewayFile->getId(),
                    ]
                );

                return [[], false];
            }
        }
        while ($fetchFromScrooge === true);

        return [$returnData, true];
    }
}
