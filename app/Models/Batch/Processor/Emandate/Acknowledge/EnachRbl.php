<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use Config;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Gateway\Enach\Rbl\Status;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

class EnachRbl extends Base
{
    const PAYMENT_ID         = 'payment_id';
    const UMRN               = 'umrn';
    const ACKNOWLEDGE_STATUS = 'acknowledge_status';
    const ACCOUNT_NUMBER     = 'account_number';
    const TOKEN_STATUS       = 'token_status';
    const ERROR_MESSAGE      = 'error_message';

    protected $gateway = Payment\Gateway::ENACH_RBL;

    /**
     * {@override}
     * @param  string $filePath
     * @param int $numRowsToSkip
     * @return array
     */
    protected function parseExcelSheetsUsingPhpSpreadSheet($filePath, $numRowsToSkip = 0): array
    {
        $fileType = SpreadsheetIOFactory::identify($filePath);
        $reader = SpreadsheetIOFactory::createReader($fileType);
        $reader->setReadDataOnly(true);
        // Override 1: Loads specific named sheet only.
        $reader->setLoadSheetsOnly('ACKNOWLEDGMENT REPORT');
        $spreadsheet = $reader->load($filePath);
        assertTrue($spreadsheet->getSheetCount() === 1);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, false);
        // Override 2: Shifts through 1 row. 1st row in this particular file is not to be considered.
        array_shift($rows);
        // First row is always expected to be header
        $headers = array_values(array_shift($rows) ?? []);
        // No rows exists
        if (empty($headers) === true)
        {
            return [];
        }
        // Format rows as "heading key => value" kind of associative array
        foreach ($rows as & $row)
        {
            $row = array_combine($headers, array_values($row));
        }

        return $rows;
    }

    protected function processEntry(array & $entry)
    {
        $entry = array_map('trim', $entry);

        $content = $this->getDataFromRow($entry);

        $this->updateEntities($content);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     * @param  array $entry
     * @return array
     */
    protected function getDataFromRow(array $entry): array
    {
        $tokenStatus = $this->getTokenStatus($entry);

        $status = (($tokenStatus === Token\RecurringStatus::INITIATED) ? true : false);

        return [
            self::PAYMENT_ID            => $entry[Batch\Header::ENACH_ACK_REF_1],
            self::UMRN                  => $entry[Batch\Header::ENACH_ACK_UMRN],
            self::ACKNOWLEDGE_STATUS    => $status,
            self::ACCOUNT_NUMBER        => $entry[Batch\Header::ENACH_ACK_ACNO],
            self::TOKEN_STATUS          => $tokenStatus,
            self::ERROR_MESSAGE         => $this->getTokenErrorMessage($tokenStatus, $entry),
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

        $oldRecurringStatus = $token->getRecurringStatus();

        $this->repo->transaction(function() use ($token, $content)
        {
            $this->updateGatewayPaymentEntity($content);

            $this->updateTokenEntity($token, $content);
        });

        (new Payment\Processor\Processor($payment->merchant))->eventTokenStatus($token, $oldRecurringStatus);
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
            Token\Entity::ACKNOWLEDGED_AT           => Carbon::now(Timezone::IST)->getTimestamp()
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(array $entry): string
    {
        if ((empty($entry[Batch\Header::ENACH_ACK_UMRN]) === false) and
            (strlen($entry[Batch\Header::ENACH_ACK_UMRN]) > 10))
        {
            return Token\RecurringStatus::INITIATED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $tokenStatus, array $entry)
    {
        if ($tokenStatus === Token\RecurringStatus::INITIATED)
        {
            return null;
        }

        return $entry[Batch\Header::ENACH_ACK_ACK_DESC] ?? 'Failed';
    }

    protected function getGatewayAttributes(array $content): array
    {
        return [
            EnachEntity::ACKNOWLEDGE_STATUS   => $content[self::ACKNOWLEDGE_STATUS],
            EnachEntity::UMRN                 => $content[self::UMRN],
        ];
    }
}
