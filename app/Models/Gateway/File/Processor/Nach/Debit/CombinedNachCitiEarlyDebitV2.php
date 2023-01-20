<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;

class CombinedNachCitiEarlyDebitV2 extends PaperNachCitiV2
{
    const FILE_TYPE         = FileStore\Type::CITI_NACH_EARLY_DEBIT;
    const SUMMARY_FILE_TYPE = FileStore\Type::CITI_NACH_EARLY_DEBIT_SUMMARY;
    const FILE_NAME         = 'citi/nach/ACH-DR-CITI-CITI137272-{$date}-RZPMUT{$serial}-INP';
    const SUMMARY_FILE_NAME = 'citi/nach/ACH-DR-CITI-CITI137272-{$date}-RZPMUT{$serial}-INP-SUMMARY';
    protected $userName     = 'CTRAZORMFS';
    protected $productType  = 'MUT';

    public function __construct()
    {
        parent::__construct();
    }

    public function fetchEntities(): PublicCollection
    {
        /*
         * We add one day to begin and end because in cron request for the gateway file generation
         * the date would be for previous day.
         * but in this case we need to send the payments of same day
         */
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
            ->addDay()
            ->addHours(9)
            ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
            ->addDay()
            ->addHours(15)
            ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingNachOrMandateDebit(
                [Payment\Gateway::ENACH_NPCI_NETBANKING, Payment\Gateway::NACH_CITI],
                $begin,
                $end,
                Payment\Gateway::ACQUIRER_CITI);
        }
        catch (ServerErrorException $e)
        {
            $this->generateMetric(Metric::EMANDATE_DB_ERROR);

            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        foreach ($tokens as $key => $token)
        {
            if ($token->merchant->isEarlyMandatePresentmentEnabled() === false)
            {
                unset($tokens[$key]);
            }
        }

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_EARLY_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'begin'           => $begin,
                'end'             => $end,
                'entity_count'    => count($paymentIds),
            ]);

        return $tokens;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = $this->getDate();

        $formatSerialNumber = $this->formatSerialNumber($data['serialNumber']);

        $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$serial}' => $formatSerialNumber]);

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getDate(): string
    {
        return Carbon::now(Timezone::IST)->addDay()->format('dmY');
    }

    protected function getHeaderDate(): string
    {
        return Carbon::now(Timezone::IST)->addDay()->format('dmY');
    }
}
