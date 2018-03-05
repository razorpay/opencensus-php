<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

use Carbon\Carbon;

class EnachRbl extends Base
{
    const GATEWAY = Payment\Gateway::ENACH_RBL;

    const EXTENSION = FileStore\Format::XLSX;

    const FILE_TYPE = FileStore\Type::RBL_ENACH_DEBIT_FILE_SFTP;

    const FILE_NAME = 'ACH-DEBIT-RATNMaker-{$date}-ESIGN000001-INP';

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
                Headings::UTILITY_CODE            => $payment->terminal->getGatewayMerchantId(),
                Headings::TRANSACTION_TYPE        => 'ACH DR',
                Headings::SETTLEMENT_DATE         => $debitDate,
                Headings::BENEFICIARYACHOLDERNAME => $token->getBeneficiaryName(),
                Headings::AMOUNT                  => $this->getFormattedAmount($payment->getAmount()),
                Headings::DESTINATIONBANKCODE     => $token->getIfsc(),
                Headings::BENEFICIARYACNO         => $token->getAccountNumber(),
                Headings::TRANSACTIONREFERENCE    => $paymentId,
                Headings::URMN                    => $token->getGatewayToken(),
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

    protected function getFormattedAmount($amount)
    {
        return $amount / 100;
    }

    protected function createGatewayEntities(PublicCollection $payments)
    {
        // Dummy function
    }
}
