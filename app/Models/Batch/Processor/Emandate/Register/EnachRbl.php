<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Enach\Rbl;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;

class EnachRbl extends Base
{
    const GATEWAY = Gateway::ENACH_RBL;

    const GATEWAY_TOKEN       = 'gateway_token';
    const TOKEN_STATUS        = 'token_status';
    const REGISTRATION_STATUS = 'registration_status';
    const ACCOUNT_NUMBER      = 'account_number';
    const ERROR_MESSAGE       = 'error_message';
    const ERROR_CODE          = 'error_code';
    const PAYMENT_ID          = 'payment_id';

    /**
     * @var Payment\Processor\Processor
     */
    protected $paymentProcessor;

    protected function processEntry(array & $entry)
    {
        $entry = array_map('trim', $entry);

        //
        // Expects $parsedData to have keys
        // 'gateway_token'       : Corresponds to Token\Entity::GATEWAY_TOKEN
        // 'status'              : Corresponds to Token\Entity::RECURRING_STATUS
        // 'registration_status' : Corresponds to EnachEntity::REGISTRATION_STATUS
        // 'account_number'      : Corresponds to Token\Entity::ACCOUNT_NUMBER
        //
        $content = $this->getDataFromRow($entry);

        $gatewayToken = $content[self::GATEWAY_TOKEN];

        $payment = $this->repo->payment->findOrFailPublic($content[self::PAYMENT_ID]);

        $gatewayPayment = $this->repo
                               ->enach
                               ->findAuthorizedPaymentByPaymentId($payment->getId());

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($payment->hasBeenAuthorized() === false)
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_RECURRING_PAYMENT_NOT_FOUND,
                null,
                null,
                [
                    'gateway'    => 'enach_rbl',
                    'token_id'   => $token->getId(),
                    'payment_id' => $payment->getId(),
                ]);
        }

        $oldRecurringStatus = $token->getRecurringStatus();

        $this->paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

        $this->repo->transaction(function() use ($payment, $token, $gatewayPayment, $gatewayToken, $content)
        {
            $this->updateGatewayPaymentEntityAndCapturePayment($payment, $gatewayPayment, $content);

            $this->updateTokenEntity($token, $gatewayToken, $content);
        });

        //
        // This should be done outside the transaction only!
        //
        $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function updateTokenEntity(Token\Entity $token, $gatewayToken, array $content)
    {
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

    protected function updateGatewayPaymentEntityAndCapturePayment(
        Payment\Entity $payment,
        EnachEntity $gatewayPayment,
        array $data)
    {
        $gatewayPayment->fill($data);

        $this->repo->saveOrFail($gatewayPayment);

        //
        // We do capture ONLY if registration is successful AND it's not already captured.
        //
        if ((Rbl\Status::isRegistrationSuccess($data[self::REGISTRATION_STATUS]) === true) and
            ($payment->hasBeenCaptured() === false))
        {
            $this->captureAuthorizedPayment($payment);
        }
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

    protected function getDataFromRow(array & $entry): array
    {
        $gatewayToken = $entry[Batch\Header::ENACH_REGISTER_UMRN];

        $status = $this->getTokenStatus($entry[Batch\Header::ENACH_REGISTER_STATUS]);

        $accountNumber = $entry[Batch\Header::ENACH_REGISTER_ACNO];

        return [
            self::GATEWAY_TOKEN       => $gatewayToken,
            self::TOKEN_STATUS        => $status,
            self::REGISTRATION_STATUS => $entry[Batch\Header::ENACH_REGISTER_STATUS],
            self::ACCOUNT_NUMBER      => $accountNumber,
            self::PAYMENT_ID          => $entry[Batch\Header::ENACH_REGISTER_REF_1],
            self::ERROR_CODE          => $entry[Batch\Header::ENACH_REGISTER_RETURN_CODE],
            self::ERROR_MESSAGE       => $this->getTokenErrorMessage($entry),
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        if (Rbl\Status::isRegistrationSuccess($gatewayTokenStatus) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(array $entry)
    {
        if (Rbl\Status::isRegistrationSuccess($entry[Batch\Header::ENACH_REGISTER_STATUS]) === true)
        {
            return null;
        }
        else
        {
            return $entry[Batch\Header::ENACH_REGISTER_CODE_DESC] ?? 'FAILED';
        }
    }

    /**
     * Overriding parseExcelSheets() because of different startRow.
     * Ideally, we should store `$startRow` in a variable and then use.
     * @param  string $filePath
     * @return array
     */
    protected function parseExcelSheets($filePath)
    {
        Config::set('excel.import.force_sheets_collection', true);
        Config::set('excel.import.heading', 'original');
        Config::set('excel.import.startRow', 2);

        $sheets = $this->parseExcelFile($filePath);

        //
        // Resetting startRow to 1 again
        //
        Config::set('excel.import.startRow', 1);

        $hasDoubleSheets  = (count($sheets) === 2);
        $errorMessage    = 'Sheets keys: ' . implode('.', array_keys($sheets));

        assertTrue($hasDoubleSheets, $errorMessage);

        //
        // We use 2nd index as 1st sheet contains the summary and
        // 2nd sheet contains th actual recon data
        //
        return $sheets[1];
    }
}
