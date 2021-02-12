<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;

class CombinedNachCitiEarlyDebit extends PaperNachCiti
{
    const FILE_TYPE         = FileStore\Type::CITI_NACH_EARLY_DEBIT;
    const SUMMARY_FILE_TYPE = FileStore\Type::CITI_NACH_EARLY_DEBIT_SUMMARY;
    const FILE_NAME         = 'citi/nach/ACH-DR-CITI-CITI999999-{$date}-MUT00010{$serial}-INP';

    protected $userName    = 'CTRAZORMFS';
    protected $productType = 'MUT';

    public function __construct()
    {
        parent::__construct();
    }

    public function fetchEntities(): PublicCollection
    {
        if (Holidays::isWorkingDay(Carbon::now(Timezone::IST)) === false)
        {
            return new PublicCollection();
        }

        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                            ->addHours(9)
                            ->getTimestamp();

        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;

        $end = Carbon::createFromTimestamp($begin, Timezone::IST)
                        ->addHours(7)
                        ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        $tokens = $this->repo->token->fetchPendingNachOrMandateDebit(
            [Payment\Gateway::ENACH_NPCI_NETBANKING, Payment\Gateway::NACH_CITI],
            $begin,
            $end,
            Payment\Gateway::ACQUIRER_CITI
        );

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
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'begin'           => $begin,
                'end'             => $end,
                'entity_count'    => count($paymentIds),
            ]);

        return $tokens;
    }

    protected function getFileToWriteNameWithoutExt(array $data)
    {
        $date = $this->getDate();

        $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$serial}' => $data['serialNumber']]);

        if (isset($data['utilityCode']) === true)
        {
            $fileName = strtr($fileName, ['{$utilityCode}' => $data['utilityCode']]);
        }

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getDate()
    {
        return Carbon::now(Timezone::IST)->addDay()->format('dmY');
    }
}
