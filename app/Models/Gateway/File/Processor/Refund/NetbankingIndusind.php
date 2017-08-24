<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Gateway\Netbanking\Indusind\RefundFileFields;

class NetbankingIndusind extends Processor\Base
{
    use GenerateRefundFile;
    use FileHandlerTrait;

    const FILE_NAME = 'PGReconRAZORPAY';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::INDUSIND_NETBANKING_REFUND;

    protected $type = Payment\Entity::BANK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $index => $row)
        {
            $formattedData[] = [
                RefundFileFields::SERIAL_NO          => $index + 1,
                RefundFileFields::TRANSACTION_ID     => $row['payment']['id'],
                RefundFileFields::REFUND             => RefundFileFields::REFUND_MODE,
                RefundFileFields::BANK               => RefundFileFields::BANK_NAME,
                RefundFileFields::REFUND_AMOUNT      => number_format($row['refund']['amount'] / 100, 2, '.', ''),
                RefundFileFields::BANK_REFERENCE_ID  => $row['gateway']['bank_payment_id']
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getTextData(array $data, string $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine . $txt;

        return $txt;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        if ($this->isTestMode() === true)
        {
            return static::FILE_NAME . $time . $this->mode;
        }

        return static::FILE_NAME . $time;
    }

    public function sendMail()
    {
        ;
    }
}
