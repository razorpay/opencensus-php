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

    public function generateAndSendCustomerOtpForMobile($phone)
    {
        $otpResponse = $this->generateOtp($phone);

        $this->sendOtpForMobile($otpResponse, $phone);
    }

    /**
     * @param string $receiver
     * @param array  $return
     *
     * @throws BadRequestException
     */
    protected function generateOtp(string $receiver): array
    {
        $context = Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $receiver;

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

        $this->redis->set($context, $otpStore, self::CUSTOMER_SUPPORT_OTP_TTL);

        return $otpStore;
    }

    protected function getPayloadForMobileOtp($phone, $otpResponse)
    {
        $payload = [
            'template'      => Constants::SMS_OTP_TEMPLATE_FOR_ACCOUNT_RECOVERY,
            'receiver'      => $phone,
            'source'        => Constants::OTP_CUSTOMER_SUPPORT_SOURCE,
            'params'        => [
                'otp'        => $otpResponse[Constants::OTP],
            ],
        ];

        return $payload;
    }

    protected function sendOtpForMobile($otpResponse, $phone)
    {
        $payload = $this->getPayloadForMobileOtp($phone, $otpResponse);

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
    public function verifyOtp($receiver, $otp): bool
    {
        $otpResponse = $this->redis->get($this->getRedisKey($receiver));

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
            throw new BadRequestValidationFailureException($errorCode,
                Constants::OTP
            );
        }
        else
        {
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

        if (Carbon::now()->getTimestamp() > $otpResponse['expires_at'])
        {
            return ErrorCode::BAD_REQUEST_OTP_EXPIRED;
        }

        $otpResponse['attempts'] = $otpResponse['attempts'] + 1;

        $this->redis->set($this->getRedisKey($receiver), $otpResponse, self::CUSTOMER_SUPPORT_OTP_TTL);

        if ($otp !== $otpResponse['otp'])
        {
            if ($this->isEnvironmentProduction() === false)
            {
                if (in_array($otp, Raven::MOCK_VALID_OTPS) === false)
                {
                    return ErrorCode::BAD_REQUEST_INCORRECT_OTP;
                }
            }
            else
            {
                return ErrorCode::BAD_REQUEST_INCORRECT_OTP;
            }
        }

        return '';
    }
}
