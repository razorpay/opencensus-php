<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Axis extends Base
{
    use FileHandler;

    const CORPORATE_FILE_NAME     = 'IConnect_Refund_RAZORPAY_CORP';
    const NON_CORPORATE_FILE_NAME = 'IConnect_Refund_RAZORPAY';
    const EXTENSION               = FileStore\Format::TXT;
    const FILE_TYPE               = FileStore\Type::AXIS_NETBANKING_REFUND;
    const GATEWAY                 = Payment\Gateway::NETBANKING_AXIS;
    const GATEWAY_CODE            = IFSC::UTIB;
    const PAYMENT_TYPE_ATTRIBUTE  = Payment\Entity::BANK;

    const HEADERS = [
        'Payee id', // pid
        'Payee name', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'AMOUNT',
        'DATETIME',
        'REFUND Amount',
    ];

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $corporate = $this->gatewayFile->getCorporate();

        $refunds = $this->repo->refund->fetchCorporateRefundsBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            static::GATEWAY_CODE,
            $begin,
            $end,
            static::GATEWAY,
            $corporate
        );

        return $refunds;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], 'Asia/Kolkata')
                    ->format('Y/m/d');

            $formattedData[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $row['gateway']['bank_payment_id'],
                $row['terminal']['gateway_merchant_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date,
                number_format($row['refund']['amount'] / 100, 2, '.', '')
            ];
        }

        $initialLine = $this->getInitialLine('~~');

        $formattedData = $this->getTextData($formattedData, $initialLine, '~~');

        return $formattedData;
    }

    public function sendFile($data)
    {
        return;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $name = ($this->gatewayFile->getCorporate() === true) ?
            static::CORPORATE_FILE_NAME :
            static::NON_CORPORATE_FILE_NAME;

        if ($this->isTestMode() === true)
        {
            return $name . '_' . $time . '_' . $this->mode . '_1';
        }

        return $name . '_' . $time . '_1';
    }
}
