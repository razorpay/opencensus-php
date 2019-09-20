<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Sbi extends Base
{
    const GATEWAY   = Gateway::NETBANKING_SBI;

    protected $useSpreadSheetLibrary = false;

    protected $gatewayPaymentMapping = [
        self::TOKEN_STATUS     => NetbankingEntity::SI_STATUS,
        self::TOKEN_ERROR_CODE => NetbankingEntity::SI_MSG,
        self::GATEWAY_TOKEN    => NetbankingEntity::SI_TOKEN
    ];

    protected function getDataFromRow(array $entry): array
    {
        $paymentId = $entry[Batch\Header::SBI_EM_REGISTER_CUSTOMER_REF_NO];

        $gatewayToken = $entry[Batch\Header::SBI_EM_REGISTER_UMRN] ?? $entry[Batch\Header::SBI_EM_REGISTER_UMRN_REJECT_RILE];

        $gatewayTokenStatus = $entry[Batch\Header::SBI_EM_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus, $entry);

        return [
            self::TOKEN_STATUS     => $status,
            self::GATEWAY_TOKEN    => $gatewayToken,
            self::TOKEN_ERROR_CODE => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID       => $paymentId,
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus, array $content): string
    {
        if (Netbanking\Sbi\Emandate\Status::isRegistrationSuccess($gatewayTokenStatus, $content) === true)
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
            return Netbanking\Sbi\Emandate\ErrorCode::getRegisterErrorCode(
                                    $entry[Batch\Header::SBI_EM_REGISTER_STATUS_DESCRIPTION] ??
                                    $entry[Batch\Header::SBI_EM_REGISTER_REJECT_REASON]
                                  );
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo->netbanking->findByPaymentIdAndActionOrFail($payment['id'], Action::AUTHORIZE);
    }

    protected function validateEntries(array & $entries, array $input)
    {
        $this->getValidator()->validateEntries($entries, $input, $this->merchant);
    }

    protected function getValidator()
    {
        return new Netbanking\Sbi\Emandate\Validator($this->batch);
    }

    protected function getNumRowsToSkipExcelFile()
    {
        return 5;
    }


    protected function getStartRowExcelFiles()
    {
        return 6;
    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        if (isset($entries[0]) === false)
        {
            return;
        }

        if (array_key_exists(Batch\Header::SBI_EM_REGISTER_UMRN_REJECT_RILE, $entries[0]) === true)
        {
            $fileType = 'success';
        }
        else
        {
            $fileType = 'rejected';
        }

        $headers = $headers[$fileType];
    }

    protected function parseTextFile(string $file, string $delimiter = '~')
    {
        return parent::parseTextFile($file, ',');
    }
}
