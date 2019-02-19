<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base as ModelBase;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

use Carbon\Carbon;

class EnachRbl extends Base
{
    const ACQUIRER  = Payment\Gateway::ACQUIRER_RATN;

    const GATEWAY   = Payment\Gateway::ENACH_RBL;

    const EXTENSION = FileStore\Format::XLSX;

    const FILE_TYPE = FileStore\Type::RBL_ENACH_DEBIT;

    const FILE_NAME = 'rbl-enach/outgoing/TXN_INP/ACH-DR-RATN-RATNA0001-{$date}-000001-INP';

    const STEP      = 'debit';

    const FILE_METADATA  = [
        'gid'   => '10000',
        'uid'   => '10006',
        'mode'  => '33188'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;
    }

    protected function formatDataForFile($tokens)
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $debitDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('d/m/Y');

            $row = [
                Headings::UTILITYCODE             => $token->terminal->getGatewayMerchantId(),
                Headings::TRANSACTIONTYPE         => 'ACH DR',
                Headings::SETTLEMENTDATE          => $debitDate,
                Headings::BENEFICIARYACHOLDERNAME => $token->getBeneficiaryName(),
                Headings::AMOUNT                  => $this->getFormattedAmount($token['payment_amount']),
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

    protected function getNewGatewayPaymentEntity()
    {
        return new Enach\Base\Entity;
    }

    protected function getGatewayAttributes(ModelBase\PublicEntity $token): array
    {
        return [
            Enach\Base\Entity::ACQUIRER => self::ACQUIRER,
            Enach\Base\Entity::UMRN     => $token['gateway_token'],
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
