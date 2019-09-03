<?php


namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingIdbi\RefundFields;

class Idbi extends Base
{
    use FileHandler;

    // todo : refund file name
    const FILE_NAME = 'IDBI REFUND';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::IDBI_NETBANKING_REFUND;
    const GATEWAY = Payment\Gateway::NETBANKING_IDBI;
    const GATEWAY_CODE = IFSC::IBKL;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];
        $n =0;
        foreach ($data as $index => $row) {
            $date = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d/m/Y');

            $formattedData[] = [
                RefundFields::SR_NO => $n++,
                RefundFields::TRANSACTION_DATE => $date,
                RefundFields::PAYMENT_ID => $row['payment']['id'],
                RefundFields::BANK_REFERENCE_ID => $this->fetchBankPaymentId($row['gateway']['raw']),
                RefundFields::TRANSACTION_AMOUNT => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                RefundFields::REFUND_AMOUNT => number_format($row['refund']['amount'] / 100, 2, '.', ''),
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return static::FILE_NAME . $dateTime;
    }

    protected function fetchBankPaymentId($data)
    {
        $dataArray = json_decode($data, true);

        return $dataArray['bank_payment_id'];
    }
}
