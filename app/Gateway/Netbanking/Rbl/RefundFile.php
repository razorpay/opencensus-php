<?php

namespace RZP\Gateway\Netbanking\Rbl;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\MailTags;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'RBL_REFUND';

    const EMAIL_BODY = 'Please forward the RBL Netbanking refunds file to the operations team';

    protected static $headers = [

    ];

    public function generate($input)
    {
        list($txt, $totalAmount, $count) = $this->getRefundData($input);

        $fileName = $this->getFileToWriteNameWithoutExt();

        $creator = $this->createFile(
            FileStore\Format::TXT,
            $txt,
            $fileName,
            FileStore\Type::FEDERAL_NETBANKING_REFUND
        );

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        return [
            'total_amount'    => $totalAmount,
            'count'           => $count,
            'signed_url'      => $signedFileUrl,
            'local_file_path' => $file['local_file_path'],
        ];
    }

    protected function getRefundData($input)
    {
        $totalAmount = 0;

        $count = 0;

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
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100
            ];

            $totalAmount += $row['refund']['amount'] / 100;

            $count++;
        }

        $txt = $this->getTextData($data);

        return [$txt, $totalAmount, $count];
    }

    protected function getTextData($data)
    {
        $txt = $this->generateText($data, '|', true);

        return $txt;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = $time = Carbon::now('Asia/Kolkata')->format('d_m_Y');

        return self::$fileToWriteName . '_' . $date;
    }
}
