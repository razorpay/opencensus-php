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
    public function generateAndSendCustomerOtp($email)
    {
        $otpResponse = $this->generateOtp($email);

        $this->sendOtp($otpResponse, $email);
    }

    /**
     * @param string $email
     * @param array $return
     * @throws BadRequestException
     */
    protected function generateOtp($email): array
    {
        $context = Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $email;

        $payload = [
            Constants::OTP_RECEIVER => $email,
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

    protected function sendOtp($otpResponse, $email)
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

    protected function getRedisKey($email): string
    {
        return Constants::OTP_CUSTOMER_SUPPORT_SOURCE . $email;
    }

    /**
     * @param string $email
     * @param string $email
     * @param void $return
     * @throws BadRequestValidationFailureException
     */
    public function verifyOtp($email, $otp): bool
    {
        $otpResponse = $this->redis->get($this->getRedisKey($email));

        $errorCode = '';

        if (empty($otpResponse) === false)
        {
            $errorCode = $this->getOtpErrorCode($email, $otp, $otpResponse);
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
                    'email' => $email
                ]
            );

            $this->redis->delete($this->getRedisKey($email));

            return true;
        }
    }

    protected function getOtpErrorCode($email, $otp, $otpResponse)
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

        $this->redis->set($this->getRedisKey($email), $otpResponse, self::CUSTOMER_SUPPORT_OTP_TTL);

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
