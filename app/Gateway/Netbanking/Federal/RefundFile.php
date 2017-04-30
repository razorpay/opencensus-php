<?php

namespace RZP\Gateway\Netbanking\Federal;

use Mail;
use Carbon\Carbon;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as MailConstants;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'FBK_REFUND';

    const EMAIL_BODY = 'Please forward the Federal Netbanking refunds file to the operations team';

    protected static $headers = [
        'Payee ID',
        'Date',
        'PRN',
        'FREEFIELD',
        'BID',
        'TXN Amount',
        'Refund Amount'
    ];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::FEDERAL_NETBANKING_REFUND
        );

        $file = $creator->get();

        $fileData = [
            'file_path' => $file['local_file_path']
        ];

        $this->sendRefundEmail($fileData);

        return [$totalAmount, $file['local_file_path']];
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    'Asia/Kolkata')
                    ->format('Y-d-m');

            $data[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'PRN'           => $row['payment']['id'],
                'FREEFIELD'     => Constants::FREEFIELD,
                'BID'           => $row['gateway']['bank_payment_id'],
                'TXN Amount'    => $row['payment']['amount'],
                'Refund Amount' => $row['refund']['amount']
            ];

            $totalAmount += $row['refund']['amount'];
        }

        $txt = $this->getTextData($data);

        return [$txt, $totalAmount];
    }

    protected function getTextData($data)
    {
        $txt = $this->generateText($data, '|', true);

        return $txt;
    }

    protected function sendRefundEmail($fileData = [])
    {
        $refundFileMail = new RefundFileMail($fileData, MailConstants::NETBANKING_FEDERAL);

        Mail::queue($refundFileMail);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = $time = Carbon::now('Asia/Kolkata')->format('d_m_Y');

        return self::$fileToWriteName . '_' . $date;
    }
}
