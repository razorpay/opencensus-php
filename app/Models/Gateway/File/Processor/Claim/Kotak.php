<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Kotak extends Processor\Base
{
    use GenerateClaimFile;
    use FileHandlerTrait;

    const TPV_FILE_NAME     = 'Kotak_Netbanking_Claim_OTRAZORPAY';
    const NON_TPV_FILE_NAME = 'Kotak_Netbanking_Claim_OSRAZORPAY';
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::KOTAK_NETBANKING_CLAIM;
    const GATEWAY           = Payment\Gateway::NETBANKING_KOTAK;

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d-M-Y');

            $formattedData[] = [
                $index + 1,
                $row['gateway']['merchant_code'],
                $date,
                $row['gateway']['int_payment_id'],
                $row['payment']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    public function sendMail()
    {
        ;
    }

    protected function getTextData($data, $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine.$txt;

        return $txt;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $name = ($this->gatewayFile->getTpv() === true) ? static::TPV_FILE_NAME : static::NON_TPV_FILE_NAME;

        return $name . '_' . $this->mode . '_' . $time;
    }
}
