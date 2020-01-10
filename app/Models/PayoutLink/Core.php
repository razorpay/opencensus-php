<?php

namespace RZP\Models\PayoutLink;

use View;
use Mail;
use RZP\Models\Base;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode;
use RZP\Constants\Environment;
use RZP\Models\Payout\Purpose;
use RZP\Models\FundAccount\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\PayoutLink\External\FundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
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
    const ONE_LAC                 =  10000000;
    const MESSAGE                 = 'message';
    const SUCCESS                 = 'success';
    const MUTEX_TIMEOUT           = 60;
    const MERCHANT_NAME           = 'merchant_name';
    const PAYOUT_PURPOSE          = 'payout_purpose';

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

        $this->mutex = $this->app['api.mutex'];
    }

    public function getSettings(MerchantEntity $merchant)
    {
        $settingsAccessor = $this->getSettingsAccessor($merchant);

        return $settingsAccessor->all()->toArray();
    }

    /**
     * Updates settings for payout-links on merchant level
     * @param MerchantEntity $merchant
     * @param $input
     * @return array
     */
    public function updateSettings(MerchantEntity $merchant, array $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_SETTINGS_UPDATE,
            [
                'merchant_id' => $merchant->getPublicId(),
                'input'       => $input
            ]
        );

        (new Validator())->validateInput(Validator::SETTINGS_RULE, $input);

        $settingsAccessor = $this->getSettingsAccessor($merchant);

        $settingsAccessor->upsert($input)->save();

        return [self::SUCCESS => self::OK];
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        (new Validator)->validateInput(Validator::GET_FUND_ACCOUNT_BY_CONTACT_RULE, $input);

        $this->tokenService->verify($input[Entity::TOKEN], $payoutLinkId);

        $fundAccounts = $this->repo
                             ->payout_link
                             ->getActiveFundAccountsByPayoutLinkIdAndMerchant($payoutLinkId, $this->merchant);

        return $fundAccounts;
    }

    public function cancel(Entity $payoutLink): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CANCEL_REQUEST,
            [
                'id' => $payoutLink->getPublicId()
            ]
        );

        // on code level, we are not going to allow cancel operation when payout-link is in processing
        // not adding this check in Status.php, because payoutlink can move from
        // Processing -> Cancelled, when the underlying payout is cancelled
        if ($payoutLink->getStatus() === Status::PROCESSING)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_CANNOT_BE_CANCELLED_IN_THIS_STATE,
                null,
                [
                    self::PAYOUT_LINK_ID => $payoutLink->getPublicId(),
                    'current_status'     => $payoutLink->getStatus(),
                    'next_status'        => Status::CANCELLED
                ]);
        }

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
     * @param Entity $payoutLink
     * @param array $input
     * @return Entity
     */
    public function initiate(Entity $payoutLink, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_INITIATE_FUND_ACCOUNT_ADD,
            $input);

        // Adding Mutex, because we want only one initiate call at a time on the same payout-link
        // So by flow, if two calls to add a fund-account-id + initiate payout come in, and the first one is successful,
        // then the next call waiting for the mutex should fail in token verification itself.
        // If we do move the token auth outside the mutex, then its possible for two fund-accounts to be added,
        // because the token verification was already successful and as soon as the mutex is acquired Fund Account
        // will be added and a payout created.
        // Also the whole thing will be a transaction, as we do not want to add new fund-account if any step fails
        return $this->mutex->acquireAndRelease(
            $payoutLink,
            function() use ($payoutLink, $input)
            {
                return $this->repo->transaction(
                    function() use ($payoutLink, $input)
                    {
                        (new Validator())->validateInput(Validator::ADD_FUND_ACCOUNT_RULE, $input);

                        $token = array_pull($input, Entity::TOKEN);

                        $this->tokenService->verify($token, $payoutLink->getPublicId());

                        if (in_array($payoutLink->getStatus(), Status::VALID_STARTING_STATUSES) === false)
                        {
                            throw new BadRequestException(
                                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST,
                                null,
                                [
                                    self::PAYOUT_LINK_ID => $payoutLink->getPublicId(),
                                    Entity::STATUS       => $payoutLink->getStatus()
                                ]);
                        }

                        // Code to create/fetch fund account and associate it with the payout-link
                        $fundAccount = (new FundAccountClient())->processFundAccountInput($input,
                                                                                          $this->merchant,
                                                                                          $payoutLink->contact);

                        // in case a fund-account-id send if not of type bank_account / vpa,
                        // then exception should be thrown
                        $fundAccountType = $fundAccount->getAccountType();

                        if (($fundAccountType !== Type::VPA) and
                            ($fundAccountType !== Type::BANK_ACCOUNT))
                        {
                            throw new BadRequestException(
                                ErrorCode::BAD_REQUEST_ONLY_VPA_AND_BANK_ACCOUNT_SUPPORTED,
                                null,
                                [
                                    Entity::ID              => $payoutLink->getId(),
                                    Entity::FUND_ACCOUNT_ID => $fundAccount->getId(),
                                ]
                            );
                        }

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
                                               'payout_link_id'     => $payoutLink->getPublicId(),
                                               'payout_link_status' => $payoutLink->getStatus()
                                           ]);

                        $this->tokenService->invalidate($token);

                        return $payoutLink;
                    });
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * This function will listen to payout updates, and update the corresponding payout-link
     * This will be inside a mutex. Transaction is not required, because its just a status update
     *
     * @param Entity $payoutLink
     * @param PayoutEntity $payout
     */
    public function payoutUpdateListener(Entity $payoutLink, PayoutEntity $payout)
    {
        $payoutStatus = $payout->getStatus();

        if (isset(Status::PAYOUT_TO_PAYOUT_LINK_STATUSES[$payoutStatus]) === false)
        {
            $this->trace->warning(TraceCode::PAYOUT_LINK_UN_HANDLED_PAYOUT_STATUS,
                                  [
                                      'payout_link_id' => $payoutLink->getPublicId(),
                                      'payout_status'  => $payoutStatus,
                                      'payout_id'      => $payout->getPublicId()
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

        //
        // Its possible that the mutex on the same payoutLinksId be acquired twice in the same request.
        // But instead of failing on lock-acquire, because the requestId is the same,
        // this is handled in the Mutex Service,
        // and access is given to the successive locks belonging to the same requestId
        //
        $this->mutex->acquireAndRelease(
            $payoutLink->getPublicId(),
            function () use ($payoutLink, $payoutStatus, $nextPayoutLinkStatus)
            {
                $payoutLink->setStatus($nextPayoutLinkStatus);

                $isDirty = $payoutLink->isDirty();

                $this->repo->saveOrFail($payoutLink);

                if ($isDirty === true)
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

    public function create(array $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        (new Validator())->validateInput(Validator::COMPOSITE_CREATE_RULE, $input);

        (new Purpose())->validatePurpose($this->merchant, $input[Entity::PURPOSE]);

        $this->processAccountNumber($input);

        $contactDetails = array_pull($input, 'contact');

        $contact = (new ContactClient())->processContact($contactDetails, $this->merchant);

        $input[Entity::CONTACT_NAME] = $contact->getName();

        $input[Entity::CONTACT_EMAIL] = $contact->getEmail();

        $input[Entity::CONTACT_PHONE_NUMBER] = $contact->getContact();

        $user = $this->app['basicauth']->getUser();

        $payoutLink = (new Entity)->build($input);

        // Doing this because we need the Id for generating short URL
        $payoutLink->generateId();

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->contact()->associate($contact);

        $balance = $this->getBalance($input);

        $payoutLink->balance()->associate($balance);

        $payoutLink->user()->associate($user);

        $payoutLink->setStatus(Status::ISSUED);

        $this->repo->saveOrFail($payoutLink);

        $this->app['events']->fire(Status::STATUS_TO_WEBHOOK_EVENT[Status::ISSUED],
                                   [$payoutLink]);

        return $payoutLink;
    }

    public function viewHostedPage(Entity $payoutLink)
    {
        $hostedPageData = $this->getDataForHostedPage($payoutLink);

        return View::make('payout_link.customer_hosted', $hostedPageData);
    }

    protected function getDataForHostedPage(Entity $payoutLink): array
    {
        $maskedEmail = $this->getMaskedEmail($payoutLink->getContactEmail());

        $maskedPhone = $this->getMaskedPhone($payoutLink->getContactPhoneNumber());

        $isUpiEnabled = $this->allowUpi($payoutLink);

        $isProduction = $this->app->environment() === Environment::PRODUCTION;

        $fundAccountDetails = $this->getMaskedFundAccountDetails($payoutLink->fundAccount);

        $data = [
            'api_host'                => $this->config['applications.payout_links.url'],
            'payout_link_id'          => $payoutLink->getPublicId(),
            'payout_link_status'      => $payoutLink->getStatus(),
            'amount'                  => $payoutLink->getAmount(),
            'currency'                => $payoutLink->getCurrency(),
            'user_name'               => $payoutLink->getContactName(),
            'description'             => $payoutLink->getDescription(),
            'user_email'              => $maskedEmail,
            'user_phone'              => $maskedPhone,
            'receipt'                 => $payoutLink->getReceipt(),
            'merchant_logo_url'       => $this->merchant->getLogoUrl(),
            'payout_link_description' => $payoutLink->getDescription(),
            'primary_color'           => $this->merchant->getBrandColor(),
            'merchant_name'           => $this->getDisplayName(),
            'allow_upi'               => $isUpiEnabled,
            'banking_url'             => $this->config['applications.banking_service_url'],
            'is_production'           => $isProduction,
            'fund_account_details'    => json_encode($fundAccountDetails)
        ];

        return $data;
    }

    /**
     * 1. Setting is enabled
     * 2. Is not RBL
     * 3. Amount less than 1 lac
     * @param Entity $payoutLink
     * @return bool
     */
    protected function allowUpi(Entity $payoutLink)
    {
        $channelSupportsUpi = true;

        $settingsAccessor = $this->getSettingsAccessor($this->merchant);

        $upiEnabledInSettings = boolval($settingsAccessor->get(Entity::UPI));

        $bankingAccount = $this->repo->banking_account->getFromBalanceId($payoutLink->getBalanceId());

        if ($bankingAccount->getChannel() === Channel::RBL)
        {
            $channelSupportsUpi = false;
        }

        $amountLessThanLac = $payoutLink->getAmount() <= self::ONE_LAC ? true : false;

        return $upiEnabledInSettings and $channelSupportsUpi and $amountLessThanLac;
    }

    protected function getMaskedFundAccountDetails(FundAccountEntity $fundAccount = null)
    {

        if ($fundAccount === null)
        {
            return null;
        }

        $details = $fundAccount->toArrayPublic();

        $type = $fundAccount->getAccountType();

        switch ($type)
        {
            case Type::VPA:
                $address = $details[Type::VPA][VpaEntity::ADDRESS];

                $handle = explode('@', $address)[1];

                $address = explode('@', $address)[0];

                $maskedAddress = substr($address, 0, 2) .
                                 str_repeat('*', strlen($address) - 4) .
                                 substr($address, strlen($address) - 2, 2);

                $maskedHandle = substr($handle, 0, 2) .
                                str_repeat('*', strlen($handle) - 4) .
                                substr($handle, strlen($handle) - 2, 2);

                $details[Type::VPA][VpaEntity::ADDRESS] = sprintf('%s@%s', $maskedAddress, $maskedHandle);
        }

        return $details;
    }

    /**
     * Masks the customer email as follows
     * Input: test_email@gmail.com
     * Output: tes*****l@g****.com
     *
     * @param string $email
     * @return mixed|string
     */
    protected function getMaskedEmail(string $email = null)
    {
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
                                             'email'      => $email
                                         ]
            );
        }

        return $maskedEmail;
    }

    public function getMaskedPhone(string $phone = null)
    {
        if (empty($phone) === true)
        {
            return '';
        }

        $phoneLen = strlen($phone);

        return substr($phone, 0, 2) .
               str_repeat('*', $phoneLen - 4) .
               substr($phone, $phoneLen - 2, 2);

    }

    public function generateAndSendCustomerOtp(Entity $payoutLink, array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_GENERATE,
            [
                self::PAYOUT_LINK_ID => $payoutLink->getPublicId()
            ]
        );

        // extra context param, that the F.E. can pass, in case they want to
        // force generation of a new OTP
        $context = array_pull($input, Entity::CONTEXT);

        if (in_array($payoutLink->getStatus(), Status::VALID_PROCESSING_START_STATUSES) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_STATE_FOR_OTP_GENERATION,
                null,
                [
                    Entity::ID     => $payoutLink->getPublicId(),
                    Entity::STATUS => $payoutLink->getStatus()
                ]
            );
        }

        $otp = $this->generateOtp($payoutLink, $context);

        $this->deliverOtp($payoutLink, $otp);

        return [self::SUCCESS => self::OK];
    }

    public function verifyCustomerOtp(Entity $payoutLink, $input): array
    {
        (new Validator)->validateInput(Validator::VERIFY_OTP, $input);

        if (in_array($payoutLink->getStatus(), Status::VALID_PROCESSING_START_STATUSES) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_STATE_FOR_OTP_VERIFICATION,
                null,
                [
                    Entity::ID     => $payoutLink->getPublicId(),
                    Entity::STATUS => $payoutLink->getStatus()
                ]
            );
        }

        $context = array_pull($input, Entity::CONTEXT);

        $receiver = $this->getReceiver($payoutLink);

        $requestContext = $this->processContext($payoutLink->getPublicId(), $context);

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

        $token = $this->tokenService->generate($payoutLink->getPublicId());

        return [
            'token' => $token
        ];
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

    protected function getSettingsAccessor($merchant)
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);
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
                                          null,
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
            $customerEmailOtp = new CustomerOtp($payoutLink->getId(),
                                                $this->merchant->getId(),
                                                $otp);

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
                self::MERCHANT_NAME  => $this->getDisplayName(),
                Entity::OTP          => $otp,
                self::PAYOUT_PURPOSE => $payoutLink->getPurpose()
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
                                          null,
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
                             $this->config['applications.payout_links.url'],
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

            $this->trace->info(TraceCode::PAYOUT_LINK_SHORT_URL_CREATED,
                [
                    Entity::ID        => $payoutLink->getPublicId(),
                    Entity::SHORT_URL => $shortUrl
                ]);
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
                    'target_url'         => $targetUrl
                ]
            );
        }

        $payoutLink->setShortUrl($shortUrl);
    }

    /**
     * @return string
     */
    protected function getDisplayName(): string
    {
        $displayName = $this->merchant->getDisplayName();

        if(empty($displayName) === true)
        {
            return $this->merchant->getName();
        }

        return $displayName;
    }
}
