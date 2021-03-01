<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

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

    protected function getFileToWriteNameWithoutExt(array $data)
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
}
