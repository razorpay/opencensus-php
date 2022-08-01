<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Config;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Gateway\Netbanking\Bob\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Bob extends Base
{
    use FileHandler;

    const FILE_NAME              = 'BOB_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::BOB_NETBANKING_REFUND;
    const BASE_STORAGE_DIRECTORY = 'Bob/Refund/Netbanking/';

    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = Payment\Gateway::NETBANKING_BOB;
    const GATEWAY_CODE           = [Payment\Processor\Netbanking::BARB_R, Payment\Processor\Netbanking::BARB_C, IFSC::VIJB];

    protected function formatDataForFile(array $inputData): string
    {
        $totalAmount = 0;

        $data = [];

        foreach ($inputData as $row)
        {
            if ((empty($row['gateway']['account_number']) === true) and (empty($row['gateway']['bank_account_number']) === true))
            {
                throw new Exception\LogicException(
                    'Account number missing for refund file generation',
                    null,
                    [
                        'gateway' => 'netbanking_bob',
                        'row'     => $row,
                    ]
                );
            }

            $data[] = $this->getDataForRow(
                $this->fetchBankAccountNumber($row),
                $row['refund']['amount'],
                $row['refund']['id'],
                Constants::REFUND_CREDIT,
                trim($this->fetchBankPaymentId($row))
            );

            $totalAmount += $row['refund']['amount'];
        }

        array_unshift(
            $data,
            $this->getDataForRow(
                Config::get('gateway.netbanking_bob.pooling_account_number'),
                $totalAmount,
                Constants::REFUND_PARTICULARS_HEAD,
                Constants::REFUND_DEBIT
            )
        );

        return $this->generateText($data, '', true);
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway']['bank_transaction_id'];
        }

        return $data['gateway']['bank_payment_id'];
    }

    protected function fetchBankAccountNumber($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway']['bank_account_number'];
        }

        return $data['gateway']['account_number'];
    }

    protected function getDataForRow($accountNumber, $amount, $particulars, $type, $bankRefNumber = ''): array
    {
        $amt = $this->getFormattedAmountString($amount);

        $data = [
            str_pad(trim($accountNumber), 16, ' '),
            'INR',
            substr($accountNumber, 0, 4),
            $type,
            $amt,
            $particulars,
            $bankRefNumber
        ];

        return $data;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        // Amount is of type NUMBER(14,2). i.e 14 digits before decimal point and 2 digits after decimal point.
        return str_pad($amt, 17, '0', STR_PAD_LEFT);
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
