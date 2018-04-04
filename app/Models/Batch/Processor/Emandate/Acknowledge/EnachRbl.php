<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use Config;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Gateway\Enach\Rbl\Status;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;

class EnachRbl extends Base
{
    const PAYMENT_ID         = 'payment_id';
    const UMRN               = 'umrn';
    const REFERENCE_ID       = 'reference_id';
    const ACKNOWLEDGE_STATUS = 'acknowledge_status';
    const ACCOUNT_NUMBER     = 'account_number';
    const TOKEN_STATUS       = 'token_status';
    const ERROR_MESSAGE      = 'error_message';

    protected $gateway = Payment\Gateway::ENACH_RBL;

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

    protected function processEntry(array & $entry)
    {
        $entry = array_map('trim', $entry);

        $content = $this->getDataFromRow($row);

        $this->updateEntities($content);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     * @param  array $entry
     * @return array
     */
    protected function getDataFromRow(array & $entry): array
    {
        // TODO: FIX!
        $status = 'true';
        // TODO: FIX!
        $referenceId = 'check';

        return [
            self::PAYMENT_ID            => $entry[Batch\Header::ENACH_ACK_REF_1],
            self::UMRN                  => $entry[Batch\Header::ENACH_ACK_UMRN],
            self::REFERENCE_ID          => $referenceId,
            self::ACKNOWLEDGE_STATUS    => $status,
            self::ACCOUNT_NUMBER        => $entry[Batch\Header::ENACH_ACK_ACNO],
            self::TOKEN_STATUS          => $this->getTokenStatus($status),
            self::ERROR_MESSAGE         => $this->getTokenErrorMessage($status),
        ];
    }

    /**
     * @param array $content
     *
     * @throws Exception\GatewayErrorException
     */
    protected function updateEntities(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $accountNumber = $content[self::ACCOUNT_NUMBER];
        // Get payment
        $payment = $this->repo->payment->findOrFail($paymentId);

        $token = $payment->getGlobalOrLocalTokenEntity();

        if (($payment->hasBeenAuthorized() === false) or
            ($payment->getGateway() !== $this->gateway) or
            ($token === null) or
            ($token->getAccountNumber() !== $accountNumber))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_RECURRING_PAYMENT_NOT_FOUND,
                null,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'account_number' => $accountNumber,
                    'token_id' => $token->getId(),
                    'gateway' => 'enach_rbl'
                ]);
        }

        $this->repo->transaction(function() use ($token, $content)
        {
            $this->updateGatewayPaymentEntity($content);

            $this->updateTokenEntity($token, $content);
        });
    }

    /**
     * @param array $content
     *
     * @return EnachEntity
     */
    protected function updateGatewayPaymentEntity(array $content): EnachEntity
    {
        $paymentId = $content[self::PAYMENT_ID];

        $gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail(
            $paymentId, GatewayAction::AUTHORIZE);

        if ((empty($gatewayPayment[EnachEntity::UMRN]) === false) and
            ($gatewayPayment[EnachEntity::UMRN] !== $content[self::UMRN]))
        {
            $this->trace->critical(TraceCode::GATEWAY_TOKEN_MISMATCH, [
                'umrn' => $content[self::UMRN],
                'gateway' => 'enach_rbl',
                'payment_id' => $paymentId,
            ]);
        }

        $attributes = $this->getGatewayAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    /**
     * @param Token\Entity $token
     * @param array       $content
     *
     * @throws Exception\LogicException
     */
    protected function updateTokenEntity(Token\Entity $token, array $content)
    {
        $currentRecurringStatus = $token->getRecurringStatus();
        $newStatus = $content[self::TOKEN_STATUS];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $newStatus)
            {
                throw new Exception\LogicException(
                    'Token status mismatch',
                    null,
                [
                        'new_status' => $newStatus,
                        'current_status' => $currentRecurringStatus,
                    ]);
            }

            // If the token has already been updated with the correct value
            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $newStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $content[self::ERROR_MESSAGE],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        if (Status::isAcknowledgeSuccess($gatewayTokenStatus) === true)
        {
            return Token\RecurringStatus::INITIATED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus)
    {
        if (Status::isAcknowledgeSuccess($gatewayTokenStatus) === true)
        {
            return null;
        }

        return 'FAILED';
    }

    protected function getGatewayAttributes(array $content): array
    {
        return [
            EnachEntity::ACKNOWLEDGE_STATUS   => $content[self::ACKNOWLEDGE_STATUS],
            EnachEntity::UMRN                 => $content[self::UMRN],
            EnachEntity::GATEWAY_REFERENCE_ID => $content[self::REFERENCE_ID],
        ];
    }
}
