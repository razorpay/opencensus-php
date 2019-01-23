<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Utility;
use RZP\Models\FileStore;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Base as ModelBase;
use RZP\Models\Base\PublicCollection;
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

    protected function formatDataForFile($tokens)
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

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getGatewayAttributes(ModelBase\PublicEntity $token): array
    {
        return [
            Netbanking\Base\Entity::CLIENT_CODE => $this->getClientCode($token),
        ];
    }

    /**
     * For debit payments, we will be following a 9am to 9am cycle.
     * If a request comes from the cron, begin and end is set as per 12 am to 12 am cycle.
     * Adding 9 hours here to make the adjustment. If the request is generated manually then this will still
     * apply as we cannot differentiate between sync and async here. So if we try to generate the file manually
     * and put the begin and end as 9am to 9am then it will be changed to 6pm to 6pm
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                         ->addHours(9)
                         ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                       ->addHours(9)
                       ->getTimestamp();

        $tokens = $this->repo->token->fetchPendingEMandateDebit(static::GATEWAY, $begin, $end);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }
}
