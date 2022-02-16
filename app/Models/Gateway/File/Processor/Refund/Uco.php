<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Uco extends Base
{
    use FileHandler;

    const BANK_ACCOUNT_NUMBER   = 'Account no.';
    const BANK_ID               = 'BankId';
    const BANK_MERCHANT_CODE    = 'BankMerchantCode';
    const REFUND_AMOUNT         = 'refund amount';
    const REMARK                = 'ref/bank ref no/txn date';
    const REFUND_DATE           = 'RefundDate';
    const BANK_REFERENCE_NUMBER = 'BankRefNo.';
    const TXN_DATE              = 'TransactionDate';


    const BANK_PAY_TYPE         = 'BankPayType';

    const FILE_NAME              = 'Razorpay_Refund_';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::UCO_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_UCO;
    const GATEWAY_CODE           = [IFSC::UCBA];
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;


    protected function formatDataForFile(array $data)
    {

        $content = [];

        $amount = 0;

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('d-m-Y');

            $refundDate = Carbon::createFromTimestamp($row['refund']['created_at'], Timezone::IST)->format('d/m/Y');

            $bankRefNo = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service

            $bankAccountNo = $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];

            $bank4digit = substr($bankAccountNo, 0, 4);

            $content[] = [
                self::BANK_ACCOUNT_NUMBER => $bankAccountNo,
                self::BANK_ID             => 'INR' . $bank4digit,
                self::BANK_MERCHANT_CODE  => 'C',
                self::REFUND_AMOUNT       => $this->getFormattedAmount($row['refund']['amount']),
                self::REMARK              => 'ref/' . $bankRefNo . '/' . $transactionDate,
                self::REFUND_DATE         => $refundDate,
            ];

            $amount = $amount + $row['refund']['amount'];
        }

        $transactionAmount = $this->getFormattedAmount($amount);

        $refundDate = Carbon::now(Timezone::IST)->format('d-m-Y');

        $refundDate1 = Carbon::now(Timezone::IST)->format('d/m/Y');

        $poolAcNumber = '18700210002254';

        $bank4digit = substr($poolAcNumber, 0, 4);

        $content[] = [
            self::BANK_ACCOUNT_NUMBER => $poolAcNumber,
            self::BANK_ID             => 'INR' . $bank4digit,
            self::BANK_MERCHANT_CODE  => 'D',
            self::REFUND_AMOUNT       => $transactionAmount,
            self::REMARK              => 'refund/' . $refundDate,
            self::REFUND_DATE         => $refundDate1,
        ];

        $content = $this->getTextData($content, '', ' ');

        return $content;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME . $dateTime;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getTextData($data, $prependLine = '', string $glue = '|'): string
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, $glue, $ignoreLastNewline);

        return $prependLine . $txt;
    }

    protected function generateText($data, $glue = '|', $ignoreLastNewline = false): string
    {
        $txt = '';

        $count = count($data);

        $refund_space_spec = [16, 11, 1, 17, 133, 0];

        foreach ($data as $row)
        {
            $testArray = array_values($row);
            for ($i = 0; $i < count($testArray); $i++)
            {
                $j = $refund_space_spec[$i];
                if ($j == 17)
                {
                    $txt = $txt . str_pad($testArray[$i], $j, ' ', STR_PAD_LEFT);
                }
                else
                {
                    $txt = $txt . str_pad($testArray[$i], $j, ' ', STR_PAD_RIGHT);
                }

            }


            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }
}
