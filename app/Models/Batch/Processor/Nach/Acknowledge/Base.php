<?php

namespace RZP\Models\Batch\Processor\Nach\Acknowledge;

use Carbon\Carbon;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Batch\Processor\Nach\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const GATEWAY_TOKEN = 'gateway_token';
    const TOKEN_STATUS  = 'token_status';
    const PAYMENT_ID    = 'payment_id';

    protected function processEntry(array & $entry)
    {
        try
        {
            $parsedData = $this->getDataFromRow($entry);

            $this->validateParsedData($parsedData);

            $payment = $this->fetchPaymentEntity($parsedData);

            $token = $payment->getGlobalOrLocalTokenEntity();

            $this->repo->transaction(function () use ($payment, $token, $parsedData) {
                $this->updateTokenEntity($token, $parsedData);
            });

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_ACKNOWLEDGE_RESPONSE_ERROR
            );

            throw $ex;
        }
    }

    protected function fetchPaymentEntity($data): Payment\Entity
    {
        return $this->repo->payment->findOrFailPublic($data[self::PAYMENT_ID]);
    }

    protected function updateTokenEntity(Token\Entity $token, array $content)
    {
        $gatewayToken = $content[self::GATEWAY_TOKEN];

        $currentRecurringStatus = $token->getRecurringStatus();

        $newRecurringStatus = $content[self::TOKEN_STATUS];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            $this->trace->info(TraceCode::TOKEN_ALREADY_IN_FINAL_STATUS,
                [
                    'token_id' => $token->getId(),
                ]);

            return;
        }

        if (empty($token->getGatewayToken()) === false)
        {
            if ($token->getGatewayToken() === $gatewayToken)
            {
                $this->trace->info(TraceCode::GATEWAY_TOKEN_ALREADY_PRESENT,
                    [
                        'token_id'      => $token->getId(),
                        'new_token'     => $gatewayToken,
                        'current_token' => $token->getGatewayToken(),
                    ]);
            }
            else
            {
                $this->trace->critical(TraceCode::GATEWAY_TOKEN_MISMATCH,
                    [
                        'token_id'      => $token->getId(),
                        'new_token'     => $gatewayToken,
                        'current_token' => $token->getGatewayToken(),
                    ]);
            }

            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS => $newRecurringStatus,
            Token\Entity::ACKNOWLEDGED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
            Token\Entity::GATEWAY_TOKEN    => $gatewayToken,
        ];

        (new Token\Core)->updateTokenFromNachGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }
}
