<?php

namespace RZP\Models\OfflinePayment\HdfcEcollect;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\OfflinePayment;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $allowedModes = [
        OfflinePayment\Mode::CASH,
        OfflinePayment\Mode::CHEQUE,
        OfflinePayment\Mode::OTHCHEQUE,
        OfflinePayment\Mode::DD,
        OfflinePayment\Mode::OTHDD,
        OfflinePayment\Mode::BC,
        OfflinePayment\Mode::OTHBC,
        OfflinePayment\Mode::HFT,
        OfflinePayment\Mode::OTHER,
    ];

    protected static $allowedStatus = [
        OfflinePayment\Status::CAPTURED,
    ];

    protected static $hdfcEcollectRules = [
        OfflinePayment\Entity::CHALLAN_NUMBER        => 'required|string',
        OfflinePayment\Entity::AMOUNT                => 'required|string',
        OfflinePayment\Entity::MODE                  => 'required|string|custom',
        OfflinePayment\Entity::STATUS                => 'required|string|custom',
        OfflinePayment\Entity::DESCRIPTION           => 'nullable|string',
        OfflinePayment\Entity::BANK_REFERENCE_NUMBER => 'nullable|string',
        OfflinePayment\Entity::PID_REFERENCE_NUMBER  => 'nullable|string',
        OfflinePayment\Entity::PID_MICR_CODE         => 'nullable|string',
        OfflinePayment\Entity::PID_DATE              => 'nullable|string',
        OfflinePayment\Entity::PD_NAME               => 'nullable|string',
        OfflinePayment\Entity::PD_IFSC               => 'nullable|string|size:11',
        OfflinePayment\Entity::PD_BRANCH_CITY        => 'nullable|string',
        OfflinePayment\Entity::PAYMENT_TIMESTAMP     => 'required|epoch',
        OfflinePayment\Entity::ADDITIONAL_INFO       => 'nullable|array',
        OfflinePayment\Entity::CLIENT_CODE           => 'required|string',
    ];


    public function validateRequestPayload(array $requestPayload)
    {

        $requestPayload[OfflinePayment\Entity::PID_REFERENCE_NUMBER] = $requestPayload['payment_instrument_details']['reference_number'] ?? null;
        $requestPayload[OfflinePayment\Entity::PID_MICR_CODE] = $requestPayload['payment_instrument_details']['micr_code'] ?? null;
        $requestPayload[OfflinePayment\Entity::PID_DATE] = $requestPayload['payment_instrument_details']['date'] ?? null;
        $requestPayload[OfflinePayment\Entity::PD_NAME] = $requestPayload['payer_details']['name'] ?? null;
        $requestPayload[OfflinePayment\Entity::PD_IFSC] = $requestPayload['payer_details']['ifsc'] ?? null;
        $requestPayload[OfflinePayment\Entity::PD_BRANCH_CITY] = $requestPayload['payer_details']['branch_city'] ?? null;

        unset($requestPayload['payment_instrument_details']);
        unset($requestPayload['payer_details']);

        $this->validateInput('hdfcEcollect', $requestPayload);

    }

    protected function validateMode($attribute, $mode)
    {
        if (in_array($mode,self::$allowedModes , true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Mode: ' . $mode, null);
        }
    }

    protected function validateStatus($attribute, $status)
    {
        if (in_array($status,self::$allowedStatus , true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Status: ' . $status, null);
        }
    }
}
