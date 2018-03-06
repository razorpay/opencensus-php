<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Gateway\Netbanking\Bob\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Config;

class Bob extends Base
{
    use FileHandler;

    const FILE_NAME              = 'BOB_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::BOB_NETBANKING_REFUND;

    const GATEWAY_CODE           = Payment\Processor\Netbanking::BARB_R;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = 'netbanking';

    protected function formatDataForFile(array $inputData)
    {
        $totalAmount = 0;

        $data = [];

        foreach ($inputData as $row)
        {
            if (empty($row['gateway']['account_number']) === true)
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
                $row['gateway']['account_number'],
                $row['refund']['amount'],
                $row['refund']['id']
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

    protected function getDataForRow($accountNumber, $amount, $particulars, $type = Constants::REFUND_CREDIT)
    {
        $amt = $this->getFormattedAmountString($amount);

        $data = [
            str_pad(trim($accountNumber), 16, ' '),
            'INR',
            substr($accountNumber, 0, 4),
            $type,
            $amt,
            $particulars,
        ];

        return $data;
    }

    protected function getFormattedAmountString(int $amount): String
    {
        $amt = number_format(($amount / 100), 2, '.', '');

        // Amount is of type NUMBER(14,2). i.e 14 digits before decimal point and 2 digits after decimal point.
        return str_pad($amt, 17, '0', STR_PAD_LEFT);
    }
}