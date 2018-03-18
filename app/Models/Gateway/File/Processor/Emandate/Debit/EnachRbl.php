<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Gateway\Enach\Rbl\TransactionType;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

use Carbon\Carbon;

class EnachRbl extends Base
{
    const GATEWAY = Payment\Gateway::ENACH_RBL;

    const EXTENSION = FileStore\Format::XLSX;

    const FILE_TYPE = FileStore\Type::RBL_ENACH_DEBIT;

    const FILE_NAME = 'rbl-enach/outgoing/ACH-DR-RNATA-RATNA0001-{$date}-000001-INP';

    const STEP      = 'debit';

    const METADATA  = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();

            $debitDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $row = [
                Headings::UTILITYCODE             => $payment->terminal->getGatewayMerchantId(),
                Headings::TRANSACTIONTYPE         => TransactionType::DEBIT,
                Headings::SETTLEMENTDATE          => $debitDate,
                Headings::BENEFICIARYACHOLDERNAME => $token->getBeneficiaryName(),
                Headings::AMOUNT                  => $this->getFormattedAmount($payment->getAmount()),
                Headings::DESTINATIONBANKCODE     => $token->getIfsc(),
                Headings::BENEFICIARYACNO         => $token->getAccountNumber(),
                Headings::TRANSACTIONREFERENCE    => $paymentId,
                Headings::UMRN                    => $token->getGatewayToken(),
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date]);

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function createGatewayEntities(PublicCollection $payments)
    {
        // @todo: Add gateway entity creation
    }
}
