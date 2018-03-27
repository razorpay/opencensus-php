<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
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

    // Return single XML row as multiple entries
    protected function parseFile(string $filePath): array
    {
        $xmlObject = simplexml_load_file($filePath);

        return [
            ['data' => json_decode(json_encode($xmlObject), true)]
        ];
    }

    protected function processEntry(array & $entry)
    {
        $row = $entry['data'];

        $data = $this->getDataFromRow($row);

        $this->updateEntities($data);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     * @param  array $row
     * @return array
     */
    protected function getDataFromRow(array & $row): array
    {
        $details = $row['MndtAccptncRpt']['UndrlygAccptncDtls'];
        $headerRow = $row['MndtAccptncRpt']['GrpHdr'];

        $originalMandate = $details['OrgnlMndt']['OrgnlMndt'];
        $status = trim($details['AccptncRslt']['Accptd']);

        return [
            self::PAYMENT_ID         => trim($originalMandate['MndtReqId']),
            self::UMRN               => trim($originalMandate['MndtId']),
            self::REFERENCE_ID       => $headerRow['MsgId'],
            self::ACKNOWLEDGE_STATUS => $status,
            self::ACCOUNT_NUMBER     => trim($originalMandate['DbtrAcct']['Id']['Othr']['Id']),
            self::TOKEN_STATUS       => $this->getTokenStatus($status),
            self::ERROR_MESSAGE      => $this->getTokenErrorMessage($status),
        ];
    }

    /**
     * @param array $content
     *
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    protected function updateEntities(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $accountNumber = $content[self::ACCOUNT_NUMBER];
        // Get payment
        $payment = $this->repo->payment->findOrFail($paymentId);

        $token = $payment->getGlobalOrLocalTokenEntity();

        if (($payment->getGateway() !== $this->gateway) or
            ($token === null) or
            ($token->getAccountNumber() !== $accountNumber))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_TOKEN_ABSENT_RECURRING_PAYMENT,
                null,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'account_number' => $accountNumber,
                    'token_id' => $token->getId(),
                    'gateway' => 'enach_rbl'
                ]);
        }

        // Update gateway payment
        $this->updateGatewayPaymentEntity($content);

        // Update token
        $this->updateTokenEntity($token, $content);
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
