<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Enach\Rbl;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Base\Entity as GatewayEntity;

class EnachRbl extends Base
{
    const GATEWAY = Gateway::ENACH_RBL;

    const REGISTRATION_STATUS = 'registration_status';
    const ERROR_CODE          = 'error_code';

    protected function getDataFromRow(array $entry): array
    {
        $gatewayToken = $entry[Batch\Header::ENACH_REGISTER_UMRN];

        $gatewayTokenStatus = $entry[Batch\Header::ENACH_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus);

        return [
            self::GATEWAY_TOKEN       => $gatewayToken,
            self::TOKEN_STATUS        => $status,
            self::ERROR_MESSAGE       => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID          => $entry[Batch\Header::ENACH_REGISTER_REF_1],
            // We are getting registration_status and error_code because we want to store
            // the actual registration status received in the file, in the gateway entity
            self::REGISTRATION_STATUS => $gatewayTokenStatus,
            self::ERROR_CODE          => $entry[Batch\Header::ENACH_REGISTER_RETURN_CODE],
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        if (Rbl\Status::isRegistrationSuccess($gatewayTokenStatus) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        if ($this->getTokenStatus($gatewayTokenStatus) === Token\RecurringStatus::CONFIRMED)
        {
            return null;
        }
        else
        {
            return $entry[Batch\Header::ENACH_REGISTER_CODE_DESC] ?? 'FAILED';
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo
                    ->enach
                    ->findAuthorizedPaymentByPaymentId($payment->getId());
    }

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
}
