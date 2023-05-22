<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Services\Scrooge;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Services\NbPlus\Netbanking;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\NetbankingDbs\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class Dbs extends Base
{

    use FileHandler;

    const FILE_NAME                  = 'HCODI01.Razorpay_Refunds_';
    const EXTENSION                  = FileStore\Format::XLSX;
    const FILE_TYPE                  = FileStore\Type::DBS_NETBANKING_REFUND;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::DBSS;
    const GATEWAY                    = Payment\Gateway::NETBANKING_DBS;
    const BASE_STORAGE_DIRECTORY     = 'Dbs/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d/m/Y H:i:s');

            $formattedData[] = [
                Constants::MERCHANT_ID              => 'RazorPay',
                Constants::MERCHANT_ORDER_ID        => $row['refund']['id'],
                Constants::BANK_REF_NO              => $row['refund']['reference1'],
                Constants::TXN_AMOUNT               => $this->getFormattedAmount($row['refund']['amount']),
                Constants::ORDER_TYPE               => $this->getOrderType($row),
                Constants::STATUS                   => $this->getStatus($row),
                Constants::TXN_DATE                 => $date,
                Constants::PAYMENT_ID               => $row['payment']['id'],
                Constants::PAYMENT_BANK_REF_NO      => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY_His');

        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . $date;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getRefundsFromScrooge(array $input): array
    {
        $returnData = [];

        $fetchFromScrooge = true;

        $skip = 0;

        do
        {
            $input[RefundConstants::SCROOGE_SKIP] = $skip;

            try
            {
                $response = $this->app['scrooge']->getRefunds($input);

                $code = $response[RefundConstants::RESPONSE_CODE];

                if (in_array($code, Scrooge::RESPONSE_SUCCESS_CODES, true) === true)
                {
                    $data = $response[RefundConstants::RESPONSE_BODY][RefundConstants::RESPONSE_DATA];

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
                    Trace::ERROR,
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

    /**
     * @throws LogicException
     */
    public function getStatus($row): string
    {
        if (($row['refund']['status'] === 'processed') and ($row['refund']['processed_source'] === 'GATEWAY_API'))
        {
            return 'Success';
        }
        else if (($row['refund']['status'] === 'processed') and ($row['refund']['processed_source'] === 'GATEWAY_RECON'))
        {
            return 'Success';
        }
        else if (($row['refund']['status'] === 'processed') and ($row['refund']['processed_source'] === 'SYSTEM_MAIL'))
        {
            return 'To be processed';
        }
        else
        {
            throw new LogicException('Should not reach here');
        }
    }

    public function getOrderType($row): string
    {
        $refundStatus = $this->getStatus($row);

        if ($refundStatus === 'Success')
            return 'Refund';
        else
            return 'Offline/Manual refund';
    }

    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        return [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => static::GATEWAY,
                    RefundConstants::REFUND_GATEWAY     => static::GATEWAY,
                    RefundConstants::SCROOGE_BANK       => static::GATEWAY_CODE,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];
    }
}
