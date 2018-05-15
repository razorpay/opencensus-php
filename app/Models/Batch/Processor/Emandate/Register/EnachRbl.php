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
use RZP\Gateway\Base\Entity as GatewayEntity;

class EnachRbl extends Base
{
    const GATEWAY = Gateway::ENACH_RBL;

    const REGISTRATION_STATUS = 'registration_status';
    const ERROR_CODE          = 'error_code';
    const PAYMENT_ID          = 'payment_id';

    protected function processEntry(array & $entry)
    {
        $entry = array_map('trim', $entry);

        //
        // Expects $parsedData to have keys
        // 'gateway_token'       : Corresponds to Token\Entity::GATEWAY_TOKEN
        // 'status'              : Corresponds to Token\Entity::RECURRING_STATUS
        // 'account_number'      : Corresponds to Token\Entity::ACCOUNT_NUMBER
        // 'registration_status' : Corresponds to something
        //
        $content = $this->getDataFromRow($entry);

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

        $this->repo->transaction(function() use ($payment, $token, $gatewayPayment, $content)
        {
            $this->updateGatewayPaymentEntityAndCapturePayment($payment, $gatewayPayment, $content);

            $this->updateTokenEntity($token, $content);
        });

        //
        // This should be done outside the transaction only!
        //
        $this->paymentProcessor->eventTokenStatus($token, $oldRecurringStatus);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function getDataFromRow(array & $entry): array
    {
        $gatewayToken = $entry[Batch\Header::ENACH_REGISTER_UMRN];

        $gatewayTokenStatus = $entry[Batch\Header::ENACH_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus);

        $accountNumber = $entry[Batch\Header::ENACH_REGISTER_ACNO];

        return [
            self::GATEWAY_TOKEN       => $gatewayToken,
            self::TOKEN_STATUS        => $status,
            self::ACCOUNT_NUMBER      => $accountNumber,
            self::ERROR_MESSAGE       => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID          => $entry[Batch\Header::ENACH_REGISTER_REF_1],
            // We are getting registration_status and error_code because we want to store
            // the actual registration status received in the file, in the gateway entity
            self::REGISTRATION_STATUS => $gatewayTokenStatus,
            self::ERROR_CODE          => $entry[Batch\Header::ENACH_REGISTER_RETURN_CODE],
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

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        if ($this->getTokenStatus($gatewayTokenStatus) === Token\RecurringStatus::CONFIRMED)
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
