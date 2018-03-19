<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use RZP\Exception;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
// use RZP\Gateway\Enach\Rbl\EMandateRegisterFileHeadings as Headings;

class EnachRbl extends Base
{
    const GATEWAY   = Gateway::ENACH_RBL;

    const ACTIVE   = 'active';
    const REJECT   = 'reject';

    protected static $statusMap = [
        self::ACTIVE => Token\RecurringStatus::CONFIRMED,
        self::REJECT => Token\RecurringStatus::REJECTED,
     ];

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
        $parsedData = $this->getDataFromRow($entry);

        $gatewayToken = $parsedData['gateway_token'];

        $accountNumber = $parsedData['account_number'];

        $remark = $parsedData['remark'];

        $gatewayPayment = $this->repo
                               ->enach
                               ->findByUmrnAndAckStatus($gatewayToken);

        $payment = $gatewayPayment->payment;
        $token = $payment->getGlobalOrLocalTokenEntity();

        $currentRecurringStatus = $token->getRecurringStatus();

        $parsedStatus = $parsedData['status'];

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

        $this->updatePaymentEntities($payment, $gatewayPayment, $parsedData);

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $parsedStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $remark,
            Token\Entity::GATEWAY_TOKEN             => $gatewayToken
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function updatePaymentEntities(Payment\Entity $payment, EnachEntity $gatewayPayment, array $data)
    {
        $gatewayPayment->fill($data);

        $this->repo->saveOrFail($gatewayPayment);

        if ($data['status'] === self::ACTIVE)
        {
            return $this->capturePayment($payment);
        }
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $amount = $payment->getAmount();

        // The payment amount is inclusive of fees, so we need to capture with the original amount.
        if ($payment->merchant->isFeeBearerCustomer() === true)
        {
            $amount = $amount - $payment->getFee();
        }

        $params = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $payment->getCurrency()
        ];

        // We do not capture the payment if its already refunded
        if (($payment->isPartiallyOrFullyRefunded() === false) and
            ($payment->hasBeenCaptured() === false))
        {
            $paymentProcessor->capture($payment, $params);
        }

        return $processor->processAuth($payment);
    }

    protected function getDataFromRow(array & $entry): array
    {
        $gatewayToken = $entry['UMRN'];

        $status = $entry['STATUS'];
        $status = $this->getTokenStatus($status);

        $accountNumber = $entry['ACNO'];

        return [
            'gateway_token'       => $gatewayToken,
            'token_status'        => $status,
            'registration_status' => $entry['STATUS'],
            'account_number'      => $accountNumber,
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        $gatewayTokenStatus = strtolower($gatewayTokenStatus);

        if (isset(self::$statusMap[$gatewayTokenStatus]) === false)
        {
            return Token\RecurringStatus::REJECTED;
        }

        return self::$statusMap[$gatewayTokenStatus];
    }

    protected function parseExcelSheets($filePath)
    {
        Config::set('excel.import.force_sheets_collection', true);
        Config::set('excel.import.heading', 'original');
        Config::set('excel.import.startRow', 2);

        $sheets = $this->parseExcelFile($filePath);

        $hasSingleSheet  = (count($sheets) === 1);
        $errorMessage    = 'Sheets keys: ' . implode('.', array_keys($sheets));

        assertTrue($hasSingleSheet, $errorMessage);

        //
        // We use head() instead of integer index as sheets might be
        // an associative array.
        //
        return head($sheets);
    }
}
