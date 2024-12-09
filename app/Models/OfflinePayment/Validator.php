<?php

namespace RZP\Models\OfflinePayment;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\OfflinePayment;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    /**
     * @var Entity
     */
    protected $entity;

    const IFSC_LENGTH = 11;


    protected static $createRules = [
        Entity::CHALLAN_NUMBER              => 'required|string',
        Entity::AMOUNT                      => 'required|string',
        Entity::MODE                        => 'required|string',
        Entity::STATUS                      => 'required|in:captured,failed,pending',
        Entity::DESCRIPTION                 => 'nullable|string',
        Entity::BANK_REFERENCE_NUMBER       => 'nullable|string',
        Entity::PAYMENT_INSTRUMENT_DETAILS  => 'sometimes|notes',
        Entity::PAYER_DETAILS               => 'sometimes|notes',
        Entity::PAYMENT_TIMESTAMP           => 'required|epoch',
        Entity::ADDITIONAL_INFO             => 'nullable|array',
        Entity::CLIENT_CODE                 => 'required|string',
        Entity::CURRENCY                    => 'required|in:INR',
        Entity::SOURCE                      => 'sometimes|string|in:file,callback',
    ];

    public static $OfflinePaymentRules = [
        'challan_no'                        => 'required|alpha_num|between:5,40',
        'amount'                            => 'required|int',
        'mode'                              => 'required|string',
        'status'                            => 'required|string',
        'description'                       => 'sometimes|string',
        'bank_reference_number'             => 'sometimes|string',
        'payment_instrument_details'        => 'sometimes|notes',
        'payer_details'                     => 'sometimes|notes',
        'payment_date'                      => 'required|string',
        'payment_time'                      => 'required|string',
        'additional_info'                   => 'sometimes|string',
        'client_code'                       => 'sometimes|string',
        'source'                            => 'sometimes|string|in:file,callback',
    ];



    protected static $allowedModesForHdfc = [
        OfflinePayment\Mode::CASH,
        OfflinePayment\Mode::CHEQUE,
        OfflinePayment\Mode::DD,
        OfflinePayment\Mode::BC,
        OfflinePayment\Mode::HFT,
    ];

    protected function validateMode($attribute, $mode)
    {
        if (Mode::isValid($mode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid offline mode',
                $attribute,
                $mode);
        }
    }

    public function validateOfflineStatus(string $status, string $inputStatus)
    {
        switch ($status) {
            case 'paid':
            case 'processed':
                $status = OfflinePayment\Status::CAPTURED;
                break;

            case 'return':
            case 'failed':
                $status = OfflinePayment\Status::FAILED;
                break;

            case 'pending':
                $status = OfflinePayment\Status::PENDING;
                break;

            default:
                throw new BadRequestValidationFailureException('invalid status: ' . $inputStatus, null, null);
        }
        return $status;
    }

    public function validateOfflineMode(string $mode, string $inputMode)
    {
        switch ($mode) {
            case 'cash':
                $mode = Mode::CASH;
                break;

            case 'cheque':
            case 'hcq':
                $mode = Mode::CHEQUE;
                break;

            case 'dd':
            case 'hdd':
                $mode = Mode::DD;
                break;

            case 'other bank cheque':
            case 'othcq':
                $mode = Mode::OTHCHEQUE;
                break;

            case 'other bank dd':
            case 'othdd':
                $mode = Mode::OTHDD;
                break;

            case 'bc':
            case 'hbc':
                $mode = Mode::BC;
                break;

            case 'other bank bc':
            case 'othbc':
                $mode = Mode::OTHBC;
                break;

            case 'hft':
                $mode = Mode::HFT;
                break;

            case 'other':
                $mode = Mode::OTHER;
                break;

            default:
                throw new BadRequestValidationFailureException('invalid mode: ' . $inputMode, null, null);
        }
        return $mode;
    }

    public function validateModeForHdfc($mode)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        try
        {
            if (in_array($mode,self::$allowedModesForHdfc , true) === false)
            {
                throw new BadRequestValidationFailureException('Invalid Mode: ' . $mode, null);
            }
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $this->trace->traceException($ex);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,$ex,$ex->getMessage()
            );
        }
    }

}
