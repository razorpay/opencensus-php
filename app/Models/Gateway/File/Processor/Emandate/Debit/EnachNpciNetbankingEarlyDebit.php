<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Enach\Npci\Netbanking\DebitFileHeading as Headings;

class EnachNpciNetbankingEarlyDebit extends EnachNpciNetbanking
{
    const FILE_NAME = 'yesbank/nach/input_file/NACH_DR_{$date}_{$utilityCode}_RAZORPAY_MUT001';

    /**
     * @throws GatewayFileException
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                         ->addHours(14)
                         ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                       ->addHours(14)
                       ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingEMandateDebitWithGatewayAcquirer(
                self::GATEWAY,
                $begin,
                $end,
                Payment\Gateway::ACQUIRER_YESB);
        }
        catch (ServerErrorException $e)
        {
            $this->generateMetric(Metric::EMANDATE_DB_ERROR);

            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
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
            TraceCode::EMANDATE_EARLY_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
                'count'           => count($paymentIds),
            ]);

        return $tokens;
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $terminal  = $token->terminal;
            $paymentId = $token['payment_id'];
            $debitDate = Carbon::tomorrow(Timezone::IST)->format('dmY');

            $rows[$terminal->getGatewayMerchantId2()][] = [
                Headings::PAYMENT_ID      => $paymentId,
                Headings::UMRN            => $token->getGatewayToken(),
                Headings::AMOUNT          => $this->getFormattedAmount($token['payment_amount']),
                Headings::SETTLEMENT_DATE => $debitDate,
                Headings::UTILITY_CODE    => $token->terminal->getGatewayMerchantId2(),
            ];
        }

        return $rows;
    }
}
