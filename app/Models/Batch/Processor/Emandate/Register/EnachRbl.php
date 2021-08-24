<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use PhpOffice\PhpSpreadsheet\IOFactory;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Gateway\Enach\Rbl;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity;

class EnachRbl extends Base
{
    const GATEWAY = Gateway::ENACH_RBL;

    protected $gatewayPaymentMapping = [
        self::GATEWAY_REGISTRATION_STATUS => Entity::REGISTRATION_STATUS,
        self::GATEWAY_ERROR_CODE          => Entity::ERROR_CODE,
        self::GATEWAY_ERROR_DESCRIPTION   => Entity::ERROR_MESSAGE,
    ];

    protected function getDataFromRow(array $entry): array
    {
        $gatewayToken = $entry[Batch\Header::ENACH_REGISTER_UMRN];

        $gatewayTokenStatus = $entry[Batch\Header::ENACH_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus, $entry);

        return [
            self::GATEWAY_TOKEN       => $gatewayToken,
            self::TOKEN_STATUS        => $status,
            self::TOKEN_ERROR_CODE    => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID          => $entry[Batch\Header::ENACH_REGISTER_REF_1],
            // We are getting registration_status and error_code because we want to store
            // the actual registration status received in the file, in the gateway entity
            self::GATEWAY_REGISTRATION_STATUS => $gatewayTokenStatus,
            self::GATEWAY_ERROR_CODE          => $entry[Batch\Header::ENACH_REGISTER_RETURN_CODE],
            self::GATEWAY_ERROR_DESCRIPTION   => $entry[Batch\Header::ENACH_REGISTER_CODE_DESC],
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus, array $content): string
    {
        if (Rbl\Status::isRegistrationSuccess($gatewayTokenStatus, $content) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        if ($this->getTokenStatus($gatewayTokenStatus, $entry) === Token\RecurringStatus::CONFIRMED)
        {
            return null;
        }
        else
        {
            return Rbl\ErrorCodes::getRegistrationPublicErrorCode($entry);
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo->enach->findByPaymentIdAndAction($payment->getId(), Action::AUTHORIZE);
    }

    /**
     * {@override}
     * @param  string $filePath
     * @param int $numRowsToSkip
     * @return array
     */
    protected function parseExcelSheetsUsingPhpSpreadSheet($filePath, $numRowsToSkip = 0): array
    {
        $fileType = IOFactory::identify($filePath);
        $reader = IOFactory::createReader($fileType);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        // Override 1: Asserts that it has 2 sheets (For some reason it is expected). And it returns the 2nd sheet's content.
        assertTrue($spreadsheet->getSheetCount() === 2);
        $rows = $spreadsheet->setActiveSheetIndex(1)->toArray(null, false);
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

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Batch\Header::ENACH_REGISTER_ACNO]);
        unset($payloadEntry[Batch\Header::ENACH_REGISTER_NODAL_ACNO]);
    }
}
