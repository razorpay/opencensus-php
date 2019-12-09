<?php

namespace RZP\Models\PayoutLink;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\PayoutLink\Clients\Contact as ContactClient;

class Core extends Base\Core
{
    const CONTEXT                 = 'context';
    const LONG_URL_FORMAT         = '%s/payout-links/%s/view';
    const OTP                     = 'otp';
    const PARAMS                  = 'params';
    const CUSTOMER_NAME           = 'customer_name';
    const TEMPLATE                = 'template';
    const SOURCE                  = 'source';
    const RECEIVER                = 'receiver';
    const SMS_TEMPLATE            = 'sms.payout_link.otp';
    const API_PAYOUT_LINK_SRC_STR = 'api.payout-link';

    protected $elfin;

    protected $raven;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];

        $this->raven = $this->app['raven'];
    }

    public function create(array $input): Entity
    {
        array_pull($input, 'XDEBUG_SESSION_START');

        $this->trace->info(
            TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::COMPOSITE_CREATE_RULE, $input);

        $contact = array_pull($input, 'contact');

        $contact = (new ContactClient())->processContact($contact, $this->merchant);

        $payoutLink = (new Entity)->build($input);

        // Doing this because we need the Id for generating short URL
        $payoutLink->generateId();

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->contact()->associate($contact);

        // todo: pl , unsure how to get the user entity from the request in core
        // $payoutLink->user()->associate($this->app->basicauth->getUser());

        $payoutLink->setStatus(Status::ISSUED);

        $payoutLink->saveOrFail();

        return $payoutLink;
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_GENERATE,
            [
                'payout_link_id' => $payoutLinkId
            ]
        );

        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $contact = $payoutLink->contact;

        $otp = $this->generateOtp($contact, $payoutLinkId);

        $this->sendCustomerOtpSms($contact, $otp);

        $this->sendCustomerOtpEmail($contact, $otp);

        $uniqueToken = $this->generateUniqueRequestToken($payoutLinkId);

        return [
            'token' => $uniqueToken
        ];
    }

    /**
     * Timestamp + payout_link_id
     */
    protected function generateUniqueRequestToken($payoutLinkId): string
    {
        $timestamp =  Carbon::now(Timezone::IST)->getTimestamp();

        return $payoutLinkId . '.' . $timestamp;
    }

    /**
     * Returns true, if atkleast one delivery worked. else returns false
     * @param ContactEntity $contact
     * @return bool
     */
    protected function deliverOtp(ContactEntity $contact, string $otp): bool
    {
        # get the phone-number
        # send SMS
    }

    protected function sendCustomerOtpEmail(ContactEntity $contactEntity, string $otp)
    {

    }

    protected function sendCustomerOtpSms(ContactEntity $contactEntity, string $otp)
    {
        $payload = [
            self::PARAMS   => [
                self::CUSTOMER_NAME => $contactEntity->getName(),
                self::OTP           => $otp
            ],
            self::TEMPLATE => self::SMS_TEMPLATE,
            self::SOURCE   => self::API_PAYOUT_LINK_SRC_STR,
            self::RECEIVER => $contactEntity->getContact()
        ];

        try
        {
            $this->raven->sendSms($payload);
        }
        catch(\Exception $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_OTP_SMS_FAILED,
                null,
                $payload
            );
        }
    }

    protected function generateOtp(ContactEntity $contact, string $payoutLinkId)
    {
        $phoneNumber = $contact->getContact();

        if ($phoneNumber === null)
        {
            #todo, pl how will we handle OTP creation when we do not have a phone-number
        }
        else
        {
            $payload = [
                self::RECEIVER => $phoneNumber,
                self::CONTEXT  => $payoutLinkId,
                self::SOURCE   => 'api.pout_lnk'
            ];
        }

        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_REQUEST,
            $payload
        );

        $response = $this->raven->generateOtp($payload);

        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_RESPONSE,
            $response
        );
        if (key_exists(self::OTP, $response) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED,
                                          [
                                              'request'  => $payload,
                                              'response' => $response,
                                          ]
            );
        }

        return $response[self::OTP];
    }

    protected function generateAndSetShortUrl(Entity &$payoutLink)
    {
        $targetUrl = sprintf(self::LONG_URL_FORMAT,
                             $this->config['url.api.production'],
                             $payoutLink->getPublicId());

        $params = [
            'metadata'       => [
                'mode'   => $this->mode,
                'entity' => $payoutLink->getEntity(),
                'id'     => $payoutLink->getPublicId(),
            ]
        ];

        try
        {
            $shortUrl = $this->elfin->shorten($targetUrl, $params, false);
        }
        catch (\Exception $e)
        {
            // in case of a problem with elfin, the short url will be same as the target url.
            $shortUrl = $targetUrl;

            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYOUT_LINK_SHORT_URL_GENERATION_FAILED,
                [
                    'message'        => $e->getMessage(),
                    'payout_link_id' => $payoutLink->getId(),
                ]
            );
        }

        $payoutLink->setShortUrl($shortUrl);
    }
}
