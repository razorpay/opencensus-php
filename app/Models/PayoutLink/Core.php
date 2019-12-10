<?php

namespace RZP\Models\PayoutLink;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\CustomerOtp;
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
    const API_POUT_LNK_SCR        = 'api.pout_l';
    const OK                      = 'OK';
    const PAYOUT_LINK_ID          = 'payout_link_id';

    const TOKEN_EXPIRE_IN_SECONDS = 900; //15 minutes

    protected $elfin;

    protected $raven;

    protected $redis;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];

        $this->raven = $this->app['raven'];

        $this->redis = $this->app['redis']->connection();
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
                self::PAYOUT_LINK_ID => $payoutLinkId
            ]
        );

        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $contact = $payoutLink->contact;

        $otp = $this->generateOtp($contact, $payoutLinkId);

        $this->deliverOtp($payoutLink, $contact, $otp);

        return self::OK;
    }

    public function verifyCustomerOtp($payoutLinkId, $otp)
    {
        $payoutLink = $this->repo
            ->payout_link
            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $contact = $payoutLink->contact;

        $receiver = $this->getReceiver($contact, $payoutLinkId);

        $payload = [
            self::RECEIVER => $receiver,
            self::CONTEXT  => $payoutLinkId,
            self::SOURCE   => self::API_POUT_LNK_SCR,
            self::OTP      => $otp
        ];

        // todo, pl check if its ok to log OTP
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_VERIFY,
            $payload
        );

        $this->raven->verifyOtp($payload);

        $uniqueToken = $this->generateUniqueRequestToken($payoutLinkId);

        $this->redis->set($uniqueToken, '', 'ex', self::TOKEN_EXPIRE_IN_SECONDS);

        return [
            'token' => $uniqueToken
        ];
    }

    /**
     * This will be stored in redis after OTP verification
     * and will be used in subsequent api calls
     */
    protected function generateUniqueRequestToken($payoutLinkId): string
    {
        $timestamp =  Carbon::now(Timezone::IST)->getTimestamp();

        return $payoutLinkId . '.' . $timestamp;
    }

    /**
     * Returns true, if atkleast one delivery worked. else returns false
     * @param Entity $payoutLink
     * @param ContactEntity $contact
     * @param string $otp
     * @return void
     * @throws BadRequestException
     */
    protected function deliverOtp(Entity $payoutLink, ContactEntity $contact, string $otp)
    {
        $successfulChannelPushCount = 0;

        $phoneNumber = $contact->getContact();

        if ($phoneNumber !== null)
        {
            $payload = $this->getSmspayload($contact, $otp);

            try
            {
                $this->raven->sendSms($payload);

                $successfulChannelPushCount++;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::PAYOUT_LINK_CUSTOMER_OTP_SMS_FAILED,
                                             [
                                                 ContactEntity::ID      => $contact->getPublicId(),
                                                 ContactEntity::NAME    => $contact->getName(),
                                                 ContactEntity::CONTACT => $contact->getContact(),
                                                 self::PAYOUT_LINK_ID   => $payoutLink->getPublicId()
                                             ]);
            }
        }

        $email = $contact->getEmail();

        if ($email !== null)
        {
            $customerEmailOtp = new CustomerOtp($email,
                                                $otp,
                                                $this->merchant->getName(),
                                                $payoutLink->getDescription());
            try
            {
                Mail::queue($customerEmailOtp);

                $successfulChannelPushCount++;
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::PAYOUT_LINK_CUSTOMER_OTP_MAIL_FAILED,
                                             [
                                                 ContactEntity::ID    => $contact->getPublicId(),
                                                 ContactEntity::NAME  => $contact->getName(),
                                                 ContactEntity::EMAIL => $contact->getEmail(),
                                                 self::PAYOUT_LINK_ID => $payoutLink->getPublicId()
                                             ]);
            }

        }

        if ($successfulChannelPushCount === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
                null,
                [
                    ContactEntity::ID      => $contact->getPublicId(),
                    ContactEntity::NAME    => $contact->getName(),
                    ContactEntity::CONTACT => $contact->getContact(),
                    ContactEntity::EMAIL   => $contact->getEmail(),
                    self::PAYOUT_LINK_ID   => $payoutLink->getPublicId()
                ]
            );
        }
    }

    protected function getSmsPayload(ContactEntity $contactEntity, string $otp)
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

        return $payload;
    }

    protected function generateOtp(ContactEntity $contact, string $payoutLinkId)
    {
        $receiver = $this->getReceiver($contact, $payoutLinkId);

        $payload = [
            self::RECEIVER => $receiver,
            self::CONTEXT  => $payoutLinkId,
            self::SOURCE   => self::API_POUT_LNK_SCR
        ];

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
                    'message'            => $e->getMessage(),
                    self::PAYOUT_LINK_ID => $payoutLink->getId(),
                ]
            );
        }

        $payoutLink->setShortUrl($shortUrl);
    }

    /**
     * @param ContactEntity $contact
     * @param string $payoutLinkId
     * @return mixed|null
     * @throws BadRequestException
     */
    protected function getReceiver(ContactEntity $contact, string $payoutLinkId): string
    {
        $phoneNumber = $contact->getContact();

        $email = $contact->getEmail();

        if (($phoneNumber === null) and ($email === null))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL,
                                          [
                                              ContactEntity::ID      => $contact->getPublicId(),
                                              ContactEntity::NAME    => $contact->getName(),
                                              ContactEntity::CONTACT => $contact->getContact(),
                                              ContactEntity::EMAIL   => $contact->getEmail(),
                                              self::PAYOUT_LINK_ID   => $payoutLinkId
                                          ]
            );
        }

        if ($phoneNumber !== null)
        {
            $receiver = $phoneNumber;
        }
        else
        {
            $receiver = $email;
        }

        return $receiver;
    }
}
