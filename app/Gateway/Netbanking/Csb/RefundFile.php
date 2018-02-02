<?php

namespace RZP\Gateway\Netbanking\Csb;

use Mail;
use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'CBS_RAZORPAY';

    const EMAIL_BODY    = 'Please forward the Cbs Netbanking refunds file to the operations team';

    const BANK_CODE     = 'CSB';

    const MERCHANT_NAME = 'RAZORPAY';

    public function generate($input)
    {
        list($data, $totalAmount, $count) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::CSB_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $fileData = [
            'file_path'  => $file['local_file_path'],
            'file_name'  => basename($file['local_file_path']),
            'signed_url' => $signedFileUrl,
            'count'      => count($data),
            'amount'     => number_format($totalAmount, 2, '.', ''),
            'date'       => $today
        ];

        $this->sendRefundEmail($fileData, (array) $input['email']);

        return $file['local_file_path'];
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0;

        $count = 0;

        $data = [];

        foreach ($input['data'] as $ind => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-m-y');

            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d-m-y');

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($row['payment']['id'],
                                                                            Base\Action::AUTHORIZE);

            $data[] = [
                'Sr.No'              => ++$ind,
                'Refund Id'          => $row['refund']['id'],
                'Bank Id'            => self::BANK_CODE,
                'Merchant Name'      => self::MERCHANT_NAME,
                'Txn date'           => $date,
                'Refund Date'        => $refundDate,
                'Bank Merchant Code' => $netbanking['reference1'],
                'Bank Ref No'        => $netbanking['bank_payment_id'],
                'PGI Reference No'   => $row['payment']['id'], // TODO: Check if this is actually payment id
                'Txn Amount(Rs Ps)'  => $row['payment']['amount'] / 100,
                'Refund'             => $row['refund']['amount'] / 100,
            ];

            $totalAmount += $row['refund']['amount'] / 100;

            $count++;
        }

        return [$data, $totalAmount, $count];
    }

    protected function getTextData($data)
    {
        return $this->generateText($data, '|', true);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = $time = Carbon::now(Timezone::IST)->format('dmY');

        return self::$fileToWriteName . '_' . $date . '_' . 'REFUND';
    }

    protected function sendRefundEmail($fileData = [], $email = null)
    {
        $refundFileMail = new RefundFileMail(
            $fileData,
            Payment\Gateway::NETBANKING_CSB,
            $email,
            'emails.message');

        Mail::queue($refundFileMail);
    }
}
