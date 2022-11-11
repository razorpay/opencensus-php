<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Environment;
use RZP\Mail\Support\CustomerSupportTicketOtp;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Services\Raven;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $raven;

    protected $redis;

    const CUSTOMER_SUPPORT_OTP_TTL = 5 * 60; // 5 minutes ( in seconds)

    const MAX_OTP_ATTEMPTS = 3;

    public function __construct()
    {
        parent::__construct();

        $this->raven = $this->app['raven'];

        $this->redis = $this->app['cache'];
    }

    public function create(array $input, string $merchantId, $allowMultiple = false): Entity
    {
        $params = ['type' => $input[Entity::TYPE]];


        if ($allowMultiple === false)
        {
            $tickets = $this->repo->merchant_freshdesk_tickets->fetch($params, $merchantId);

            if ($tickets->count() !== 0)
            {
                throw new BadRequestValidationFailureException(
                    ErrorCode::FRESHDESK_TICKET_ALREADY_EXISTS,
                    null,
                    [
                        'merchant_id' => $merchantId,
                        'type' => $input[Entity::TYPE]
                    ]
                );
            }
        }

        $ticketEntity = new Entity;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $ticketEntity->merchant()->associate($merchant);

        $input[Entity::MERCHANT_ID] = $merchantId;

        $ticketEntity->build($input);

        $this->repo->saveOrFail($ticketEntity);

        return $ticketEntity;
    }

    /**
     * @param string $email
     * @param string $return
     * @throws BadRequestException
     */
    public function generateAndSendCustomerOtpForEmail($email)
    {
        $otpResponse = $this->generateOtp($email);

        $this->sendOtpForEmail($otpResponse, $email);
    }

    public function generateAndSendCustomerOtpForMobile($phone, $action = null)
    {
        $otpResponse = $this->generateOtp($phone, $action);

        $this->sendOtpForMobile($otpResponse, $phone, $action);
    }

    /**
     * @param string $receiver
     * @param array  $return
     *
     * @throws BadRequestException
     */
    protected function generateOtp(string $receiver, $action = null): array
    {
        $context = (Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $receiver). (empty($action) === true ? '' : '_' . $action);

        $payload = [
            Constants::OTP_RECEIVER => $receiver,
            Constants::OTP_CONTEXT  => $context,
            Constants::OTP_SOURCE   => Constants::OTP_CUSTOMER_SUPPORT_SOURCE
        ];

        $this->trace->info(
            TraceCode::FRESHDESK_SUPPORT_CUSTOMER_OTP_REQUEST,
            $payload
        );

        $response = $this->raven->generateOtp($payload);

        if (key_exists(Constants::OTP, $response) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED,
                null,
                [
                    'request'  => $payload,
                    'response' => $response,
                ]
            );
        }

        $otp = array_pull($response, Constants::OTP);

        $expires_at = Carbon::now()->addSeconds(self::CUSTOMER_SUPPORT_OTP_TTL)->timestamp;

        $otpStore = [
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => $expires_at,
        ];

        $this->redis->set($context, ['attempts' => $otpStore['attempts']], self::CUSTOMER_SUPPORT_OTP_TTL);

        return $otpStore;
    }

    protected function getPayloadForMobileOtp($phone, $otpResponse, $action = null)
    {
        if (empty($action))
        {
            $action  = Constants::ACCOUNT_RECOVERY;
        }
        $payload = [
            'template' => Constants::ACTION_VS_TEMPLATE_FOR_OTP[$action],
            'receiver' => $phone,
            'source'   => Constants::OTP_CUSTOMER_SUPPORT_SOURCE,
            'params'   => [
                'otp' => $otpResponse[Constants::OTP],
            ],
        ];

        return $payload;
    }

    protected function sendOtpForMobile($otpResponse, $phone, $action = null)
    {
        $payload = $this->getPayloadForMobileOtp($phone, $otpResponse, $action);

        try
        {
            $this->app['raven']->sendSms($payload, true);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Logger::CRITICAL, TraceCode::FRESHDESK_SUPPORT_OTP_SMS_FAILED);
        }
    }

    protected function sendOtpForEmail($otpResponse, $email)
    {
        $customerSupportTicketOtp = new CustomerSupportTicketOtp($email, $otpResponse['otp']);

        try
        {
            Mail::queue($customerSupportTicketOtp);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Logger::CRITICAL, TraceCode::FRESHDESK_SUPPORT_OTP_EMAIL_FAILED);
        }
    }

    protected function getRedisKey($receiver): string
    {
        return Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $receiver;
    }

    /**
     * @param string $receiver
     * @param string $otp
     * @param void   $return
     *
     * @throws BadRequestValidationFailureException
     */
    public function verifyOtp($receiver, $otp, $action = null): bool
    {
        if (empty($action) === true)
        {
            $otpResponse = $this->redis->get($this->getRedisKey($receiver));
        }
        else
        {
            $otpResponse = $this->redis->get($this->getRedisKey($receiver.'_'.$action));
        }

        $errorCode = '';

        if (empty($otpResponse) === false)
        {
            $errorCode = $this->getOtpErrorCode($receiver, $otp, $otpResponse);
        }
        else
        {
            $errorCode = ErrorCode::BAD_REQUEST_INCORRECT_OTP;
        }

        if ($errorCode !== '')
        {
            throw new BadRequestValidationFailureException($errorCode, Constants::OTP);
        }
        else
        {
            $context = (Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $receiver). (empty($action) === true ? '' : '_' . $action);

            $payload = [
                Constants::OTP_RECEIVER => $receiver,
                Constants::OTP_CONTEXT  => $context,
                Constants::OTP_SOURCE   => Constants::OTP_CUSTOMER_SUPPORT_SOURCE,
                Constants::OTP          => $otp
            ];

            try
            {
                $this->raven->verifyOtp($payload);
            }
            catch (\Throwable $e)
            {
                throw new BadRequestValidationFailureException($e->getMessage(), Constants::OTP);
            }

            $this->trace->info(
                TraceCode::FRESHDESK_SUPPORT_CUSTOMER_OTP_VALIDATED,
                [
                    'receiver' => $receiver
                ]
            );

            $this->redis->delete($this->getRedisKey($receiver));

            return true;
        }
    }

    protected function getOtpErrorCode($receiver, $otp, $otpResponse)
    {
        if ($otpResponse['attempts'] > self::MAX_OTP_ATTEMPTS)
        {
            return ErrorCode::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED;
        }

        $otpResponse['attempts'] = $otpResponse['attempts'] + 1;

        $this->redis->set($this->getRedisKey($receiver), $otpResponse, self::CUSTOMER_SUPPORT_OTP_TTL);

        return '';
    }
}
