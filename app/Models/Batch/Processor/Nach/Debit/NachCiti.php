<?php

namespace RZP\Models\Batch\Processor\Nach\Debit;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Citi\Status;
use RZP\Models\Payment\RecurringType;
use RZP\Gateway\Enach\Base\Entity as EnachEntity;
use RZP\Gateway\Enach\Citi\ErrorCodes as ErrorCode;
use RZP\Gateway\Enach\Citi\NRImprovementErrorCodes as NRErrorCode;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class NachCiti extends Base
{
    protected $gateway = Gateway::NACH_CITI;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        $paymentId       = substr($row[Headings::TRANSACTION_REFERENCE], -14);
        $accountNumber   = $row[Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER];
        $rejectionCode   = $row[Headings::REASON_CODE];
        $status          = $row[Headings::FLAG];
        $amount          = $row[Headings::AMOUNT];

        return [
            self::PAYMENT_ID            => $paymentId,
            self::ACCOUNT_NUMBER        => $accountNumber,
            self::GATEWAY_ERROR_CODE    => $rejectionCode,
            self::GATEWAY_RESPONSE_CODE => $status,
            self::AMOUNT                => $amount,
        ];
    }

    protected function parseTextRow(string $row, int $ix, string $delimiter, array $headings = null)
    {
        $values=[
            Headings::ACH_TRANSACTION_CODE             =>  substr($row, 0, 2),
            Headings::CONTROL_9S                       =>  substr($row, 2, 9),
            Headings::DESTINATION_ACCOUNT_TYPE         =>  substr($row, 11, 2),
            Headings::LEDGER_FOLIO_NUMBER              =>  substr($row, 13, 3),
            Headings::CONTROL_15S                      =>  substr($row, 16, 15),
            Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  substr($row, 31, 40),
            Headings::CONTROL_9SS                      =>  substr($row, 71, 9),
            Headings::CONTROL_7S                       =>  substr($row, 80, 7),
            Headings::USER_NAME                        =>  substr($row, 87, 20),
            Headings::CONTROL_13S                      =>  substr($row, 107, 13),
            Headings::AMOUNT                           =>  substr($row, 120, 13),
            Headings::ACH_ITEM_SEQ_NO                  =>  substr($row, 133, 10),
            Headings::CHECKSUM                         =>  substr($row, 143, 10),
            Headings::FLAG                             =>  substr($row, 153, 1),
            Headings::REASON_CODE                      =>  substr($row, 154, 2),
            Headings::DESTINATION_BANK_IFSC            =>  substr($row, 156, 11),
            Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  substr($row, 167, 35),
            Headings::SPONSOR_BANK_IFSC                =>  substr($row, 202, 11),
            Headings::USER_NUMBER                      =>  substr($row, 213, 18),
            Headings::TRANSACTION_REFERENCE            =>  substr($row, 231, 30),
            Headings::PRODUCT_TYPE                     =>  substr($row, 261, 3),
            Headings::BENEFICIARY_AADHAR_NUMBER        =>  substr($row, 264, 15),
            Headings::UMRN                             =>  substr($row, 279, 20),
            Headings::FILLER                           =>  substr($row, 299, 7),
        ];

        return $values;
    }

    protected function parseTextFile(string $file,string $delimiter = '')
    {
        $rows = $this->getFileLines($file);

        $data = [];

        foreach ($rows as $ix => $row)
        {
            // Ending row may be just empty.
            if (blank($row) === false)
            {
                $data[] = $this->parseTextRow($row, $ix, $delimiter);
            }
        }

        return $data;
    }

    /**
     * @param array $content
     * @return bool
     * @throws Exception\GatewayErrorException
     */
    protected function isAuthorized(array $content): bool
    {
        return Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE]);
    }

    /**
     * @param array $content
     * @return bool
     * @throws Exception\GatewayErrorException
     */
    protected function isRejected(array $content): bool
    {
        return Status::isDebitRejected($content[self::GATEWAY_RESPONSE_CODE]);
    }

    protected function parseFileAndCleanEntries(string $filePath): array
    {
        $entries = $this->parseFile($filePath);

        array_shift($entries);

        return $this->cleanParsedEntries($entries);
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        // Get payment
        $payment = $this->repo->payment->findOrFail($paymentId);

        $token = $payment->getGlobalOrLocalTokenEntity();

        $fileAccountNumber = $content[self::ACCOUNT_NUMBER];

        $tokenAccNo = $token->getAccountNumber();

        if ((in_array($payment->getGateway(), [Gateway::ENACH_NPCI_NETBANKING, Gateway::NACH_CITI], true) === false) or
            ($payment->getRecurringType() !== RecurringType::AUTO) or
            ($token === null) or
            ($tokenAccNo !== $fileAccountNumber))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_RECURRING_PAYMENT_NOT_FOUND,
                null,
                null,
                [
                    'payment_id'        => $payment->getId(),
                    'token_id'          => $token->getId(),
                    'payment'           => $payment->isCreated(),
                    'gateway'           => $payment->getGateway(),
                    'recurring_type'    => $payment->getRecurringType()
                ]);
        }

        return $payment;
    }

    protected function updateGatewayPaymentEntity($content, $payment)
    {
        if ($payment->getGateway() !== Gateway::ENACH_NPCI_NETBANKING)
        {
            return;
        }

        $gatewayPayment = $this->getGatewayPayment($payment->getId());

        $attrs = $this->getGatewayAttributes($content);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getGatewayPayment(string $paymentId)
    {
        return $this->repo
                    ->enach
                    ->findAuthorizedPaymentByPaymentId($paymentId);
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        return [
            EnachEntity::STATUS        => $parsedData[self::GATEWAY_RESPONSE_CODE],
            EnachEntity::ERROR_MESSAGE => $this->getGatewayErrorDesc($parsedData),
        ];
    }

    protected function  removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER]);
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCode::getDebitInternalErrorCode($content);
    }
    
    protected function getNRErrorCode(array $content)
    {
        return NRErrorCode::getNRErrorCodes($content);
    }

    protected function getGatewayErrorDesc(array $content): string
    {
        return ErrorCode::getDebitPublicErrorDescription($content[self::GATEWAY_ERROR_CODE]);
    }

    protected function getBankStatus($status): string
    {
        return Status::bankMappedStatus($status);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }
}
