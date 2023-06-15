<?php

namespace RZP\Models\Customer;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Raven extends Base\Core
{
    protected $raven = null;

    protected $sns = null;

    public function __construct()
    {
        parent::__construct();

        $this->raven = $this->app['raven'];

        $this->sns = $this->app['sns'];
    }

    public function sendOtp($input, $merchant)
    {
        $success = true;

        $request = $this->getRavenSendOtpRequestInput($input, $merchant);

        try
        {
            $this->sns->publish(json_encode($request));
        }
        catch (\Throwable $e)
        {
            $traceData = array(
                'error' => $e->getMessage(),
                'request' => $request,
            );

            $this->trace->error(TraceCode::RAVEN_ASYNC_REQUEST_FAILED, $traceData);

            $success = true;

            $response = $this->raven->sendOtp($request);

            if (isset($response['sms_id']) === false)
            {
                $success = false;
            }
        }

        return ['success' => $success];
    }

    public function verifyOtp($input, $merchant)
    {
        $request = $this->getRavenVerifyOtpRequestInput($input, $merchant);

        $response = $this->raven->verifyOtp($request);

        return $response;
    }

    public function updateSmsStatus($gateway, $input)
    {
        $this->trace->info(TraceCode::RAVEN_CALLBACK_REQUEST,
            [
                'gateway' => $gateway,
                'input'   => $input,
            ]);

        $result = $this->raven->smsCallback($gateway, $input);

        return $result;
    }

    protected function getRavenSendOtpRequestInput($input, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $input['contact'],
            'source' => 'api',
            'params' => [
                'merchant_name' => strtoupper(mb_substr($merchant->getBillingLabel(), 0, 19, 'UTF-8'))
            ],
            'stork' => [
                'owner_id' => $merchant->getId(),
            ],
        );

        if (isset($input['otp_reason']) === true and
            $this->validateOtpReason($input['otp_reason']) === true)
        {
          $request['template'] = $this->getTemplateByOtpReason($input['otp_reason']);
        }

        if (isset($input['sms_hash']) === true)
        {
            $request['params']['sms_hash'] = $input['sms_hash'];
        }

        if (isset($input['merchant_domain']) === true)
        {
            $request['params']['merchant_domain'] = $input['merchant_domain'];
        }

        if (empty($input['template']) === false)
        {
            $request['template'] = $input['template'];
        }

        if ((isset($input['method'])) and
            (($input['method'] === Method::CARDLESS_EMI) or
            ($input['method'] === Method::PAYLATER)))
        {

            $request['template'] = 'sms.otp_cardless';

            $request['params']['provider'] = $input['provider'] === Gateway::GETSIMPL ? 'Simpl' : $input['provider'];
        }

        $orgId = $merchant->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $request['stork']['context']['org_id'] = $orgId;
        }

        return $request;
    }

    protected function getRavenVerifyOtpRequestInput($input, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $input['contact'],
            'source' => 'api',
            'otp' => $input['otp']
        );

        return $request;
    }

    private function getTemplateByOtpReason(string $otpReason): string
    {
        switch ($otpReason)
        {
            CASE 'verify_coupon':
                return 'sms.checkout.verify_coupon_otp';

            CASE 'verify_coupon_v2':
                return 'sms.checkout.verify_coupon_otp_v2';

            CASE 'mandatory_login':
                return 'sms.checkout.mandatory_login_otp';

            CASE 'mandatory_login_v2':
                return 'sms.checkout.mandatory_login_otp_v2';

            CASE 'access_address':
                return 'sms.checkout.access_address_otp';

            CASE 'access_address_v2':
                return 'sms.checkout.access_address_otp_v2';

            CASE 'save_address':
                return 'sms.checkout.save_address_otp';

            CASE 'save_address_v2':
                return 'sms.checkout.save_address_otp_v2';

            CASE 'access_card':
                return 'sms.checkout.access_card_otp';

            CASE 'access_card_v3':
                return 'sms.checkout.access_card_otp_v3';

            CASE 'save_card':
                return 'sms.checkout.save_card_otp';

            CASE 'save_card_v3':
                return 'sms.checkout.save_card_otp_v3';

            CASE 'support_page_login':
                return 'sms.support.login_otp';

            CASE 'save_card_v2':
                return 'sms.checkout.save_card_otp_v4';

            CASE 'access_card_v2':
                return 'sms.checkout.access_card_otp_v4';

            case 'mweb_save_card':
                return 'sms.checkout.save_card_otp_v6';

            case 'mweb_access_card':
                return 'sms.checkout.access_saved_card_otp_v6';

            default:
                return 'sms.otp';
        }
    }

    private function validateOtpReason($otpReason)
    {
        return in_array($otpReason, [
            'verify_coupon',
            'verify_coupon_v2',
            'mandatory_login',
            'mandatory_login_v2',
            'access_address',
            'access_address_v2',
            'save_address',
            'save_address_v2',
            'access_card',
            'access_card_v3',
            'save_card',
            'save_card_v3',
            'access_card_v2',
            'save_card_v2',
            'support_page_login',
            'mweb_save_card',
            'mweb_access_card',
        ]);
    }
}
