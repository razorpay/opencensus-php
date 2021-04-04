<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class HdfcEmandate extends Hdfc
{
    const FILE_NAME              = 'HDFC_Emandate_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::HDFC_EMANDATE_REFUND;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = Payment\Gateway::NETBANKING_HDFC;
    const GATEWAY_CODE           = IFSC::HDFC;
    const BASE_STORAGE_DIRECTORY = 'Hdfc/Refund/Emandate/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                'Sr No'            => $index + 1,
                'Transaction date' => $date,
                'Order #'          => $row['payment']['id'],
                'Order Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'    => $row['refund']['amount'] / 100,
                'Merchant Code'    => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $formattedData;
    }

    /**
     * @param int $begin
     * @param int $end
     * @return PublicCollection
     */
    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        //
        // Regular flow - fetching refunds from API DB
        //

        $refunds = $this->repo->refund->fetchRefundsForMethodGatewaysBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            static::GATEWAY_CODE,
            $begin,
            $end,
            static::GATEWAY,
            Payment\Method::EMANDATE
        );

        return $refunds;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => static::GATEWAY,
                    RefundConstants::SCROOGE_BANK       => static::GATEWAY_CODE,
                    RefundConstants::SCROOGE_METHOD     => Payment\Method::EMANDATE,
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

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $time . '-0';
    }
}
