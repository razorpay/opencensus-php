<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
// use RZP\Gateway\Enach\Rbl\EMandateRegisterFileHeadings as Headings;

class EnachRbl extends Base
{
    const GATEWAY   = Gateway::ENACH_RBL;

    const ACTIVE   = 'active';
    const REJECT   = 'reject';

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

        $gatewayPayment = $this->repo
                               ->enach
                               ->findByUmrnAndAckStatus($gatewayToken);

        $payment = $gatewayPayment->payment;
        $token = $payment->getGlobalOrLocalTokenEntity();

        $currentRecurringStatus = $token->getRecurringStatus();

        $parsedStatus = $parsedData['token_status'];

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
            Token\Entity::GATEWAY_TOKEN             => $gatewayToken
        ];

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function updatePaymentEntities(Payment\Entity $payment, EnachEntity $gatewayPayment, array $data)
    {
        $gatewayPayment->fill($data);

        $this->repo->saveOrFail($gatewayPayment);

        if ($data['registration_status'] === self::ACTIVE)
        {
            return $this->processAuthorizedPayment($payment);
        }
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $paymentProcessor = (new Payment\Processor\Processor($payment->merchant));

        $paymentProcessor->setPayment($payment);

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

        return $paymentProcessor->processAuth($payment);
    }

    protected function getDataFromRow(array & $entry): array
    {
        $gatewayToken = $entry['UMRN'];

        $registrationStatus = strtolower($entry['STATUS']);
        $status = $this->getTokenStatus($registrationStatus);

        $accountNumber = $entry['ACNO'];

        return [
            'gateway_token'       => $gatewayToken,
            'token_status'        => $status,
            'registration_status' => $registrationStatus,
            'account_number'      => $accountNumber,
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        if ($gatewayTokenStatus === self::ACTIVE)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
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
