<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use Rzp\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Axis\EMandateDebitFileHeadings as Headings;

use Carbon\Carbon;

class Axis extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AXIS;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::AXIS_EMANDATE_DEBIT;

    const FILE_NAME = 'Axis_EMandate_Debit';

    protected function formatDataForFile()
    {
        $mode = $this->app['basicauth']->getMode();

        $gateway = $this->app['gateway']->gateway(self::GATEWAY);

        $gateway->setMode($mode);

        $merchantId = $gateway->getEmandateMerchantId();

        $payments = $this->data;

        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $debitDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [
                Headings::PAYMENT_ID => $paymentId,
                Headings::DEBIT_DATE => $debitDate,
                Headings::MERCHANT_ID => $merchantId,
                Headings::TOKEN_ID => $token['id'],
                Headings::CUSTOMER_NAME => $token->customer['name'],
                Headings::DEBIT_ACCOUNT => $token->getAccountNumber(),
                Headings::AMOUNT => $payment->getAmount(),
                Headings::ADDITIONAL_INFO_1 => '',
                Headings::ADDITIONAL_INFO_2 => '',
                Headings::UNDERLYING_REFERENCE_NUMBER => '',
            ];

            $rows[] = $row;
        }

        return $rows;
    }
}
