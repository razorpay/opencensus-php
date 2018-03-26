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
            'payment_id'         => trim($originalMandate['MndtReqId']),
            'umrn'               => trim($originalMandate['MndtId']),
            'reference_id'       => $headerRow['MsgId'],
            'acknowledge_status' => $status,
            'account_number'     => trim($originalMandate['DbtrAcct']['Id']['Othr']['Id']),
            'token_status'       => $this->getTokenStatus($status),
            'error_message'      => null,
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
        $paymentId = $content['payment_id'];

        $accountNumber = $content['account_number'];
        // Get payment
        $payment = $this->repo->payment->findOrFail($paymentId);

        $token = $payment->getGlobalOrLocalTokenEntity();

        if (($payment->getGateway() !== $this->gateway) or
            ($token === null) or
            ($token->getAccountNumber() !== $accountNumber))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                null,
                null,
                ['payment_id' => $payment->getId()]);
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
        $paymentId = $content['payment_id'];

        $gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail(
            $paymentId, GatewayAction::AUTHORIZE);

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
        $newStatus = $content['token_status'];

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
            Token\Entity::RECURRING_FAILURE_REASON  => $content['error_message'],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        $gatewayTokenStatus = strtolower($gatewayTokenStatus);

        if ($gatewayTokenStatus === Status::ACKNOWLEDGE_SUCCESS)
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
