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

    protected $type = Payment\Entity::BANK;

    const REFUND_TYPE = 'refund';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $amount = 0;
        $index = 0;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('dmY');

            $account_number = str_pad(substr($this->fetchBankAccountNumber($row), 3), 17, "0", STR_PAD_LEFT);

            $narration_text = str_pad($row['merchant']->getFilteredDba(), 50, " ", STR_PAD_RIGHT);

            $transaction_amount = str_pad($row['refund']['amount'], 16, '0', STR_PAD_LEFT);

            $formattedData[] = [
                RefundFields::TYPE_OF_TRANSACTION  => '01',
                RefundFields::ACCOUNT_NUMBER       => $account_number,
                RefundFields::TRANSACTION_AMOUNT   => $transaction_amount,
                RefundFields::NARRATION_TEXT       => $narration_text,
                RefundFields::REFERENCE_NO         => '        ',
                RefundFields::VALUE_DATE           => $date,
            ];

            ++$index;

            $amount = $amount + $row['refund']['amount'];
        }
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $transaction_amount = str_pad($amount, 16, '0', STR_PAD_LEFT);

        $narration_text = str_pad("Debit to Razorpay", 50, " ", STR_PAD_RIGHT);

        $account_number = str_pad(Config::get('gateway.mozart.netbanking_cbi.account_number'), 17, "0", STR_PAD_LEFT);

        $formattedData[$index] = [
            RefundFields::TYPE_OF_TRANSACTION  => "51",
            RefundFields::ACCOUNT_NUMBER       => $account_number,
            RefundFields::TRANSACTION_AMOUNT   => $transaction_amount,
            RefundFields::NARRATION_TEXT       => $narration_text,
            RefundFields::REFERENCE_NO         => "        ",
            RefundFields::VALUE_DATE           => $date
        ];

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

        return static::FILE_NAME . $dateTime;
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
