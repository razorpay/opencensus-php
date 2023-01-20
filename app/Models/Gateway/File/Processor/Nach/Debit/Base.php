<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Models\Customer\Token;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Gateway\File\Processor\Nach;

abstract class Base extends Nach\Base
{
    public function fetchEntities(): PublicCollection
    {
        if (Holidays::isWorkingDay(Carbon::now(Timezone::IST)) === false)
        {
            return new PublicCollection();
        }

        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                        ->addHours(9)
                        ->getTimestamp();

        $begin = $this->getLastWorkingDay($begin);

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                      ->addHours(9)
                      ->getTimestamp();

        try
        {
            $tokens = $this->repo->token->fetchPendingNachDebit( static::GATEWAY, $begin, $end);
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

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'count'           => count($paymentIds),
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    public function generateData(PublicCollection $tokens): PublicCollection
    {
        return $tokens;
    }

    public function getAccountTypeValue(Token\Entity $token): string
    {
        $accountTypeMap = [
            Token\Entity::ACCOUNT_TYPE_SAVINGS     => '10',
            Token\Entity::ACCOUNT_TYPE_CURRENT     => '11',
            Token\Entity::ACCOUNT_TYPE_CASH_CREDIT => '13',
            Token\Entity::ACCOUNT_TYPE_SB_NRE      => '10',
            Token\Entity::ACCOUNT_TYPE_SB_NRO      => '10',
        ];

        $accountType = $token->getAccountType() ?? 'savings';

        return $accountTypeMap[$accountType];
    }
}
