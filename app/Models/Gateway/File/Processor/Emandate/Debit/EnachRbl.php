<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
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

    protected function getEnachGatewayAttributes($token): array
    {
        return [
            Enach\Base\Entity::ACQUIRER => self::ACQUIRER,
            Enach\Base\Entity::UMRN     => $token['gateway_token'],
        ];
    }

    public function checkIfValidDataAvailable(PublicCollection $tokens)
    {
        if ($tokens->count() === 0)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

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

    public function generateData(PublicCollection $tokens)
    {
        try
        {
            $data = $tokens;

            // Create gateway entities
            $this->createGatewayEntities($tokens);

            return $data;
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function createGatewayEntities(PublicCollection $tokens)
    {
        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $gatewayPayment = $this->gatewayRepo->findByPaymentIdAndAction(
                $paymentId, GatewayAction::AUTHORIZE);

            //
            // If gatewayPayment already exists then skip its creation.
            // This case will arise when we retry sending some payments to the bank
            //
            if ($gatewayPayment !== null)
            {
                continue;
            }

            $this->createEnachGatewayEntity($token);
        }
    }

    protected function createEnachGatewayEntity($token)
    {
        $paymentId = $token['payment_id'];

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction(GatewayAction::AUTHORIZE);

        $gatewayPayment->setBank($token['bank']);

        $gatewayPayment->setAmount($token['payment_amount']);

        $attributes = $this->getEnachGatewayAttributes($token);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }
}
