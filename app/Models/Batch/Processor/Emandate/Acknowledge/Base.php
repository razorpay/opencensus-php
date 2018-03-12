<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
use RZP\Models\Batch\Processor\Base as BaseProcessor;
use RZP\Gateway\Enach\Base\AcknowledgeFileHeadings as Headings;

class Base extends BaseProcessor
{
    const TRUE = 'true';
    const FALSE = 'false';

    protected static $statusMap = [
        self::TRUE   => Token\RecurringStatus::INITIATED,
        self::FALSE  => Token\RecurringStatus::REJECTED,
     ];

    // Return single XML row as multiple entries
    protected function parseXmlFile($file)
    {
        $xmlObject = simplexml_load_file($file);

        return [
            ['data' => json_decode(json_encode($xmlObject), true)]
        ];
    }

    protected function processEntry(array & $entry)
    {
        $row = $entry['data'];

        $data = $this->getDataFromRow($row);

        $this->updateEntities($data);
    }

    protected function getDataFromRow(array & $row): array
    {
        $row = $row['MndtAccptncRpt']['UndrlygAccptncDtls'];

        $originalMandate = $row['OrgnlMndt']['OrgnlMndt'];
        $parsedStatus = trim($row['AccptncRslt']['Accptd']);
        $umrn = trim($originalMandate['MndtId']);

        return [
            'payment_id'         => trim($originalMandate['MndtReqId']),
            'umrn'               => $umrn,
            'acknowledge_status' => $parsedStatus,
            'account_number'     => trim($originalMandate['DbtrAcct']['Id']['Othr']['Id']),
            'token_status'       => $this->getTokenStatus($parsedStatus),
            'error_message'      => null,
        ];
    }

    protected function updateEntities(array $parsedData)
    {
        $paymentId = $parsedData['payment_id'];

        $accountNumber = $parsedData['account_number'];

        // Update gateway payment
        $gatewayPayment = $this->updateGatewayPayment($parsedData);

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                        $this->gateway,
                        $paymentId,
                        $accountNumber);

        // Update token
        $this->updateToken($parsedData, $payment);
    }

    /**
     * @param array $parsedData
     * @param $parsedData['payment_id']
     * @param $parsedData['status']
     * @param $parsedData['error_message']
     *
     * @return EnachEntity
     */
    protected function updateGatewayPayment(array $parsedData): EnachEntity
    {
        $paymentId = $parsedData['payment_id'];

        $gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail(
            $paymentId, GatewayAction::AUTHORIZE);

        $attrs = $this->getGatewayAttributes($parsedData);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateToken(array $parsedData, Payment\Entity $payment)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        $currentRecurringStatus = $token->getRecurringStatus();
        $parsedStatus = $parsedData['token_status'];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $parsedStatus)
            {
                throw new Exception\LogicException(
                    'Token status mismatch: current_status: ' . $currentRecurringStatus . ', parsed_status: ' . $parsedStatus);
            }

            // If the token has already been updated with the correct value
            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $parsedStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $parsedData['error_message'],
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        $gatewayTokenStatus = strtolower($gatewayTokenStatus);

        if (isset(self::$statusMap[$gatewayTokenStatus]) === false)
        {
            throw new Exception\LogicException(
                'Unrecognized gateway status: ' . $gatewayTokenStatus);
        }

        return self::$statusMap[$gatewayTokenStatus];
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        $gatewayStatus = $parsedData['status'];

        if (in_array($gatewayStatus, [self::TRUE, self::FALSE], true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS,
                '',
                '',
                ['parsed_data' => $parsedData]);
        }

        return [
            EnachEntity::RECEIVED           => true,
            EnachEntity::ERROR_MESSAGE      => $parsedData['error_message'],
            EnachEntity::ACKNOWLEDGE_STATUS => $gatewayStatus,
        ];
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    protected function createSetOutputFileAndSave(array & $entries)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }
}