<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base\Entity as GatewayEntity;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

abstract class Base extends BaseProcessor
{
    /**
     * Params expected in the getDataFromRow method's response
     */
    const GATEWAY_TOKEN  = 'gateway_token';
    const TOKEN_STATUS   = 'token_status';
    const ERROR_MESSAGE  = 'error_message';
    const PAYMENT_ID     = 'payment_id';

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
        $parsedData = $this->getDataFromRow($entry);

        // TODO: FIX for HDFC! We are getting token id there currently.
        $payment = $this->repo->payment->findOrFailPublic($parsedData[self::PAYMENT_ID]);

        $gatewayPayment = $this->getGatewayPayment($payment);

        $token = $payment->getGlobalOrLocalTokenEntity();

        $oldRecurringStatus = $token->getRecurringStatus();

        $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

        $this->repo->transaction(function() use ($payment, $token, $gatewayPayment, $parsedData)
        {
            $this->updateGatewayPaymentEntityAndCapturePayment($payment, $gatewayPayment, $parsedData);

            $this->updateTokenEntity($token, $parsedData);
        });

        $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    abstract protected function getDataFromRow(array $entry): array;
    abstract protected function getTokenStatus(string $gatewayTokenStatus): string;
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

            return;
        }

        $amount = $payment->getAmount();

        // The payment amount is inclusive of fees, so we need to capture with the original amount.
        if ($payment->merchant->isFeeBearerCustomer() === true)
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
            Token\Entity::RECURRING_FAILURE_REASON  => $content[self::ERROR_MESSAGE],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }
}
