<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class Hdfc extends Base
{
    const FILE_NAME              = 'HDFC_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::HDFC_NETBANKING_REFUND;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = Payment\Gateway::NETBANKING_HDFC;
    const GATEWAY_CODE           = IFSC::HDFC;
    const BASE_STORAGE_DIRECTORY = 'Hdfc/Refund/Netbanking/';

    /**
     * Formats the data fetched from database as per HDFC netbanking refund file format
     */
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
                'Bank reference #' => $this->fetchBankPaymentId($row),
                'Order #'          => $row['payment']['id'],
                'Order Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'    => $row['refund']['amount'] / 100,
                'Merchant Code'    => $row['terminal']['gateway_merchant_id'],
            ];
        }

        return $formattedData;
    }

    /**
     * Fetches required data to be sent as part of the mail to HDFC
     */
    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
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
            static::GATEWAY
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
                    RefundConstants::SCROOGE_METHOD     => Payment\Method::NETBANKING,
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

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }
}
