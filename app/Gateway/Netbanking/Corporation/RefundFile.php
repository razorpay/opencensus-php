<?php

namespace RZP\Gateway\Netbanking\Corporation;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    // TODO: Use env variables for these
    const DEBIT_ACCOUNT             = '12313123123132123';
    const POOLING_ACCOUNT_BR_CODE   = '1234';
    const CUSTOMER_ACCOUNT_BR_CODE  = '4321';
    const SELF_ACCOUNT_NUMBER       = '234567';
    const OTHERS_ACCOUNT_NUMBER     = '987654';

    const FIXED_VALUE = '824603';

    const FIXED_STRING_REAR = '000000000000000000000000000000000000000000   0000000   00000000000000Refunds';

    const FIXED_VALUE_REAR = '26645097';

    public function generate($input)
    {
        sd($input);
        list($txt, $totalAmount, $count) = $this->getRefundData($input);
    }

    protected function getRefundData(array $input)
    {
        $totalAmount = 0;

        $count = 0;

        $data = [];

        // For first line
        array_push($data, getDataForRow(
                self::POOLING_ACCOUNT_BR_CODE,
                Constants::REFUND_FILE_DEBIT,
                '00000000120000',
                Constants::REFUND_FILE_ACCOUNT_TYPE_1,
                Constants::REFUND_FILE_ACCOUNT_SUB_TYPE,
                self::SELF_ACCOUNT_NUMBER,
                true
            )
        );

        foreach ($input['data'] as $row)
        {
            sd($row);
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], Timezone::IST)
                    ->format('Ymd');

            array_push($data, getDataForRow(
                    self::CUSTOMER_ACCOUNT_BR_CODE,
                    Constants::REFUND_FILE_CREDIT,
                    '00000000040000',
                    Constants::REFUND_FILE_ACCOUNT_TYPE_1,
                    Constants::REFUND_FILE_ACCOUNT_SUB_TYPE,
                    self::SELF_ACCOUNT_NUMBER,
                    true
                )
            );

            $totalAmount += $row['refund']['amount'] / 100;

            $count++;
        }

        $text = $this->generateText($data, '', $ignoreLastNewline);

        return [$txt, $totalAmount, $count];
    }

    protected function getDataForRow(
        $accountBrCode,
        $mode,
        $amount,
        $accountType,
        $accountSubType,
        $accountNumber,
        $firstLine = false
    )
    {
        $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST
            )
            ->format('Ymd');

        $data = [
            $accountBrCode,
            $date,
            $date,
            $mode,
            self::FIXED_VALUE,
            $amount,
            $accountType,
            $accountSubType,
            $accountNumber,
            $this->getRearString($firstLine)
        ];
    }

    protected function getRearString($firstLine)
    {
        $lastString = self::FIXED_VALUE_REAR;

        if($firstLine === true)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST
            )
            ->format('d.m.Y');

            $lastString = ' Dt: ' . $date;
        }

        return self::FIXED_STRING_REAR . $lastString;
    }
}
