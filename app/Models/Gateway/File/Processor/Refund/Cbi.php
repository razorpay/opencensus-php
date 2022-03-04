<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Config;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Mozart\NetbankingCbi\RefundFields;

class Cbi extends Base
{
    use FileHandler;

    // todo : refund file name
    const FILE_NAME              = 'CBIRefund_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::CBI_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CBI;
    const GATEWAY_CODE           = IFSC::CBIN;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Cbi/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $from_account_number = str_pad(Config::get('gateway.mozart.netbanking_cbi.account_number'), 17, "0", STR_PAD_LEFT);

            if ($row['terminal']['tpv'] === 1)
            {
                $from_account_number = str_pad(Config::get('gateway.mozart.netbanking_cbi.account_number_tpv'), 17, "0", STR_PAD_LEFT);
            }

            $to_account_number = str_pad(substr($this->fetchBankAccountNumber($row), 3), 17, "0", STR_PAD_LEFT);

            $narration_text = str_pad($row['merchant']->getFilteredDba(), 50, " ", STR_PAD_RIGHT);

            $transaction_amount = str_pad($row['refund']['amount'], 17, '0', STR_PAD_LEFT);

            $reference_no = str_pad($row['refund']['id'], 20, "0", STR_PAD_LEFT);

            $formattedData[] = [
                RefundFields::TYPE_OF_TRANSACTION  => 'DD',
                RefundFields::FROM_ACCOUNT_NUMBER  => $from_account_number,
                RefundFields::TO_ACCOUNT_NUMBER    => $to_account_number,
                RefundFields::TRANSACTION_AMOUNT   => $transaction_amount,
                RefundFields::NARRATION_TEXT       => $narration_text,
                RefundFields::REFERENCE_NO         => $reference_no,
            ];
        }

        $formattedData = $this->getTextData($formattedData, "", "");

        return $formattedData;
    }

    protected function fetchBankAccountNumber($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
        }
        return $data['gateway']['data']['account_number'];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('dmY');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function collectPaymentData(Payment\Entity $payment): array
    {
        $terminal = $payment->terminal;

        $merchant = $payment->merchant;

        $col['payment'] = $payment->toArray();

        $col['terminal'] = $terminal->toArray();

        $col['merchant'] = $merchant;

        return $col;
    }
}
