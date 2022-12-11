<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use RZP\Base;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\FileStore;
use RZP\Models\Payment\Method;
use Illuminate\Http\UploadedFile;

class Validator extends Base\Validator
{
    protected static $notifyRules = [
        Constants::FILE     => 'required|file|custom',
    ];

    protected static $rowRules = [
        Constants::INPUT_KEY_ARN                     => 'required_without:' . Constants::INPUT_KEY_PAYMENT_ID,
        Constants::INPUT_KEY_TYPE                    => 'sometimes',
        Constants::INPUT_KEY_PAYMENT_ID              => 'required_without:' . Constants::INPUT_KEY_ARN,
        Constants::INPUT_KEY_REPORTED_BY             => 'required|in:CyberSafe,CyberCell,Visa,MasterCard,Issuer,Network',
        Constants::INPUT_KEY_PAYMENT_METHOD          => 'required_with:' . Constants::INPUT_KEY_ARN . '|custom',
        Constants::INPUT_KEY_REPORTED_TO_RAZORPAY_AT => 'required|custom',
    ];

    protected function validateFile(string $attribute, UploadedFile $file)
    {
        if ($file->getClientOriginalExtension() !== FileStore\Format::XLSX)
        {
            $message = 'Invalid file type (' . $file->getClientOriginalExtension() . '). Allowed extensions: (' . FileStore\Format::XLSX . ')';

            throw new Exception\BadRequestValidationFailureException($message);
        }
    }

    protected function validatePaymentMethod($attribute, $method)
    {
        if (empty($method) === true)
        {
            return;
        }

        if (Method::isValid($method) === false)
        {
            $message = 'Invalid payment method given: ' . $method;

            throw new Exception\BadRequestValidationFailureException($message);
        }
    }

    protected function validateReportedToRazorpayAt($attribute, $reportedDate)
    {
        $date = str_replace('/', '-', $reportedDate);

        $date = date('Y-m-d', strtotime($date));

        $currentDate = Carbon::today(Timezone::IST)->format('Y-m-d');

        if ($date > $currentDate) {

            $message = 'Future Date should not be given: ' . $date;

            throw new Exception\BadRequestValidationFailureException($message);
        }


    }
}
