<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;

class PaperNachCitiV2 extends PaperNachCiti
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws GatewayFileException
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->getTimestamp();

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
            $tokenBegin = $begin;

            if ($token->merchant->isEarlyMandatePresentmentEnabled() === true)
            {
                while ($tokenBegin < $end)
                {
                    $nineAM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(9);
                    $threePM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(15);
                    $createdAt = $token['payment_created_at'];
                    /*
                     * the payments done from previous day 9am to 3pm should not be considered here as
                     * these payments will be part of mutual fund exclusive timing cycle (9am to 3pm)
                     */
                    if (($createdAt >= $nineAM->timestamp) and ($createdAt < $threePM->timestamp))
                    {
                        unset($tokens[$key]);
                    }
                    $tokenBegin = $tokenBegin + Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;
                }
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

    protected function getHeaderDate(): string
    {
        $offset = (int) $this->gatewayFile->getSubType();

        return Carbon::now(Timezone::IST)->addDays($offset)->format('dmY');
    }

}
