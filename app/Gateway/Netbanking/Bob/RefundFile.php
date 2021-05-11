<?php

namespace RZP\Gateway\Netbanking\Bob;

use Mail;
use Config;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'BOB_Netbanking_Refunds';

    const BASE_STORAGE_DIRECTORY = 'Bob/Refund/Netbanking/';

    public function generate($input)
    {
        $text = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $text,
            $fileName,
            FileStore\Type::BOB_NETBANKING_REFUND
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

    protected function getRefundData(array $input)
    {
        $totalAmount = 0;

        $data = [];

        foreach ($input['data'] as $row)
        {
            if (empty($row['gateway']['account_number']) === true)
            {
                throw new Exception\LogicException(
                    'Recon needs to be run before generation of refund file',
                    null,
                    [
                        'gateway' => 'netbanking_bob',
                        'row'     => $row,
                    ]
                );
            }

            $data[] = $this->getDataForRow(
                $row['gateway']['account_number'],
                $row['refund']['amount'],
                $row['refund']['id']
            );

            $totalAmount += $row['refund']['amount'];
        }

        array_unshift(
            $data,
            $this->getDataForRow(
                Config::get('gateway.netbanking_bob.pooling_account_number'),
                $totalAmount,
                Constants::REFUND_PARTICULARS_HEAD,
                Constants::REFUND_DEBIT
            )
        );

        return $this->generateText($data, '', true);
    }

    protected function getDataForRow($accountNumber, $amount, $particulars, $type = Constants::REFUND_CREDIT)
    {
        $amt = $this->getFormattedAmountString($amount);

        $data = [
            str_pad(trim($accountNumber), 16, ' '),
            'INR',
            substr($accountNumber, 0, 4),
            $type,
            $amt,
            $particulars,
        ];

        return $data;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        // Amount is of type NUMBER(14,2). i.e 14 digits before decimal point and 2 digits after decimal point.
        return str_pad($amt, 17, '0', STR_PAD_LEFT);
    }

    protected function sendRefundEmail($fileData = [], array $email = [])
    {
        $refundFileMail = new RefundFileMail($fileData, Gateway::NETBANKING_BOB, $email);

        Mail::queue($refundFileMail);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $this->mode . '_' . $time;
    }
}
