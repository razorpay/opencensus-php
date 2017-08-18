<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Gateway\Netbanking\Federal\Constants;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class NetbankingFederal extends Processor\Base
{
    use GenerateRefundFile;
    use FileHandlerTrait;

    const FILE_NAME = 'FBK_REFUND';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::FEDERAL_NETBANKING_REFUND;

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    Timezone::IST)
                    ->format('Y-d-m');

            $formattedData[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'PRN'           => $row['payment']['id'],
                'FREEFIELD'     => Constants::FREEFIELD,
                'BID'           => $row['gateway']['bank_payment_id'],
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getTextData($data)
    {
        $txt = $this->generateText($data, '|', true);

        return $txt;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        return self::FILE_NAME . '_' . $date;
    }
}
