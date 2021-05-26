<?php

namespace RZP\Gateway\Netbanking\Federal;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use RZP\Constants\MailTags;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Models\Payment\Gateway;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'FBK_REFUND';

    const BASE_STORAGE_DIRECTORY = 'Federal/Refund/Netbanking/';

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
                    Timezone::IST)
                    ->format('Y-d-m');

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($row['payment']['id'], Action::AUTHORIZE);

            $prn = $row['payment']['id'];

            if ($netbanking->isTpv() === true)
            {
                $accountNumber = $netbanking->getAccountNumber();

                $prn .= '.' . $accountNumber;
            }

            $data[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'PRN'           => $prn,
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
        $date = $time = Carbon::now(Timezone::IST)->format('d_m_Y');

        return static::BASE_STORAGE_DIRECTORY . static::$fileToWriteName . '_' . $date;
    }
}
