<?php

namespace RZP\Gateway\Netbanking\Corporation;

use Carbon\Carbon;
use Mail;
use Config;

use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'CORPORATION_Netbanking_Refunds';

    const BASE_STORAGE_DIRECTORY = 'Corporation/Refund/Netbanking/';
    // TODO: Remove the below data and use env to store them
    const POOLING_ACCOUNT_BR_CODE = '1234';
    const POOLING_ACCOUNT_TYPE    = 'CA';
    const POOLING_ACCOUNT_SUBTYPE = '01';

    const FIXED_VALUE = '824603';

    const FIXED_STRING_REAR = '000000000000000000000000000000000000000000   0000000   00000000000000Refunds';

    const FIXED_VALUE_REAR = '26645097';

    public function generate($input)
    {
        $text = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $text,
            $fileName,
            FileStore\Type::CORPORATION_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];
    }

    protected function sendRefundEmail($fileData = [], array $email = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_CORPORATION, $email);

        Mail::queue($refundFileMail);
    }

    protected function getRefundData(array $input)
    {
        $totalAmount = 0;

        $count = 0;

        $data = [];

        // For first line
        $data[] = $this->getDataForRow(
            self::POOLING_ACCOUNT_BR_CODE,
            Carbon::now(Timezone::IST)->timestamp,
            Constants::REFUND_FILE_DEBIT,
            '00000000120000',
            self::POOLING_ACCOUNT_TYPE,
            self::POOLING_ACCOUNT_SUBTYPE,
            Config::get('gateway.netbanking_corporation.pooling_account_number'),
            true
        );

        foreach ($input['data'] as $row)
        {
            $data[] = $this->getDataForRow(
                $row['gateway'][NetbankingEntity::ACCOUNT_BRANCHCODE],
                $row['payment']['created_at'],
                Constants::REFUND_FILE_CREDIT,
                '00000000040000',
                str_pad($row['gateway'][NetbankingEntity::ACCOUNT_TYPE], 5, ' ', STR_PAD_RIGHT),
                $row['gateway'][NetbankingEntity::ACCOUNT_SUBTYPE],
                $row['gateway'][NetbankingEntity::ACCOUNT_NUMBER]
            );

            $totalAmount += $row['refund']['amount'];

            $count++;
        }

        return $this->generateText($data, '', true);
    }

    protected function getDataForRow(
        $accountBrCode,
        $date,
        $mode,
        $amount,
        $accountType,
        $accountSubType,
        $accountNumber,
        $firstLine = false
    )
    {
        $date = Carbon::createFromTimestamp($date, Timezone::IST)
                        ->format('Ymd');

        $data = [
            $accountBrCode,
            $date,
            $date,
            $mode,
            self::FIXED_VALUE,
            $amount,
            $accountType,
            $accountSubType,
            $accountNumber,
            $this->getRearString($firstLine, $date)
        ];

        return $data;
    }

    protected function getRearString($firstLine, $date = null)
    {
        $lastString = self::FIXED_VALUE_REAR;

        if ($firstLine === true)
        {
            $date = Carbon::createFromTimestamp($date)
                            ->format('d.m.Y');

            $lastString = ' Dt: ' . $date;
        }

        return self::FIXED_STRING_REAR . $lastString;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
