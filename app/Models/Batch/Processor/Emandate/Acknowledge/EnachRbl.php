<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
use RZP\Gateway\Enach\Base\AcknowledgeFileHeadings as Headings;

class EnachRbl extends Base
{
    const TRUE = 'true';
    const FALSE = 'false';

    protected $gateway = Gateway::ENACH_RBL;

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
        s($data);
        $this->updateEntities($data);
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
        $umrn = trim($originalMandate['MndtId']);

        return [
            'payment_id'         => trim($originalMandate['MndtReqId']),
            'umrn'               => $umrn,
            'reference_id'       => $headerRow['MsgId'],
            'acknowledge_status' => $status,
            'account_number'     => trim($originalMandate['DbtrAcct']['Id']['Othr']['Id']),
            'token_status'       => $this->getTokenStatus($status),
            'error_message'      => null,
        ];
    }

    /**
     * @param array $parsedData
     */
    protected function updateEntities(array $content)
    {
        $paymentId = $content['payment_id'];

        $accountNumber = $content['account_number'];

        // Get payment
        $payment = $this->repo->payment->fetchEmandatePaymentPendingRegistration(
                        $this->gateway,
                        $paymentId,
                        $accountNumber);

        if (($payment->isFailed() === true) or
            ($payment->isCaptured() === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment has been already processed',
                ['payment_id'],
                ['payment_id' => $payment->getId()]);
        }

        // Update gateway payment
        $this->updateGatewayPaymentEntity($content);

        // Update token
        $this->updateTokenEntity($payment, $content);
    }

    /**
     * @param array $content

     * @return EnachEntity
     */
    protected function updateGatewayPaymentEntity(array $content): EnachEntity
    {
        $paymentId = $content['payment_id'];

        $gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail(
            $paymentId, GatewayAction::AUTHORIZE);

        $attributes = $this->getGatewayAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    /**
     * @param  Payment\Entity $payment
     * @param  array          $content
     */
    protected function updateTokenEntity(Payment\Entity $payment, array $content)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            $this->app['trace']->error(TraceCode::PAYMENT_TOKEN_NOT_FOUND,
                [
                    'payment_id' => $payment->getId()
                ]);
        }

        $currentRecurringStatus = $token->getRecurringStatus();
        $parsedStatus = $content['token_status'];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $parsedStatus)
            {
                throw new Exception\LogicException(
                    'Token status mismatch: ' .
                    PHP_EOL . 'current_status: ' .  $currentRecurringStatus .
                    PHP_EOL . 'parsed_status: ' . $parsedStatus);
            }

            // If the token has already been updated with the correct value
            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $parsedStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $content['error_message'],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        $gatewayTokenStatus = strtolower($gatewayTokenStatus);

        if ($gatewayTokenStatus === self::TRUE)
        {
            return Token\RecurringStatus::INITIATED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getGatewayAttributes(array $content): array
    {
        return [
            EnachEntity::ACKNOWLEDGE_STATUS   => $content['acknowledge_status'],
            EnachEntity::UMRN                 => $content['umrn'],
            EnachEntity::GATEWAY_REFERENCE_ID => $content['reference_id'],
        ];
    }
}
