<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Bob\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

use Config;

class Bob extends Base
{
    use FileHandler;

    const FILE_NAME              = 'BOB_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::BOB_NETBANKING_REFUND;

    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const GATEWAY                = 'netbanking_bob';

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
                $row['refund']['id'],
                Constants::REFUND_CREDIT,
                trim($row['gateway']['bank_payment_id'])
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

    protected function getDataForRow($accountNumber, $amount, $particulars, $type, $bankRefNumber = '')
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

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $refunds = $this->repo->refund->fetchRefundsForGatewaysBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            [Payment\Processor\Netbanking::BARB_R, Payment\Processor\Netbanking::BARB_C],
            $begin,
            $end,
            static::GATEWAY
        );

        return $refunds;
    }
}
