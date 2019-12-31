<?php

namespace RZP\Models\PayoutLink;

use View;
use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Mode;
use RZP\Models\FundAccount\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\PayoutLink\External\FundAccount;
use RZP\Models\PayoutLink\External\Payout as PayoutClient;
use RZP\Models\PayoutLink\External\Contact as ContactClient;
use RZP\Models\PayoutLink\External\FundAccount as FundAccountClient;

class Core extends Base\Core
{
    use Base\Traits\ProcessAccountNumber;

    const LONG_URL_FORMAT         = '%s/v1/payout-links/%s/view';
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
    const TWO_LACS                =  20000000;
    const MESSAGE                 = 'message';
    const SUCCESS                 = 'success';
    const ACTIVE                  = 'active';
    const MUTEX_TIMEOUT           = 60;

    protected $elfin;

    protected $raven;

    protected $tokenService;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];

        $this->raven = $this->app['raven'];

        $this->tokenService = new TokenService();

        $this->redis = $this->app['redis']->connection();

        $this->mutex = $this->app['api.mutex'];
    }

    public function getSettings($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $settingsAccessor = $this->getSettingsAccessor($merchant);

        return $settingsAccessor->all()->toArray();
    }

    /**
     * Updates settings for payoutlinks on merchant level
     * @param $input
     * @return array
     */
    public function updateSettings($merchantId, $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_SETTINGS_UPDATE,
            [
                'merchant_id' => $merchantId,
                'input'       => $input
            ]
        );

        (new Validator())->validateInput(Validator::SETTINGS_RULE, $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $settingsAccessor = $this->getSettingsAccessor($merchant);

        $settingsAccessor->upsert($input)->save();

        return [self::SUCCESS => self::OK];
    }

    protected function getSettingsAccessor($merchant)
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::GET_FUND_ACCOUNT_BY_CONTACT_RULE, $input);

        (new TokenService())->verify($input[Entity::TOKEN]);

        $fundAccounts = $this->repo
                             ->payout_link
                             ->getFundAccountByPayoutLinkIdAdnMerchant($payoutLinkId, $this->merchant);

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

        //   If already cancelled, then return the entity without any change. Makes this call idempotent.
        if ($payoutLink->getStatus() === Status::CANCELLED)
        {
            return $payoutLink;
        }

        return $this->mutex->acquireAndRelease(
            $payoutLink->getId(),
            function () use ($payoutLink)
            {
                $payoutLink->setStatus(Status::CANCELLED);

                $this->repo->saveOrFail($payoutLink);

                $this->app->events->fire(Status::STATUS_TO_WEBHOOK_EVENT[Status::CANCELLED],
                                         [$payoutLink]);

                return $payoutLink;
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * @param string $payoutLinkId
     * @param array $input
     * @return array
     */
    public function initiate(string $payoutLinkId, array $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_INITIATE_FUND_ACCOUNT_ADD,
            $input);

        // Adding Mutex, because we want only one initiate call at a time on the same payoutlink
        // Also the whole thing will be a transaction, as we do not want to add new fund-account if any step fails
        return $this->mutex->acquireAndRelease(
            $payoutLinkId,
            function() use ($payoutLinkId, $input)
            {
                return $this->repo->transaction(
                    function() use ($payoutLinkId, $input)
                    {
                        $tokenService = new TokenService();

                        $validator = (new Entity())->getValidator();

                        $validator->validateInput(Validator::ADD_FUND_ACCOUNT_RULE, $input);

                        $token = array_pull($input, Entity::TOKEN);

                        $tokenService->verify($token);

                        $payoutLink = $this->repo
                                           ->payout_link
                                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

                        if (in_array($payoutLink->getStatus(), Status::VALID_STARTING_STATUSES) === false)
                        {
                            throw new BadRequestException(
                                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST,
                                [
                                    self::PAYOUT_LINK_ID => $payoutLinkId,
                                    'status'             => $payoutLink->getStatus()
                                ]);
                        }

                        // Code to create/fetch fund account and associate it with the payoutlink
                        $fundAccount = (new FundAccountClient())->processFundAccountInput($input,
                                                                                          $this->merchant,
                                                                                          $payoutLink->contact);
                        $payoutLink->fundAccount()->associate($fundAccount);

                        // pushing this to DB layer, before going to payout create flow
                        $this->repo->saveOrFail($payoutLink);

                        // code to create a payout as this payout-link as the source
                        $mode = $this->getPayoutMode($payoutLink);

                        (new PayoutClient())->processPayout($payoutLink, $this->merchant, $mode);

                        $payoutLink->setStatus(Status::PROCESSING);

                        $this->repo->saveOrFail($payoutLink);

                        $this->app->events->fire(Status::STATUS_TO_WEBHOOK_EVENT[Status::PROCESSING],
                                                 [$payoutLink]);

                        $this->trace->info(TraceCode::PAYOUT_LINK_INVALIDATING_REDIS_TOKEN,
                                           [
                                               'payout_link_id'     => $payoutLinkId,
                                               'payout_link_status' => $payoutLink->getStatus()
                                           ]);

                        $tokenService->invalidate($token);

                        return $payoutLink;
                    });
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * This function will listen to payout updates, and update the corresponding payoutlink
     * This will be inside a mutex. Transaction is not required, because its just a status update
     *
     * @param string $payoutLinkId
     * @param string $payoutStatus
     */
    public function payoutUpdateListener(Entity $payoutLink, string $payoutStatus)
    {
        if (isset(Status::PAYOUT_TO_PAYOUT_LINK_STATUSES[$payoutStatus]) === false)
        {
            $this->trace->warning(TraceCode::PAYOUT_LINK_UN_HANDLED_PAYOUT_STATUS,
                                  [
                                      'payout_link_id' => $payoutLink->getPublicId(),
                                      'payout_status'  => $payoutStatus,
                                  ]);
            return;
        }

        $nextPayoutLinkStatus = Status::PAYOUT_TO_PAYOUT_LINK_STATUSES[$payoutStatus];

        $this->trace->info(
            TraceCode::PAYOUT_LINK_PAYOUT_UPDATE_PUSH,
            [
                'payout_link_id'          => $payoutLink->getPublicId(),
                'payout_status'           => $payoutStatus,
                'next_payout_link_status' => $nextPayoutLinkStatus
            ]);

        $this->mutex->acquireAndRelease(
            $payoutLink->getPublicId(),
            function () use ($payoutLink, $payoutStatus, $nextPayoutLinkStatus)
            {
                $payoutLink->setStatus($nextPayoutLinkStatus);

                $isDirty = $payoutLink->isDirty();

                $this->repo->saveOrFail($payoutLink);

                if($isDirty === true)
                {
                    $this->app->events->fire(Status::STATUS_TO_WEBHOOK_EVENT[$nextPayoutLinkStatus], [$payoutLink]);
                }
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * @param Entity $payoutLink
     * @return string
     */
    protected function getPayoutMode(Entity $payoutLink)
    {
        $settingsAccessor = $this->getSettingsAccessor($this->merchant);

        $amount = $payoutLink->getAmount();

        $fundAccount = $payoutLink->fundAccount;

        switch ($fundAccount->getAccountType())
        {
            case Type::BANK_ACCOUNT:
                $isImpsEnabled = boolval($settingsAccessor->get(Entity::IMPS));

                if (($isImpsEnabled === true) and
                    ($amount < self::TWO_LACS))
                {
                    return Mode::IMPS;
                }
                else
                {
                    return Mode::NEFT;
                }
            case Type::VPA:
                return Mode::UPI;
        }
    }

    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::COMPOSITE_CREATE_RULE, $input);

        $this->processAccountNumber($input);

        $contactDetails = array_pull($input, 'contact');

        $contact = (new ContactClient())->processContact($contactDetails, $this->merchant);

        $input[Entity::CONTACT_NAME] = $contact->getName();

        $input[Entity::CONTACT_EMAIL] = $contact->getEmail();

        $input[Entity::CONTACT_PHONE_NUMBER] = $contact->getContact();

        $payoutLink = (new Entity)->build($input);

        // Doing this because we need the Id for generating short URL
        $payoutLink->generateId();

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->contact()->associate($contact);

        $balance = $this->getBalance($input);

        $payoutLink->balance()->associate($balance);

        // todo: pl , unsure how to get the user entity from the request in core
//         $payoutLink->user()->associate($this->app['basicauth']->getUser());

        $payoutLink->setStatus(Status::ISSUED);

        $this->repo->saveOrFail($payoutLink);

        $this->app['events']->fire(Status::STATUS_TO_WEBHOOK_EVENT[Status::ISSUED],
                                   [$payoutLink]);

        return $payoutLink;
    }

    public function viewHostedPage($payoutLinkId)
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $hostedPageData = $this->getDataForHostedPage($payoutLink);

        return View::make('payout_link.customer_hosted', $hostedPageData);
    }

    protected function getDataForHostedPage(Entity $payoutLink): array
    {
        $contact = $payoutLink->contact;

        $maskedEmail = $this->getMaskedEmail($contact);

        $maskedPhone = $this->getMaskedPhone($contact);

        $data = [
            'api_host'                => $this->config['url.api.production'],
            'payout_link_id'          => $payoutLink->getPublicId(),
            'payout_link_status'      => $payoutLink->getStatus(),
            'amount'                  => $payoutLink->getAmount(),
            'currency'                => $payoutLink->getCurrency(),
            'user_name'               => $contact->getName(),
            'description'             => $payoutLink->getDescription(),
            'user_email'              => $maskedEmail,
            'user_phone'              => $maskedPhone,
            'receipt'                 => $payoutLink->getReceipt(),
            'merchant_logo_url'       => $this->merchant->getLogoUrl(),
            'payout_link_description' => $payoutLink->getDescription(),
            'primary_color'           => $this->merchant->getBrandColor(),
            'merchant_name'           => $this->merchant->getName()
        ];

        return $data;
    }

    /**
     * Masks the customer email as follows
     * Input: test_email@gmail.com
     * Output: tes*****l@g****.com
     *
     * @param ContactEntity $contact
     * @return mixed|string
     */
    protected function getMaskedEmail(ContactEntity $contact)
    {
        $email = $contact->getEmail();

        $maskedEmail = $email;

        if (empty($email) === true)
        {
            return '';
        }

        try
        {
            // assuming that if this is filled, then its a valid email

            $email = explode('@', $email); // ex: test_email@gmail.com

            $emailName = $email[0]; // test_email

            $emailDomain = $email[1]; // gmail.com

            $emailDomain = explode('.', $emailDomain);

            $domain = $emailDomain[0]; // gmail

            $topLevelDomain = $emailDomain[1]; // .com

            // replace the name except first 3 characters with *
            $maskedEmailName = substr($emailName, 0, 3) .
                               str_repeat('*', strlen($emailName) - 3);

            // replace the domain with *, except the first and the last character
            $maskedDomain = $domain[0] .
                            str_repeat('*', strlen($domain) - 2) .
                            $domain[strlen($domain) - 1];

            $maskedEmail = sprintf('%s@%s.%s', $maskedEmailName, $maskedDomain, $topLevelDomain);
        }
        catch(\Exception $e)
        {
            // Do not want the page load to fail because the email was incorrect
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::INVALID_EMAIL_CANNOT_MASK,
                                         [
                                             'email'      => $email,
                                             'contact_id' => $contact->getId()
                                         ]
            );
        }

        return $maskedEmail;
    }

    public function getMaskedPhone(ContactEntity $contact)
    {
        $phone = $contact->getContact();

        if (empty($phone) === true)
        {
            return '';
        }

        $phoneLen = strlen($phone);

        return substr($phoneLen, 0, 2) .
               str_repeat('*', $phoneLen - 4) .
               substr($phone, $phoneLen - 2, $phoneLen - 1);

    }

    protected function getBalance(array $input)
    {
        $balanceId = array_pull($input, Entity::BALANCE_ID);

        if (empty($balanceId) === true)
        {
            $balance = $this->merchant->primaryBalance;
        }
        else
        {
            $balance = $this->repo->balance->findByPublicIdAndMerchant($balanceId, $this->merchant);
        }

        return $balance;
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

        $otp = $this->generateOtp($payoutLink, $context);

        $this->deliverOtp($payoutLink, $otp);

        return [self::SUCCESS => self::OK];
    }

    public function verifyCustomerOtp($payoutLinkId, $input): array
    {
        (new Entity())->getValidator()
                      ->validateInput(Validator::VERIFY_OTP, $input);

        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $context = array_pull($input, Entity::CONTEXT);

        $receiver = $this->getReceiver($payoutLink);

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
     * @param Entity $payoutLink
     * @return string
     * @throws BadRequestException
     */
    protected function getReceiver(Entity $payoutLink): string
    {
        $phoneNumber = $payoutLink->getContactPhoneNumber();

        $email = $payoutLink->getContactEmail();

        if ((empty($phoneNumber) === true) and (empty($email) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL,
                                          [
                                              ContactEntity::ID      => $payoutLink->getContactId(),
                                              ContactEntity::NAME    => $payoutLink->getContactName(),
                                              ContactEntity::CONTACT => $payoutLink->getContactPhoneNumber(),
                                              ContactEntity::EMAIL   => $payoutLink->getContactEmail(),
                                              self::PAYOUT_LINK_ID   => $payoutLink->getPublicId()
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

    /**
     * * Returns true, if atleast one delivery worked. else returns false
     * @param Entity $payoutLink
     * @param string $otp
     * @return void
     * @throws BadRequestException
     */
    protected function deliverOtp(Entity $payoutLink, string $otp)
    {
        $successfulChannelPushCount = 0;

        $phoneNumber = $payoutLink->getContactPhoneNumber();

        if (empty($phoneNumber) === false)
        {
            $payload = $this->getSmsPayload($payoutLink, $otp);

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
                                                 Entity::CONTACT_ID           => $payoutLink->getContactId(),
                                                 Entity::CONTACT_NAME         => $payoutLink->getContactName(),
                                                 self::PAYOUT_LINK_ID         => $payoutLink->getPublicId(),
                                                 Entity::CONTACT_PHONE_NUMBER => $payoutLink->getContactPhoneNumber(),
                                             ]);
            }
        }

        $email = $payoutLink->getContactEmail();

        if (empty($email) === false)
        {
            $customerEmailOtp = new CustomerOtp($email,
                                                $otp,
                                                $this->merchant->getDisplayName(),
                                                $payoutLink->getPurpose(),
                                                $this->merchant->getLogoUrl(),
                                                $this->merchant->getBrandColor()

            );

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
                                                 Entity::CONTACT_ID    => $payoutLink->getContactId(),
                                                 Entity::CONTACT_NAME  => $payoutLink->getContactName(),
                                                 Entity::CONTACT_EMAIL => $payoutLink->getContactEmail(),
                                                 self::PAYOUT_LINK_ID  => $payoutLink->getPublicId()
                                             ]);
            }

        }

        if ($successfulChannelPushCount === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED,
                null,
                [
                    Entity::CONTACT_ID           => $payoutLink->getContactId(),
                    Entity::CONTACT_NAME         => $payoutLink->getContactName(),
                    Entity::CONTACT_PHONE_NUMBER => $payoutLink->getContactPhoneNumber(),
                    Entity::CONTACT_EMAIL        => $payoutLink->getContactEmail(),
                    self::PAYOUT_LINK_ID         => $payoutLink->getPublicId()
                ]
            );
        }
    }

    protected function getSmsPayload(Entity $payoutLink, string $otp): array
    {
        $payload = [
            self::PARAMS   => [
                self::CUSTOMER_NAME => $payoutLink->getContactName(),
                Entity::OTP         => $otp
            ],
            self::TEMPLATE => self::SMS_TEMPLATE,
            self::SOURCE   => self::API_PAYOUT_LINK_SRC_STR,
            self::RECEIVER => $payoutLink->getContactPhoneNumber()
        ];

        return $payload;
    }

    protected function generateOtp(Entity $payoutLink, string $context = null)
    {
        $receiver = $this->getReceiver($payoutLink);

        $requestContext = $this->processContext($payoutLink->getPublicId(), $context);

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
}
