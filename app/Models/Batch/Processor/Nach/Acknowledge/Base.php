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
use RZP\Models\Batch\Processor\Nach\Register;

class Base extends BaseProcessor
{
    const GATEWAY_TOKEN = 'gateway_token';
    const TOKEN_STATUS  = 'token_status';
    const PAYMENT_ID    = 'payment_id';

    const GATEWAY_ERROR_CODE      = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE   = 'gateway_error_message';
    const INTERNAL_ERROR_CODE     = 'internal_error_code';

    // Stored in token entity
    const TOKEN_ERROR_CODE = 'token_error_code';

    /**
     * @var Payment\Processor\Processor
     */
    protected $paymentProcessor;

    protected function processEntry(array & $entry)
    {
        try
        {
            $parsedData = $this->getDataFromRow($entry);

            $this->validateParsedData($parsedData);

            $payment = $this->fetchPaymentEntity($parsedData);

            $token = $payment->getGlobalOrLocalTokenEntity();

            $oldRecurringStatus = $token->getRecurringStatus();

            $this->repo->transaction(function () use ($payment, $token, $parsedData) {
                $this->updateTokenEntity($token, $parsedData);
            });

            // If payment is received as initial reject in ack file itself, update payment status & send webhooks
            if($token->getRecurringStatus() === Token\RecurringStatus::REJECTED){

                $this->trace->info(TraceCode::INITIAL_REJECT_PAYMENT_IN_ACK_FILE,
                [
                    'payment received rejected in ack file'      => $payment,
                ]);

                // 1. update subscriptions registration & payment entity
                (new Register\NachIcici)->updateTokenRegistration($payment);
                (new Register\NachIcici)->processFailedPayment($payment, $parsedData);

                // 2. send token reject webhook
                $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));
                $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);
            }

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
            Token\Entity::RECURRING_FAILURE_REASON  => $content[self::TOKEN_ERROR_CODE],
        ];

        (new Token\Core)->updateTokenFromNachGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }
}
