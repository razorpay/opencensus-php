<?php

namespace RZP\Modules\SecondFactorAuth;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;

/**
 * 2fa aunthetication via SMS OTP verification.
 */
class SmsOtpAuth implements BaseAuth
{

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    const RECEIVER        = 'receiver';
    const UNIQUE_ID       = 'unique_id';
    const ACTION          = 'action';
    const CONTEXT         = 'context';
    const SOURCE          = 'source';
    const OTP             = 'otp';

    const SEND_OTP_RULES = [
        self::UNIQUE_ID     => 'required|filled',
        self::ACTION        => 'required|filled',
        self::RECEIVER      => 'required|filled|max:15',
    ];

    const VERIFY_OTP_RULES = [
        self::UNIQUE_ID     => 'required|filled',
        self::ACTION        => 'required|filled',
        self::RECEIVER      => 'required|filled|max:15',
        self::OTP           => 'required|filled',
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * Within the input array pass the OTP for verification. Also,
     * pass an unqiue id and receiver number in input.
     *
     * NOTE: Please make sure you send the sms before calling
     *       this method.
     *
     * @param array $input
     *
     * @return  bool
     */
    public function is2faCredentialValid(array $input): bool
    {
        try
        {
            $this->verifyOtp($input);
            $success = true;
        }
        catch (BadRequestException $e)
        {
            $this->app['trace']->info(TraceCode::VERIFY_2FA_OTP_SMS_FOR_ACTION_FAILED, [
                'reason'    => 'input_validation_failed',
                'exception' => $e->getMessage(),
            ]);

            $success = false;
        }

        return $success;
    }

    /**
     * It synchronously calls Raven to send the OTP.
     *
     * @param   array   $input
     * @throws  BadRequestException
     */
    public function sendOtp(array $input)
    {
        try
        {
            (new JitValidator)->strict(true)->rules(self::SEND_OTP_RULES)->input($input)->validate();
        }
        catch(BaseException $e)
        {
            $this->app['trace']->info(TraceCode::SEND_2FA_OTP_SMS_FAILED, [
                'reason'    => 'input_validation_failed',
                'exception' => $e->getMessage(),
            ]);

            throw new LogicException($e->getPublicError());
        }

        $this->app['trace']->info(TraceCode::SEND_2FA_OTP_SMS_FOR_ACTION, compact('input'));

        $payload = $this->getBasePayloadForRaven($input);

        $payload['template'] = 'sms.user.' . $input[self::ACTION];

        try
        {
            $response = $this->app->raven->sendOtp($payload);
        }
        catch(BaseException $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SMS_OTP_FAILED, compact('input'));
        }

        if (isset($response['sms_id']) === false)
        {
            $this->app['trace']->info(TraceCode::SEND_2FA_OTP_SMS_FAILED, compact('input', 'response'));

            throw new BadRequestException(ErrorCode::BAD_REQUEST_SMS_OTP_FAILED);
        }
    }

    /**
     * It synchronously calls Raven to verify the OTP.
     *
     * @param   array   $input
     */
    protected function verifyOtp(array $input)
    {
        try
        {
            (new JitValidator)->strict(true)->rules(self::VERIFY_OTP_RULES)->input($input)->validate();
        }
        catch(BaseException $e)
        {
            $this->app['trace']->info(TraceCode::VERIFY_OTP_SMS_VALIDATION_FAILURE, [
                'reason'    => 'input_validation_failed',
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }

        $inputForLogging = $input;
        unset($inputForLogging[self::OTP]);

        $this->app['trace']->info(TraceCode::VERIFY_2FA_OTP_SMS_FOR_ACTION, compact('inputForLogging'));

        $payload = $this->getBasePayloadForRaven($input);

        $payload['otp'] = $input['otp'];

        $this->app->raven->verifyOtp($payload);
    }

    /**
     * Constructs the base payload for both sending otp and
     * verifying the otp.
     *
     * @param   array   $input
     * @return  array
     *
     */
    protected function getBasePayloadForRaven(array $input): array
    {
        return [
            self::CONTEXT  => sprintf('%s:%s:%s', '2fa', $input['unique_id'], $input[self::ACTION]),
            self::RECEIVER => $input['receiver'],
            self::SOURCE   => 'api.2fa.otp.auth',
        ];
    }
}
