<?php

namespace RZP\Models\Batch\Processor\Nach\Register;

use ZipArchive;
use Storage;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Batch;
use DirectoryIterator;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Customer\Token;
use RZP\Gateway\Enach\Citi\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Batch\Processor\Nach\Base as BaseProcessor;


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
    const GATEWAY_ERROR_MESSAGE       = 'gateway_error_message';
    const INTERNAL_ERROR_CODE         = 'internal_error_code';
    // Stored in token entity
    const TOKEN_ERROR_CODE            = 'token_error_code';
    /**
     * @var Payment\Processor\Processor
     */
    protected $paymentProcessor;

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

            if ($parsedData[self::TOKEN_STATUS] === Token\RecurringStatus::INITIATED)
            {
                return;
            }

            $token = $payment->getGlobalOrLocalTokenEntity();

            $oldRecurringStatus = $token->getRecurringStatus();

            $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

            $this->repo->transaction(function () use ($payment, $token, $parsedData) {
                $this->updateTokenEntity($token, $parsedData);

                $this->updateTokenRegistrationAndUpdatePayment($payment, $parsedData);
            });

            $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_REGISTER_RESPONSE_ERROR,
                [
                    'gateway' => $this->gateway
                ]
            );

            throw $ex;
        }
    }

    abstract protected function getDataFromRow(array $entry): array;
    abstract protected function getTokenStatus(string $gatewayTokenStatus, array $content): string;
    abstract protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry);

    protected function updateTokenRegistrationAndUpdatePayment(Payment\Entity $payment, array $content)
    {
        $this->updateTokenRegistration($payment);

        $this->updatePayment($payment, $content);
    }

    public function updateTokenRegistration(Payment\Entity $payment)
    {
        if ($payment->hasInvoice() === true)
        {
            $invoice = $payment->invoice;

            if ($invoice->getEntityType() === Constants\Entity::SUBSCRIPTION_REGISTRATION)
            {
                $subscriptionRegistration = $invoice->entity;

                $token = $payment->getGlobalOrLocalTokenEntity();

                (new SubscriptionRegistration\Core)->authenticate($subscriptionRegistration, $token);
            }
        }
    }

    protected function updatePayment(Payment\Entity $payment, array $content)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token->getRecurringStatus() === Token\RecurringStatus::CONFIRMED)
        {
            return $this->processAuthorizedPayment($payment);
        }

        return $this->processFailedPayment($payment, $content);
    }

    public function processFailedPayment(Payment\Entity $payment, array $content)
    {
        if ($payment->isFailed() === true)
        {
            $this->trace->info(TraceCode::PAYMENT_STATUS_FAILED, ['payment_id' => $payment->getId()]);

            return;
        }

        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $errorCode = $this->getApiErrorCode($content);

        $e = new Exception\GatewayErrorException(
            $errorCode,
            $content[self::GATEWAY_ERROR_CODE] ?? null,
            $this->getGatewayErrorDesc($content),
            [
                'payment_id' => $payment->getId(),
                'gateway'    => $this->gateway,
            ]);

        $processor = $processor->setPayment($payment);

        $processor->updatePaymentAuthFailed($e);
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    protected function getGatewayErrorDesc(array $content): string
    {
        return PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED;
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        $data = $processor->processAuth($payment);

        if ($payment->hasBeenCaptured() === false)
        {
            $this->captureAuthorizedPayment($payment);
        }

        $this->reconcileEntity($payment);

        return $data;
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

            return;
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

    protected function updateTokenEntity(Token\Entity $token, array $content)
    {
        $gatewayToken = $content[self::GATEWAY_TOKEN];

        $currentRecurringStatus = $token->getRecurringStatus();

        $newRecurringStatus = $content[self::TOKEN_STATUS];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $newRecurringStatus)
            {
                $this->trace->critical(TraceCode::CUSTOMER_TOKEN_STATUS_MISMATCH,
                    [
                        'token_id'       => $token->getId(),
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

        (new Token\Core)->updateTokenFromNachGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
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
}
