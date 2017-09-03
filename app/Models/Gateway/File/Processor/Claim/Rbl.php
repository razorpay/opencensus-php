<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;
use RZP\Gateway\Netbanking\Base\Entity;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Rbl extends Processor\Base
{
    use GenerateClaimFile;
    use FileHandlerTrait;

    const FILE_NAME = 'Rbl_Netbanking_Claims';
    const EXTENSION = FileStore\Format::TXT;
    const FILE_TYPE = FileStore\Type::RBL_NETBANKING_CLAIM;
    const GATEWAY   = Payment\Gateway::NETBANKING_RBL;

    const SECONDS_PER_DAY = 86400;

    protected function fetchReconciledPayments(array $statuses)
    {
        // Payments made yesterday are reconciled today,
        // so forwarding time stamps by 1 day
        list($from, $to) = $this->updateTimeStamps();

        $claims = $this->repo->payment
                             ->fetchReconciledPaymentsForGateway($from,
                                                                $to,
                                                                static::GATEWAY,
                                                                $statuses);

        return $claims;
    }

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row['payment'][Payment\Entity::CREATED_AT],
                        Timezone::IST)
                        ->format('m-d-y h:m:s');

            $paymentAmount = $this->getFormattedAmount($row['payment'][Payment\Entity::AMOUNT]);

            $formattedData[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => $row['gateway'][Entity::CUSTOMER_ID],
                ClaimFields::DEBIT_ACCOUNT      => $row['gateway'][Entity::ACCOUNT_NUMBER],
                ClaimFields::CREDIT_ACCOUNT     => $row['gateway'][Entity::CREDIT_ACCOUNT_NUMBER],
                ClaimFields::TRANSACTION_AMOUNT => $paymentAmount,
                ClaimFields::PGI_REFERENCE      => $row['gateway'][Entity::BANK_PAYMENT_ID],
                ClaimFields::BANK_REFERENCE     => $row['payment'][Payment\Entity::ID],
                ClaimFields::MERCHANT_NAME      => Constants::MERCHANT_NAME,
                ClaimFields::PGI_STATUS         => $this->getGatewayStatus($row),
                ClaimFields::ERROR_DESCRIPTION  => $this->getErrorMessage($row),
                ClaimFields::TRANSACTION_STATUS => $this->getPaymentStatus($row),
            ];
        }

        $formattedData = $this->generateText($formattedData, ',');

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getPaymentStatus(array $row)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::REFUNDED,
            Payment\Status::CAPTURED
        ];

        if (in_array($row['payment'][Payment\Entity::STATUS], $status, true) === true)
        {
            return 'Success';
        }

        return 'Failed';
    }

    protected function getGatewayStatus(array $row)
    {
        if ($row['gateway'][Entity::STATUS] === 'SUC')
        {
            return 'Success';
        }

        return 'Failed';
    }

    protected function getErrorMessage(array $row)
    {
        if (empty($row['gateway'][Entity::ERROR_MESSAGE]) === true)
        {
            return 'NA';
        }

        return $row['gateway'][Entity::ERROR_MESSAGE];
    }

    protected function updateTimeStamps()
    {
        $tsDifference = static::SECONDS_PER_DAY;

        return [$this->gatewayFile->getFrom() + $tsDifference, $this->gatewayFile->getTo() + $tsDifference];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
