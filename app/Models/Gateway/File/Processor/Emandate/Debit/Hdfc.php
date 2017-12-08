<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;
use RZP\Gateway\Utility;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\Payment;

class Hdfc extends Base
{
    const ADHOC         = 'As & when Presented';
    const MAX_END_DATE  = '31/12/2099';

    const STEP          = 'debit';
    const GATEWAY       = Payment\Gateway::NETBANKING_HDFC;
    const FILE_NAME     = 'HDFC_EMandate_Debit';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::HDFC_EMANDATE_DEBIT;

    protected function getClientCode(Payment\Entity $payment): string
    {
        $email = $payment->getEmail() ?: Payment\Entity::DUMMY_EMAIL;

        $clientCode = Utility::stripEmailSpecialChars($email);

        return $clientCode;
    }

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $startDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [

                Headings::TRANSACTION_REF_NO  => $paymentId,
                Headings::MANDATE_ID          => $token->getId(),
                Headings::ACCOUNT_NO          => $token->getAccountNumber(),
                Headings::AMOUNT              => $this->getFormattedAmount($payment->getAmount()),
                Headings::SIP_DATE            => $startDate,
                Headings::FREQUENCY           => self::ADHOC,
                Headings::FROM_DATE           => $startDate,
                Headings::TO_DATE             => self::MAX_END_DATE,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getGatewayAttributes(Payment\Entity $payment): array
    {
        return [
            Netbanking\Base\Entity::CLIENT_CODE => $this->getClientCode($payment),
        ];
    }
}
