<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Debit;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base as ModelBase;
use RZP\Models\Base\PublicCollection;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Gateway\Enach\Npci\Netbanking\DebitFileHeading as Headings;

use Carbon\Carbon;

class EnachNpciNetbanking extends Base
{
    const ACQUIRER  = Payment\Gateway::ACQUIRER_YESB;

    const GATEWAY   = Payment\Gateway::ENACH_NPCI_NETBANKING;

    const EXTENSION = FileStore\Format::CSV;

    const FILE_TYPE = FileStore\Type::ENACH_NPCI_NB_DEBIT;

    const FILE_NAME = 'NACH_DR_{$date}_{$utilityCode}_RAZORPAY_001';

    protected $utilityCode;

    const STEP = 'debit';

    const FILE_METADATA  = [
        'gid'   => '10000',
        'uid'   => '10004',
        'mode'  => '33188'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->enach;
    }

    protected function formatDataForFile($tokens)
    {
        $this->setUtilitycodeAttribute($tokens);

        $rows = [];

        foreach ($tokens as $token)
        {
            if ($this->isSponsorYesBank($token->terminal) === true)
            {
                $paymentId = $token['payment_id'];

                $debitDate = Carbon::createFromTimestamp($token['payment_created_at'], Timezone::IST)->format('dmY');

                $row = [
                    Headings::PAYMENT_ID              => $paymentId,
                    Headings::UMRN                    => $token->getGatewayToken(),
                    Headings::AMOUNT                  => $this->getFormattedAmount($token['payment_amount']),
                    Headings::SETTLEMENT_DATE         => $debitDate,
                    Headings::UTILITY_CODE            => $token->terminal->getGatewayMerchantId(),
                ];

                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date, '{$utilityCode}' => $this->utilityCode]);

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

    protected function isSponsorYesBank($terminal)
    {
        $sponsorBank = strtolower($terminal->getGatewayAcquirer());

        return $sponsorBank === Payment\Gateway::ACQUIRER_YESB;
    }

    protected function setUtilityCodeAttribute($tokens)
    {
        $this->utilityCode = $tokens[0]->terminal->getGatewayMerchantId();
    }

    public function sendFile($data)
    {
        $file = $this->gatewayFile
                ->files()
                ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                ->first();

        $fullFileName = $file->getName() . '.' . $file->getExtension();

        $fileInfo = [$fullFileName];

        $data =  [
            BeamService::BEAM_PUSH_FILES   => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::YESBANK_ENACH_NB_JOB_NAME
        ];

        // In seconds
        //TODO
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'emandate',
            'filetype'  => FileStore\Type::ENACH_NPCI_NB_DEBIT,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::EMANDATE]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }
}
