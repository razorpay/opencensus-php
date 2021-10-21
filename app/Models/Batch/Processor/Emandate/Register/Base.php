<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Error\PublicErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Base\Entity as GatewayEntity;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

abstract class Base extends BaseProcessor
{
    /**
     * Params expected in the getDataFromRow method's response
     */
    const GATEWAY_TOKEN               = 'gateway_token';
    const TOKEN_STATUS                = 'token_status';
    const PAYMENT_ID                  = 'payment_id';
    // Stored in gateway entity
    const GATEWAY_REGISTRATION_STATUS = 'gateway_registration_status';
    const GATEWAY_ERROR_CODE          = 'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION   = 'gateway_error_description';
    // Stored in token entity
    const TOKEN_ERROR_CODE            = 'token_error_code';
    const ACCOUNT_NUMBER              = 'account_number';

    /**
     * @var Payment\Processor\Processor
     */
    protected $paymentProcessor;

    /**
     * @var array Used for mapping the file content to the corresponding gateway entity
     */
    protected $gatewayPaymentMapping = [];

    protected function processEntry(array & $entry)
    {
        //
        // Expects $parsedData to have keys
        // 'token_id'         : Corresponds to Token\Entity::ID
        // 'status'           : Corresponds to Token\Entity::RECURRING_STATUS
        // 'remark'           : Corresponds to Token\Entity::RECURRING_FAILURE_REASON
        // 'gateway_token'    : Corresponds to Token\Entity::GATEWAY_TOKEN
        //

        try
        {
            $parsedData = $this->getDataFromRow($entry);

            $payment = $this->fetchPaymentEntity($parsedData);

            if ($this->shouldUpdateBatchOutputWithPaymentId() === true)
            {
                $entry[Batch\Header::PAYMENT_ID] = $payment->getId();
            }

            list($payment, $authorizeSuccess) = $this->forceAuthorizeIfApplicable($payment, $parsedData);

            if ($authorizeSuccess === false)
            {
                $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                    [
                        'trace_code' => TraceCode::EMANDATE_RECON_ROW_FAILED,
                        'message'  => 'payment force authorize failed',
                        'payment_id' => $payment->getId(),
                    ]);

                $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE]        = PublicErrorCode::SERVER_ERROR;
                $entry[Batch\Header::ERROR_DESCRIPTION] = PublicErrorDescription::SERVER_ERROR;

                return;
            }

            $gatewayPayment = $this->getGatewayPayment($payment);

            $token = $payment->getGlobalOrLocalTokenEntity();

            $oldRecurringStatus = $token->getRecurringStatus();

            $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

            $this->repo->transaction(function() use ($payment, $token, $gatewayPayment, $parsedData)
            {
                $this->updateGatewayPaymentEntityAndCapturePayment($payment, $gatewayPayment, $parsedData);

                $this->updateTokenEntity($token, $parsedData, $payment);
            });

            $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EMANDATE_REGISTER_RESPONSE_ERROR,
                [
                    'gateway' => static::GATEWAY
                ]
            );

            throw $ex;
        }
    }

    abstract protected function getDataFromRow(array $entry): array;
    abstract protected function getTokenStatus(string $gatewayTokenStatus, array $content): string;
    abstract protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry);
    abstract protected function getGatewayPayment(Payment\Entity $payment);

    protected function updateGatewayPaymentEntityAndCapturePayment(
        Payment\Entity $payment,
        GatewayEntity $gatewayPayment,
        array $data
    )
    {
        $content = $this->getMappedAttributes($data);

        $gatewayPayment->fill($content);

        $this->repo->saveOrFail($gatewayPayment);

        //
        // We do capture ONLY if registration is successful AND it's not already captured.
        //
        if (($data[self::TOKEN_STATUS] === Token\RecurringStatus::CONFIRMED) and
            ($payment->hasBeenCaptured() === false))
        {
            $this->captureAuthorizedPayment($payment);

            $this->reconcileEntity($payment);
        }
        //
        // We marked payment as refunded if payment is authorized and registration is rejected
        //
        else if (($data[self::TOKEN_STATUS] === Token\RecurringStatus::REJECTED) and ($payment->isAuthorized() === true))
        {
            $refund = $this->refundPayment($payment);

            $this->reconcileEntity($refund);
        }
    }

    protected function getMappedAttributes($attributes)
    {
        $attrs = [];

        $map = $this->gatewayPaymentMapping;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]) === true)
            {
                $newKey = $map[$key];
                $attrs[$newKey] = $value;
            }
            else
            {
                $attrs[$key] = $value;
            }
        }

        return $attrs;
    }

    protected function captureAuthorizedPayment(Payment\Entity $payment)
    {
        if ($payment->isAuthorized() === false)
        {
            $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'status' => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            if ((in_array($payment->getGateway(), Payment\Gateway::$verifyDisabled, true) === true) or
                ($payment->isStatusCreatedOrFailed() === false))
            {
                return;
            }

            $response = $this->paymentProcessor->authorizeFailedPayment($payment);

            if ($response['status'] !== Payment\Status::AUTHORIZED)
            {
                $this->trace->critical(TraceCode::PAYMENT_AUTHORIZE_FAILED_FAILURE,
                    [
                        'status' => $payment->getStatus(),
                        'payment_id' => $payment->getId(),
                    ]);

                return;
            }
        }

        $amount = $payment->getAmount();

        // The payment amount is inclusive of fees, so we need to capture with the original amount.
        if ($payment->isFeeBearerCustomer() === true)
        {
            $amount = $amount - $payment->getFee();
        }

        $parameters = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $payment->getCurrency()
        ];

        //
        // We do not capture the payment if its already refunded
        // We are not putting it inside a try-catch block as
        // it's already under transaction and we don't want
        // token to be confirmed if there is any bug on our end
        //
        $this->paymentProcessor->capture($payment, $parameters);
    }

    protected function refundPayment($payment)
    {
        if ($payment->isAuthorized() === false)
        {
            $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'status' => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            return;
        }

        // based on experiment, refund request will be routed to Scrooge
        return (new Payment\Processor\Processor($payment->merchant))->refundAuthorizedPayment($payment);
    }

    protected function updateTokenEntity(Token\Entity $token, array $content, Payment\Entity $payment)
    {
        // In some gateways like HDFC, there's no gateway token
        $gatewayToken = $content[self::GATEWAY_TOKEN] ?? null;

        $currentRecurringStatus = $token->getRecurringStatus();

        $newRecurringStatus = $content[self::TOKEN_STATUS];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $newRecurringStatus)
            {
                $this->trace->critical(TraceCode::CUSTOMER_TOKEN_STATUS_MISMATCH,
                    [
                        'new_status'     => $newRecurringStatus,
                        'current_status' => $currentRecurringStatus,
                    ]);
            }

            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $newRecurringStatus,
            Token\Entity::GATEWAY_TOKEN             => $gatewayToken,
            Token\Entity::RECURRING_FAILURE_REASON  => $content[self::TOKEN_ERROR_CODE],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        if ($token->getTerminalId() === null)
        {
            $token->terminal()->associate($payment->terminal);
        }

        $this->repo->saveOrFail($token);
    }

    protected function forceAuthorizeIfApplicable(Payment\Entity $payment, array $data)
    {
        // Override this in child class if required
        return [$payment, true];
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }

    protected function sendProcessedMail()
    {
        return;
    }

    protected function fetchPaymentEntity($data): Payment\Entity
    {
        return $this->repo->payment->findOrFailPublic($data[self::PAYMENT_ID]);
    }

    protected function shouldUpdateBatchOutputWithPaymentId()
    {
        return false;
    }
}
