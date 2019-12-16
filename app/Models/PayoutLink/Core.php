<?php

namespace RZP\Models\PayoutLink;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\PayoutLink\External\Contact as ContactClient;

class Core extends Base\Core
{
    const LONG_URL_FORMAT         = '%s/payout-links/%s/view';
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

    const MESSAGE = 'message';
    const SUCCESS = 'success';
    const ACTIVE  = 'active';

    protected $elfin;

    protected $raven;

    protected $tokenService;

    public function __construct()
    {
        parent::__construct();
        $this->elfin = $this->app['elfin'];

        $this->raven = $this->app['raven'];

        $this->tokenService = new TokenService();
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::GET_FUND_ACCOUNT_BY_CONTACT_RULE, $input);

        (new TokenService())->verify($input[Entity::TOKEN]);

        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $fundAccounts = $payoutLink->contact->fundAccounts;

        return $this->filterOutInActiveFundAccounts($fundAccounts);
    }

    // todo, pl this looks like a  repo funtionality, but unsure how to push it there. #reviewer ?
    public function filterOutInActiveFundAccounts(PublicCollection $fundAccounts)
    {
        return $fundAccounts->where(self::ACTIVE, '=' , '1');
    }

    public function cancel(string $payoutLinkId): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CANCEL_REQUEST,
            [
                'id' => $payoutLinkId
            ]
        );

        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);
        // If already cancelled, then return the entity without any change. Makes this call idempotent.
        if ($payoutLink->getStatus() === Status::CANCELLED)
        {
            return $payoutLink;
        }

        $payoutLink->setStatus(Status::CANCELLED);

        $this->repo->saveOrFail($payoutLink);

        return $payoutLink;
    }

    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::COMPOSITE_CREATE_RULE, $input);

        $contactDetails = array_pull($input, 'contact');

        $contact = (new ContactClient())->processContact($contactDetails, $this->merchant);

        $payoutLink = (new Entity)->build($input);

        // Doing this because we need the Id for generating short URL
        $payoutLink->generateId();

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->contact()->associate($contact);

        // todo: pl , unsure how to get the user entity from the request in core
        // $payoutLink->user()->associate($this->app->basicauth->getUser());

        $payoutLink->setStatus(Status::ISSUED);

        $this->repo->saveOrFail($payoutLink);

        return $payoutLink;
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_GENERATE,
            [
                self::PAYOUT_LINK_ID => $payoutLinkId
            ]
        );

        (new Entity())->getValidator()
                      ->validateInput(Validator::GENERATE_OTP, $input);

        // extra context param, that the F.E. can pass, in case they want to
        // force generation of a new OTP
        $context = array_pull($input, Entity::CONTEXT);

        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $contact = $payoutLink->contact;

        $otp = $this->generateOtp($contact, $payoutLinkId, $context);

        $this->deliverOtp($payoutLink, $contact, $otp);

        return [self::SUCCESS => self::OK];
    }

    public function verifyCustomerOtp($payoutLinkId, $input): array
    {
        (new Entity())->getValidator()
                      ->validateInput(Validator::VERIFY_OTP, $input);

        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $contact = $payoutLink->contact;

        $context = array_pull($input, Entity::CONTEXT);

        $receiver = $this->getReceiver($contact, $payoutLinkId);

        $requestContext = $this->processContext($payoutLinkId, $context);

        $payload = [
            self::RECEIVER  => $receiver,
            Entity::CONTEXT => $requestContext,
            self::SOURCE    => self::API_POUT_LNK_SCR,
            Entity::OTP     => $input[Entity::OTP]
        ];

        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_VERIFY,
            [
                self::RECEIVER  => $receiver,
                Entity::CONTEXT => $requestContext,
                self::SOURCE    => self::API_POUT_LNK_SCR
            ]
        );

        $this->raven->verifyOtp($payload);

        $token = $this->tokenService->generate($payoutLinkId);

        return [
            'token' => $token
        ];
    }

    protected function processContext(string $payoutLinkId, string $context = null): string
    {
        $requestContext = $payoutLinkId;

        if (empty($context) === false)
        {
            $requestContext .= '.' . $context;
        }

        return $requestContext;
    }

    /**
<<<<<<< HEAD
     * Returns true, if atleast one delivery worked. else returns false
=======
     * This will be stored in redis after OTP verification
     * and will be used in subsequent api calls
     * @param string $payoutLinkId
     * @return string
     */
    protected function generateUniqueRequestToken(string $payoutLinkId): string
    {
        $timestamp =  Carbon::now(Timezone::IST)->getTimestamp();

        return $payoutLinkId . '.' . $timestamp;
    }

    /**
     * Returns true, if atkleast one delivery worked. else returns false
>>>>>>> payoutlinks_3
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

        if (empty($phoneNumber) === false)
        {
            $payload = $this->getSmsPayload($contact, $otp);

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

        if (empty($email) === false)
        {
            $customerEmailOtp = new CustomerOtp($email,
                                                $otp,
                                                $this->merchant->getName(),
                                                $payoutLink->getDescription());
            try
            {
                // Todo, pl update the template with the new html. Currently this is a plain test email
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

    protected function getSmsPayload(ContactEntity $contactEntity, string $otp): array
    {
        $payload = [
            self::PARAMS   => [
                self::CUSTOMER_NAME => $contactEntity->getName(),
                Entity::OTP         => $otp
            ],
            self::TEMPLATE => self::SMS_TEMPLATE,
            self::SOURCE   => self::API_PAYOUT_LINK_SRC_STR,
            self::RECEIVER => $contactEntity->getContact()
        ];

        return $payload;
    }

    protected function generateOtp(ContactEntity $contact, string $payoutLinkId, string $context = null)
    {
        $receiver = $this->getReceiver($contact, $payoutLinkId);

        $requestContext = $this->processContext($payoutLinkId, $context);

        $payload = [
            self::RECEIVER => $receiver,
            Entity::CONTEXT  => $requestContext,
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

        if (key_exists(Entity::OTP, $response) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED,
                                          [
                                              'request'  => $payload,
                                              'response' => $response,
                                          ]
            );
        }

        return $response[Entity::OTP];
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
                    self::MESSAGE        => $e->getMessage(),
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

        if ((empty($phoneNumber) === true) and (empty($email) === true))
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

        if (empty($phoneNumber) === false)
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
