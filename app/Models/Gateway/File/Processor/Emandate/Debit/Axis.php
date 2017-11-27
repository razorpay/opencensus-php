<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Axis\EMandateDebitFileHeadings as Headings;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Models\Terminal\Entity as TerminalEntity;

use Carbon\Carbon;

class Axis extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AXIS;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::AXIS_EMANDATE_DEBIT;

    const FILE_NAME = 'Axis_EMandate_Debit';

    const STEP      = 'debit';

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $debitDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [
                Headings::PAYMENT_ID                  => $paymentId,
                Headings::DEBIT_DATE                  => $debitDate,
                Headings::GATEWAY_MERCHANT_ID         => $payment->terminal->getGatewayMerchantId(),
                Headings::TOKEN_ID                    => $token->getId(),
                Headings::CUSTOMER_NAME               => $token->customer->getName(),
                Headings::DEBIT_ACCOUNT               => $token->getAccountNumber(),
                Headings::AMOUNT                      => $this->getFormattedAmount($payment->getAmount()),
                Headings::ADDITIONAL_INFO_1           => '',
                Headings::ADDITIONAL_INFO_2           => '',
                Headings::UNDERLYING_REFERENCE_NUMBER => '',
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getFormattedAmount($amount)
    {
        return $amount / 100;
    }
}
