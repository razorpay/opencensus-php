<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Base\Entity;
use RZP\Gateway\Netbanking\Rbl\Constants;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;
use RZP\Models\Gateway\File\Processor\FileHandler;

class RblCorp extends NetbankingBase
{
    use FileHandler;

    const FILE_NAME              = 'Rbl_Corp_Netbanking_Claims';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::RBL_CORP_NETBANKING_CLAIM;
    const GATEWAY                = Payment\Gateway::NETBANKING_RBL;
    const BASE_STORAGE_DIRECTORY = 'Rbl/Claims/Netbanking/';

    const BANKCODE = Payment\Processor\Netbanking::RATN_C;

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment->fetchReconciledPaymentsForGatewayWithBankCode(
            $begin,
            $end,
            static::GATEWAY,
            static::BANKCODE,
            $statuses
        );

        return $claims;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment'][Payment\Entity::CREATED_AT],
                Timezone::IST)
                ->format('m-d-y h:m:s');

            $paymentAmount = $this->getFormattedAmount($row['payment'][Payment\Entity::AMOUNT]);

            if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                $customerId = $row['gateway'][Netbanking::ADDITIONAL_DATA]['customer_id'];
                $bankRef = $row['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
                $credit_account_number = $row['gateway'][Netbanking::ADDITIONAL_DATA]['credit_account_number'];
                $debit_account_number = $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
            }
            else
            {
                $customerId = $row['gateway'][Entity::CUSTOMER_ID];
                $bankRef = $row['gateway'][Entity::BANK_PAYMENT_ID];
                $credit_account_number = $row['gateway'][Entity::CREDIT_ACCOUNT_NUMBER];
                $debit_account_number = $row['gateway'][Entity::ACCOUNT_NUMBER];
            }

            $formattedData[] = [
                ClaimFields::SERIAL_NO          => $index++,
                ClaimFields::TRANSACTION_DATE   => $date,
                ClaimFields::USER_ID            => $customerId,
                ClaimFields::DEBIT_ACCOUNT      => $debit_account_number,
                ClaimFields::CREDIT_ACCOUNT     => $credit_account_number,
                ClaimFields::TRANSACTION_AMOUNT => $paymentAmount,
                ClaimFields::PGI_REFERENCE      => $bankRef,
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
        if ($row['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            $status = $row['gateway'][Netbanking::GATEWAY_STATUS];

            if ($status === 'SUC')
            {
                return 'Success';
            }
            return 'Failed';
        }
        else
        {
            if ($row['gateway'][Entity::STATUS] === 'SUC')
            {
                return 'Success';
            }
            return 'Failed';
        }
    }

    protected function getErrorMessage(array $row)
    {
        if (empty($row['gateway'][Entity::ERROR_MESSAGE]) === true)
        {
            return 'NA';
        }

        return $row['gateway'][Entity::ERROR_MESSAGE];
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}
