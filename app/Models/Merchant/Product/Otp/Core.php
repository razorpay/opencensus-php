<?php

namespace RZP\Models\Merchant\Product\Otp;

use Razorpay\Trace\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Base;
use RZP\Models\Merchant\Product\Util;

class Core extends Base\Core
{
    const MERCHANT_OTP_CREATE_MUTEX_PREFIX = 'api_merchant_otp_create_';

    public function fetchOtpVerificationLog(Merchant\Entity $merchant): array
    {
        $merchantId      = $merchant->getMerchantId();
        $merchant_detail = $this->merchant->merchantDetail;

        $otpLog   = $this->repo->merchant_otp_verification_logs->findMerchantOtpLogByMidAndContactNumber($merchantId, $merchant_detail->getContactMobile());
        $response = [];
        if (empty($otpLog) == false)
        {
            $response = $this->formatResponse($otpLog->toArray());
        }

        return $response;
    }

    public function saveOtpVerificationLog(Merchant\Entity $merchant, array $input): array
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::STORE_OTP_VERIFICATION_LOG, [
            'merchantId' => $merchantId,
            'input'      => $input
        ]);

        $otpHandler = (new Util\OtpRequestHandler());

        $formattedContactNumber = $otpHandler->formatContactNumber($input[Util\Constants::OTP][Util\Constants::CONTACT_MOBILE], $merchant);

        $otpLog = $this->repo->merchant_otp_verification_logs->findMerchantOtpLogByMidAndContactNumber($merchantId, $formattedContactNumber);

        $input[Util\Constants::OTP][Util\Constants::CONTACT_MOBILE] = $formattedContactNumber;

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $merchant, $input, $otpLog) {

            if (empty($otpLog) === false)
            {
                $this->trace->info(TraceCode::MERCHANT_OTP_VERIFICATION_LOG_ALREADY_EXIST,
                                   ['otp' => $otpLog]);
                $otpLog->edit($input[Util\Constants::OTP]);
                $this->repo->merchant_otp_verification_logs->saveOrFail($otpLog);
            }
            else
            {
                $input[Util\Constants::OTP][Entity::MERCHANT_ID] = $merchant->getId();
                $otpLog                                          = $this->createOtp($merchant, $input);
            }

            return $this->formatResponse($otpLog->toArray());
        });
    }

    public function hasPendingOtpLog(string $merchantId, string $contactNumber): bool
    {

        $exists = $this->repo->merchant_otp_verification_logs->isOtpVerificationLogExist($merchantId, $contactNumber);

        return ($exists === false);
    }

    private function createOtp(Merchant\Entity $merchant, array $input)
    {

        $otpLog = new Entity;

        $otpLog->generateId();

        $merchantDetail = $merchant->merchantDetail;

        $otpLog->build($input[Util\Constants::OTP]);

        $otpLog->merchantDetail()->associate($merchantDetail);

        $this->repo->merchant_otp_verification_logs->saveOrFail($otpLog);

        return $otpLog;
    }

    private function formatResponse(array $otp): array
    {
        $requiredKey = array(Entity::CONTACT_MOBILE, Entity::EXTERNAL_REFERENCE_NUMBER, Entity::OTP_VERIFICATION_TIMESTAMP, Entity::OTP_SUBMISSION_TIMESTAMP);

        return array_filter(
            $otp,
            function($key) use ($requiredKey) {
                return in_array($key, $requiredKey);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

}
