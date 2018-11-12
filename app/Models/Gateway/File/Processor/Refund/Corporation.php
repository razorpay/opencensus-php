<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Corporation extends Base
{
    use FileHandler;

    const FILE_NAME                  = 'CORPBANK';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::CORPORATION_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_CORPORATION;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::CORP;

    const HEADERS = [
        'MERCHANT_CODE',
        'TXN_EXECUTED_DATE',
        'BANK_TXN_ID',
        'MERCHANT_TXN_ID',
        'TXN_ORG_AMOUNT',
        'BILLER_NAME',
        'TXN_REFUND_AMOUNT',
        'TXN_REFUND_DATE',
        'REFUND_REASON'
    ];

    private $mid;

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        // MID remains same for all rows
        $this->setMerchantId($data[0]);

        foreach ($data as $row)
        {
            $paymentDate = Carbon::createFromTimestamp($row['payment']['created_at'], 'Asia/Kolkata')
                                   ->format('dmY');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], 'Asia/Kolkata')
                                  ->format('dmY');

            if (empty($row['refund']['notes']) === false)
            {
                $refundReason = $row['refund']['notes'];
            }
            else
            {
                $refundReason = 'Refund initiated by Customer';
            }

            $formattedData[] = [
                $this->mid,
                $paymentDate,
                $row['gateway']['bank_payment_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $row['merchant']['billing_label'],
                number_format($row['refund']['amount'] / 100, 2, '.', ''),
                $refundDate,
                $refundReason
            ];
        }

        $initialLine = $this->getInitialLine();

        $formattedData = $this->getTextData($formattedData, $initialLine);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return $this->mid . '_' . $date . '_' . self::FILE_NAME;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $totalAmount = array_reduce(
            $data,
            function(int $carry, array $item)
            {
                $carry += $item['refund']['amount'];

                return $carry;
            },
            0);

        $totalAmount = $totalAmount / 100;

        $totalAmount = number_format($totalAmount, 2, '.', '');

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'amount'     => $totalAmount,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
    }

    private function setMerchantId($row)
    {
        if ($this->mode === Mode::LIVE)
        {
            $this->mid = $row['terminal']['gateway_merchant_id'];
        }
        else
        {
            $this->mid = $this->app['config']['gateway']['netbanking_corporation']['test_merchant_id'];
        }
    }

    /**
     * Overriding this because refund file needs merchant name.
     * Merchant is thus included in data by accessing it through payment
     *
     * @param  PublicCollection $refunds
     *
     * @return array
     */
    public function generateData(PublicCollection $refunds)
    {
        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;

            $terminal = $payment->terminal;

            $merchant = $payment->merchant;

            $col['refund'] = $refund->toArray();

            $col['payment'] = $payment->toArray();

            $col['terminal'] = $terminal->toArray();

            $col['merchant'] = $merchant->toArray();

            $data[] = $col;
        }

        $data = $this->addGatewayEntitiesToData($data, $refunds);

        return $data;
    }
}
