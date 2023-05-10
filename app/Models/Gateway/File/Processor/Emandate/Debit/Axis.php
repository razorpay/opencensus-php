<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Base\RuntimeManager;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Axis\EMandateDebitFileHeadings as Headings;

class Axis extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_AXIS;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::AXIS_EMANDATE_DEBIT;

    const FILE_NAME = 'Axis_EMandate_Debit';

    const BASE_STORAGE_DIRECTORY = 'Axis/Emandate/Netbanking/';

    const STEP      = 'debit';

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->netbanking;
    }

    protected function formatDataForFile($tokens): array
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
                Headings::CUSTOMER_NAME               => $token->customer === null ? "" : $token->customer->getName(),
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

    protected function getNewGatewayPaymentEntity(): Netbanking\Base\Entity
    {
        return new Netbanking\Base\Entity;
    }

    protected function getFormattedAmount($amount): string
    {
        return $amount / 100;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $time = Carbon::now(Timezone::IST)->format('dmYHis');

        if ($this->isTestMode() === true)
        {
            return static::FILE_NAME . '_' . $time . '_' . $this->mode;
        }

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $time;
    }
    
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('4096M');
    }
}
