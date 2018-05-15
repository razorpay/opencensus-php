<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base\Entity as GatewayEntity;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    /**
     * Params expected in the getDataFromRow method's response
     */
    const TOKEN_ID         = 'token_id';
    const GATEWAY_TOKEN_ID = 'gateway_token_id';
    const STATUS           = 'status';
    const REMARK           = 'remark';
    const ACCOUNT_NUMBER   = 'account_number';

    /**
     * @var Payment\Processor\Processor
     */
    protected $paymentProcessor;

    // Used for mapping the file content to the corresponding gateway entity
    protected $gatewayPaymentMapping;

    protected function processEntry(array & $entry)
    {
        //
        // Expects $parsedData to have keys
        // 'token_id'         : Corresponds to Token\Entity::ID
        // 'status'           : Corresponds to Token\Entity::RECURRING_STATUS
        // 'remark'           : Corresponds to Token\Entity::RECURRING_FAILURE_REASON
        // 'gateway_token_id' : Corresponds to Token\Entity::GATEWAY_TOKEN
        // 'account_number'   : Corresponds to Token\Entity::ACCOUNT_NUMBER
        //
        $parsedData = $this->getDataFromRow($entry);

        $tokenId = $parsedData[self::TOKEN_ID];

        $gatewayToken = $parsedData[self::GATEWAY_TOKEN_ID];

        $accountNumber = $parsedData[self::ACCOUNT_NUMBER];

        $payment = $this->repo->payment->fetchByTokenId($tokenId);

        $gatewayPayment = $this->getGatewayPayment($payment);

        $token = $this->repo->token->getTokenByIdAndAccountNumber($tokenId, $accountNumber);

        $oldRecurringStatus = $token->getRecurringStatus();

        $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

        $this->repo->transaction(function() use ($payment, $token, $gatewayPayment, $gatewayToken, $parsedData)
        {
            $this->updateGatewayPaymentEntityAndCapturePayment($payment, $gatewayPayment, $parsedData);

            $this->updateTokenEntity($token, $parsedData);
        });

        $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    /**
     * Child class must implement it
     *
     * @param array $entry
     */
    protected function getDataFromRow(array & $entry)
    {
        throw new \BadMethodCallException();
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }

    /**
     * To be overridden by the child classes.
     *
     * @param Payment\Entity $payment
     */
    protected function getGatewayPayment(Payment\Entity $payment)
    {
        throw new \BadMethodCallException();
    }

    protected function updateGatewayPaymentEntityAndCapturePayment(
        Payment\Entity $payment,
        GatewayEntity $gatewayPayment,
        array $content
    )
    {
        $data = $this->getMappedAttributes($content);

        $gatewayPayment->fill($data);

        $this->repo->saveOrFail($gatewayPayment);

        //
        // We do capture ONLY if registration is successful AND it's not already captured.
        //
        if (($content[self::STATUS] === Token\RecurringStatus::CONFIRMED) and
            ($payment->hasBeenCaptured() === false))
        {
            $this->captureAuthorizedPayment($payment);
        }
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->gatewayPaymentMapping;

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
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
        $gatewayToken = $content[self::GATEWAY_TOKEN_ID];

        $currentRecurringStatus = $token->getRecurringStatus();

        $newRecurringStatus = $content[self::STATUS];

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
            Token\Entity::RECURRING_FAILURE_REASON  => $content[self::REMARK],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }
}
