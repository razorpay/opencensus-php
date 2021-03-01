<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Models\Base\PublicCollection;
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

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->netbanking;
    }

    protected function formatDataForFile($tokens)
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $debitDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('d/m/Y');

            $row = [
                Headings::PAYMENT_ID                  => $paymentId,
                Headings::DEBIT_DATE                  => $debitDate,
                Headings::GATEWAY_MERCHANT_ID         => $token->terminal->getGatewayMerchantId(),
                Headings::CUSTOMER_UID                => $token->getGatewayToken(),
                Headings::CUSTOMER_NAME               => $token->customer->getName(),
                // If the account number starts with 0 and the file is
                // opened with MS-Excel, it trims the 0 since it treats
                // the account number as an integer rather than a string.
                // Adding a `'` in the start ensures that MS-Excel
                // treats it as a string and not an integer.
                Headings::DEBIT_ACCOUNT               => '\'' . $token->getAccountNumber(),
                Headings::AMOUNT                      => $this->getFormattedAmount($token['payment_amount']),
                Headings::ADDITIONAL_INFO_1           => '',
                Headings::ADDITIONAL_INFO_2           => '',
                Headings::UNDERLYING_REFERENCE_NUMBER => '',
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getFormattedAmount($amount)
    {
        return $amount / 100;
    }
}
