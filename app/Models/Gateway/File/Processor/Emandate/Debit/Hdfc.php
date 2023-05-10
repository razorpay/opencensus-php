<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Gateway\Utility;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Base\RuntimeManager;
use RZP\Models\Base as ModelBase;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class Hdfc extends Base
{
    const ADHOC         = 'As & when Presented';
    const MAX_END_DATE  = '31/12/2099';
    const STEP          = 'debit';
    const GATEWAY       = Payment\Gateway::NETBANKING_HDFC;
    const FILE_NAME     = 'HDFC_EMandate_Debit';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::HDFC_EMANDATE_DEBIT;

    const BASE_STORAGE_DIRECTORY = 'Hdfc/Emandate/Debit/';

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->netbanking;
    }

    protected function getClientCode(ModelBase\PublicEntity $token): string
    {
        $email = $token['payment_email'] ?: Payment\Entity::DUMMY_EMAIL;

        $clientCode = Utility::stripEmailSpecialChars($email);

        return $clientCode;
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $startDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('d/m/Y');

            $row = [
                Headings::TRANSACTION_REF_NO  => $paymentId,
                Headings::SUB_MERCHANT_NAME   => $token->merchant->getFilteredDba(),
                Headings::MANDATE_ID          => $token->getId(),
                Headings::ACCOUNT_NO          => $token->getAccountNumber(),
                Headings::AMOUNT              => $this->getFormattedAmount($token['payment_amount']),
                Headings::SIP_DATE            => $startDate,
                Headings::FREQUENCY           => self::ADHOC,
                Headings::FROM_DATE           => $startDate,
                Headings::TO_DATE             => self::MAX_END_DATE,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getNewGatewayPaymentEntity(): Netbanking\Base\Entity
    {
        return new Netbanking\Base\Entity;
    }

    protected function getGatewayAttributes(ModelBase\PublicEntity $token): array
    {
        return [
            Netbanking\Base\Entity::CLIENT_CODE => $this->getClientCode($token),
        ];
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $fileName = parent::getFileToWriteNameWithoutExt($data);

        return static::BASE_STORAGE_DIRECTORY . $fileName;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('8192M'); // 8 GB

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }
}
