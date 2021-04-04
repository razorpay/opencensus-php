<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Mozart\PaylaterIcici\RefundFields;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class PaylaterIcici extends Base
{
    const FILE_NAME              = 'Icici_Paylater_Refunds';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::ICICI_PAYLATER_REFUND;
    const GATEWAY                = Payment\Gateway::PAYLATER_ICICI;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::WALLET;
    const ACQUIRER               = Payment\Processor\PayLater::ICICI;
    const PAYLATER               = Payment\Gateway::PAYLATER;
    const BASE_STORAGE_DIRECTORY = 'Icici/Refund/Paylater/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $count = 1;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('jS F Y');

                $formattedData[] = [
                    RefundFields::SERIAL_NO          => $count++,
                    RefundFields::PAYEE_ID           => $row['terminal']['gateway_merchant_id'],
                    RefundFields::SPID               => '',
                    RefundFields::BANK_REFERENCE_ID  => $row['gateway']['data']['bank_payment_id'],
                    RefundFields::TRANSACTION_DATE   => $date,
                    RefundFields::TRANSACTION_AMOUNT => $row['payment']['amount'] / 100,
                    RefundFields::REFUND_AMOUNT      => $row['refund']['amount'] / 100,
                    RefundFields::TRANSACTION_ID     => $row['payment']['id'],
                    RefundFields::REFUND_MODE        => 'C',
                    RefundFields::REMARKS            => '',
                ];
        }

        return $formattedData;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $totalAmount = array_reduce($data, function ($carry, $item)
        {
            $carry += ($item['refund']['amount'] / 100);

            return $carry;
        });

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl,
            'count'      => count($data),
            'amount'     => $totalAmount,
            'date'       => $today,
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        // the serial no is hardcoded as the file is generated only once
        return static::BASE_STORAGE_DIRECTORY . self::FILE_NAME . $date;
    }

    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        $refunds = $this->repo->refund->fetchRefundsForGatewaysBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            static::ACQUIRER,
            $begin,
            $end,
            static::PAYLATER
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
                    RefundConstants::SCROOGE_GATEWAY          => static::PAYLATER,
                    RefundConstants::SCROOGE_GATEWAY_ACQUIRER => static::ACQUIRER,
                    RefundConstants::SCROOGE_CREATED_AT       => [
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

        if (empty($refundIds) === false) {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }
}
