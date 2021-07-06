<?php

namespace RZP\Models\PayoutLink;

use View;
use Mail;
use Config;


use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Payout\Mode;
use RZP\Constants\Environment;
use RZP\Models\Payout\Purpose;
use RZP\Models\FundAccount\Type;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Jobs\PayoutLinkNotification;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\PayoutLink\External\FundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PayoutLink\External\Payout as PayoutClient;
use RZP\Models\PayoutLink\External\Contact as ContactClient;
use RZP\Models\PayoutLink\Notifications\Type as NotificationType;
use RZP\Models\PayoutLink\External\FundAccount as FundAccountClient;

class Core extends Base\Core
{
    use Base\Traits\ProcessAccountNumber;

    const LONG_URL_FORMAT     = '%s/v1/payout-links/%s/view';
    const SMS_TEMPLATE        = 'sms.payout_link.otp';

    // Source param, when calling Raven Apis
    const OK                  = 'OK';
    const MESSAGE             = 'message';
    const SUCCESS             = 'success';
    const MUTEX_TIMEOUT       = 60;
    const SLACK_CHANNEL_COLOR = 'danger';
    const CONTRAST_COLOR      = "contrast_color";

    protected $elfin;

    protected $raven;

    protected $tokenService;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];

        $this->raven = $this->app['raven'];

        $this->tokenService = $this->app['token_service'];

        $this->mutex = $this->app['api.mutex'];
    }

    public function getSettings(MerchantEntity $merchant)
    {
        return $this->getSettingsAttributeArray($merchant);
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
            ]
        );
        (new Validator())->validateInput(Validator::SETTINGS_RULE, $input);

        $settingsAccessor = $this->getSettingsAccessor($merchant);

        $oldSettings = $settingsAccessor->all()->toArray();

        $settingsAccessor->upsert($input)->save();

        $this->notifyPayoutModeSettingChangeOnSlack($merchant->getPublicId(), $oldSettings, $input);

        return $settingsAccessor->all()->toArray();
    }

    public function getFundAccountsOfContact(Entity $payoutLink, array $input)
    {
        (new Validator)->validateInput(Validator::GET_FUND_ACCOUNT_BY_CONTACT_RULE, $input);

        $this->tokenService->verify($input[Entity::TOKEN], $payoutLink->getPublicId());

        return $payoutLink->contact->fundAccounts->where('active', true);
    }

    public function cancel(Entity $payoutLink): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CANCEL_REQUEST,
            [
                'id' => $payoutLink->getPublicId()
            ]
        );

        return $this->mutex->acquireAndRelease(
            $payoutLink->getPublicId(),
            function () use ($payoutLink)
            {
                $this->repo->reload($payoutLink);

                // on code level, we are not going to allow cancel operation when payout-link is in processing
                // not adding this check in Status.php, because payoutlink can move from
                // Processing -> Cancelled, when the underlying payout is cancelled
                if ($payoutLink->getStatus() === Status::PROCESSING)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYOUT_LINK_CANNOT_BE_CANCELLED_IN_THIS_STATE,
                        null,
                        [
                            'payout_link_id' => $payoutLink->getPublicId(),
                            'current_status' => $payoutLink->getStatus(),
                            'next_status'    => Status::CANCELLED
                        ]);
                }

                // If already cancelled, then return the entity without any change.
                // Makes this call idempotent.
                if ($payoutLink->getStatus() === Status::CANCELLED)
                {
                    return $payoutLink;
                }

                $payoutLink->setStatus(Status::CANCELLED);

                $this->repo->saveOrFail($payoutLink);

                $this->app->events->dispatch(Status::getWebhookEventCorrespondingToStatus(Status::CANCELLED),
                                         [$payoutLink]);

                return $payoutLink;
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function pushSlackAlert(string $headline, array $message)
    {
        $settings = [
            'channel' => $this->config->get('slack.channels.payout_links_alerts'),
            'color'   => self::SLACK_CHANNEL_COLOR
        ];

        $this->app['slack']->queue($headline, $message, $settings);
    }

    /**
     * @param Entity $payoutLink
     * @param array $input
     * @return Entity
     */
    public function initiate(Entity $payoutLink, array $input): Entity
    {
        (new Validator())->validateInput(Validator::ADD_FUND_ACCOUNT_RULE, $input);
        //
        // Adding Mutex, because we want only one initiate call at a time on the same payout-link
        // So by flow, if two calls to add a fund-account-id + initiate payout come in, and the first one is successful,
        // then the next call waiting for the mutex should fail in token verification itself.
        // If we do move the token auth outside the mutex, then its possible for two fund-accounts to be added,
        // because the token verification was already successful and as soon as the mutex is acquired Fund Account
        // will be added and a payout created.
        // Also the whole thing will be a transaction, as we do not want to add new fund-account if any step fails
        //
        $payoutLink = $this->mutex->acquireAndRelease(
            $payoutLink->getPublicId(),
            function () use ($payoutLink, $input)
            {
                return $this->repo->transaction(
                    function () use ($payoutLink, $input)
                    {
                        $this->repo->reload($payoutLink);

                        $this->trace->info(
                            TraceCode::PAYOUT_LINK_INITIATE_FUND_ACCOUNT_ADD,
                            [
                                'payout_link_id' => $payoutLink->getPublicId()
                            ]);

                        $token = array_pull($input, Entity::TOKEN);

                        $this->tokenService->verify($token, $payoutLink->getPublicId());

                        if (Status::payoutLinkInProcessableState($payoutLink->getStatus()) === false)
                        {
                            throw new BadRequestException(
                                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST,
                                null,
                                [
                                    'payout_link_id' => $payoutLink->getPublicId(),
                                    Entity::STATUS   => $payoutLink->getStatus()
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

                        //
                        // setting the status to Processing, before the payout-create call, even though,
                        // the payout on getting issued would have updated the PayoutLink via a queue,
                        // for the following two scenarios
                        // 1. Payout takes 5 seconds (queue delay) to update PL, and in the mean while
                        //    PL is in ISSUED state, where it can get cancelled. Hence there can
                        //    be an active Payout, with a cancelled PL
                        // 2. Another PayoutLink initiate request is fired before Payout updated PayoutLink,
                        //    and now we have two active payouts for the same PayoutLink.
                        //
                        $payoutLink->setStatus(Status::PROCESSING);

                        $this->repo->saveOrFail($payoutLink);

                        (new PayoutClient())->processPayout($payoutLink, $this->merchant, $mode);

                        $payoutLink->setPayout();

                        $this->app->events->dispatch(Status::getWebhookEventCorrespondingToStatus(Status::PROCESSING),
                                                 [$payoutLink]);

                        $this->trace->info(TraceCode::PAYOUT_LINK_INVALIDATING_REDIS_TOKEN,
                                           [
                                               'payout_link_id' => $payoutLink->getPublicId(),
                                               Entity::STATUS   => $payoutLink->getStatus()
                                           ]);

                        $this->tokenService->invalidate($token);

                        return $payoutLink;
                    });
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);

        return $payoutLink;
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
                'payout_id'               => $payout->getPublicId(),
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
                $this->repo->reload($payoutLink);

                $payoutLink->setStatus($nextPayoutLinkStatus);

                $isDirty = $payoutLink->isDirty();

                $this->repo->saveOrFail($payoutLink);

                if ($isDirty === true)
                {
                    $this->app->events->dispatch(Status::getWebhookEventCorrespondingToStatus($nextPayoutLinkStatus),
                                             [$payoutLink]);

                    $this->pushStatusUpdateNotification($payoutLink);
                }
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function updateNotificationInformation(Entity $payoutLink, array $input): Entity
    {
        return $this->mutex->acquireAndRelease(
            $payoutLink->getPublicId() . time(),
            function () use ($payoutLink, $input)
            {
                $this->repo->reload($payoutLink);

                (new Validator($payoutLink))->validateInput(Validator::RESEND_NOTIFICATION_RULE , $input);

                $contactEmail = array_pull($input, Entity::CONTACT_EMAIL, $payoutLink->getContactEmail());

                $contactPhone = array_pull($input, Entity::CONTACT_PHONE_NUMBER, $payoutLink->getContactPhoneNumber());

                if (empty($contactEmail) === true)
                {
                    $contactEmail = $payoutLink->getContactEmail();
                }

                if (empty($contactPhone) === true)
                {
                    $contactPhone = $payoutLink->getContactPhoneNumber();
                }

                $sendSms = boolval(array_pull($input, Entity::SEND_SMS, $payoutLink->getSendSms()));

                $sendEmail = boolval(array_pull($input, Entity::SEND_EMAIL, $payoutLink->getSendEmail()));

                $updateValues = [
                    Entity::CONTACT_PHONE_NUMBER => $contactPhone,
                    Entity::CONTACT_EMAIL        => $contactEmail,
                    Entity::SEND_SMS             => $sendSms,
                    Entity::SEND_EMAIL           => $sendEmail,
                ];

                $payoutLink->update($updateValues);

                $payoutLink->saveOrFail();

                return $payoutLink;
            });
    }

    public function create(array $input): Entity
    {
        return $this->repo->transaction(
            function () use ($input)
            {

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

                $this->app['events']->dispatch(Status::getWebhookEventCorrespondingToStatus(Status::ISSUED),
                                            [$payoutLink]);

                $this->sendLinkToCustomers($payoutLink);

                return $payoutLink;
            }
        );
    }

    public function viewHostedPage(Entity $payoutLink)
    {
        $hostedPageData = $this->getDataForHostedPage($payoutLink);

        return View::make('payout_link.customer_hosted', $hostedPageData);
    }

    public function generateAndSendCustomerOtp(Entity $payoutLink, array $input): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_LINK_CUSTOMER_OTP_GENERATE,
            [
                'payout_link_id' => $payoutLink->getPublicId()
            ]
        );
        // extra context param, that the F.E. can pass, in case they want to
        // force generation of a new OTP
        $context = array_pull($input, Entity::CONTEXT);

        if (Status::payoutLinkInProcessableState($payoutLink->getStatus()) === false)
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

        return [Entity::SUCCESS => Entity::OK];
    }

    public function verifyCustomerOtp(Entity $payoutLink, $input): array
    {
        (new Validator)->validateInput(Validator::VERIFY_OTP, $input);

        if (Status::payoutLinkInProcessableState($payoutLink->getStatus()) === false)
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
            Entity::RECEIVER => $receiver,
            Entity::CONTEXT  => $requestContext,
            Entity::SOURCE   => Entity::API_POUT_LNK_SRC,
            Entity::OTP      => $input[Entity::OTP]
        ];

        $response = $this->raven->verifyOtp($payload);

        if ((isset($response['success']) === false) or
            ($response['success'] !== true)
        )
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP,
                                          null,
                                          [
                                              'payout_link_id' => $payoutLink->getPublicId()
                                          ]);
        }

        $token = $this->tokenService->generate($payoutLink->getPublicId());

        return [
            'token' => $token
        ];
    }

    public function sendLinkToCustomers(Entity $payoutLink)
    {
        PayoutLinkNotification::dispatch($this->mode,
                                         NotificationType::SEND_LINK,
                                         $payoutLink->getPublicId());
    }

    /**
     * 1. Setting is enabled
     * 2. Is not RBL
     * 3. Amount less than 1 lac
     * @param Entity $payoutLink
     * @param array $settingsAttributeArray
     * @return bool
     */
    protected function allowUpi(Entity $payoutLink, array $settingsAttributeArray)
    {
        $channelSupportsUpi = true;

        $upiEnabledInSettings = (key_exists(Entity::UPI, $settingsAttributeArray) and
                                boolval($settingsAttributeArray[Entity::UPI]));

        $bankingAccount = $payoutLink->balance->bankingAccount;

        if ($bankingAccount->getChannel() === Channel::RBL)
        {
            $channelSupportsUpi = false;
        }

        $amountLessThanLac = $payoutLink->getAmount() <= Validator::MAX_UPI_AMOUNT ? true : false;

        return $upiEnabledInSettings and $channelSupportsUpi and $amountLessThanLac;
    }

    /**
     * Masks the VPA details before sending to the front-end
     * todo, pl Need to move to VPA/Entity [https://razorpay.atlassian.net/browse/RX-1343]
     *
     * @param FundAccountEntity|null $fundAccount
     * @return array|null
     */
    protected function getMaskedFundAccountDetails(FundAccountEntity $fundAccount = null)
    {
        $percentageToMask = '0.7';

        if ($fundAccount === null)
        {
            return null;
        }

        $details = $fundAccount->toArrayPublic();

        $type = $fundAccount->getAccountType();

        switch ($type)
        {
            case Type::VPA:
                $address = $details[Type::VPA][VpaEntity::USERNAME];

                $handle = $details[Type::VPA][VpaEntity::HANDLE];

                $addressLen = strlen($address);

                $handleLen = strlen($handle);

                $lengthOfHandleToMask = ceil($handleLen * $percentageToMask);

                $lengthOfAddressToMask = ceil($addressLen * $percentageToMask);

                $maskedAddress = substr($address, 0, $addressLen - $lengthOfAddressToMask) .
                                 str_repeat('*', $lengthOfAddressToMask);

                $maskedHandle = substr($handle, 0, $handleLen - $lengthOfHandleToMask) .
                                str_repeat('*', $lengthOfHandleToMask);

                $details[Type::VPA][VpaEntity::ADDRESS] = sprintf('%s@%s', $maskedAddress, $maskedHandle);

                $details[Type::VPA][VpaEntity::HANDLE] = $maskedHandle;

                $details[Type::VPA][VpaEntity::USERNAME] = $maskedAddress;
        }

        return $details;
    }

    /**
     * @param Entity $payoutLink
     * @return string
     * @throws BadRequestValidationFailureException
     */
    protected function getPayoutMode(Entity $payoutLink)
    {
        $settingsAttributeArray = $this->getSettingsAttributeArray($this->merchant);

        $amount = $payoutLink->getAmount();

        $fundAccount = $payoutLink->fundAccount;

        switch ($fundAccount->getAccountType())
        {
            case Type::BANK_ACCOUNT:
                $isImpsEnabled = (key_exists(Entity::IMPS, $settingsAttributeArray) and
                                 boolval($settingsAttributeArray[Entity::IMPS]));

                if (($isImpsEnabled === true) and
                    ($amount < Validator::MAX_IMPS_AMOUNT))
                {
                    return Mode::IMPS;
                }
                else
                {
                    return Mode::NEFT;
                }

            case Type::VPA:
                return Mode::UPI;

            default:
                throw new BadRequestValidationFailureException('Fund Accounts of type ' .
                                                               $fundAccount->getAccountType() .
                                                               'are not supported',
                                                               null,
                                                               [
                                                                   'payout_link_id'    => $payoutLink->getPublicId(),
                                                                   'fund_account_id'   => $fundAccount->getPublicId(),
                                                                   'fund_account_type' => $fundAccount->getAccountType()
                                                               ]
                );
        }
    }

    protected function getDataForHostedPage(Entity $payoutLink): array
    {
        $maskedEmail = mask_email($payoutLink->getContactEmail());

        $maskedPhone = mask_phone($payoutLink->getContactPhoneNumber());

        $fundAccountDetails = $this->getMaskedFundAccountDetails($payoutLink->fundAccount);

        $settingsAttributeArray = $this->getSettingsAttributeArray($payoutLink->merchant);

        // This is required to Add/Remove code on the HTML page that pushed GA events.
        // We do not want this to be added in non-prod envs
        $isProduction = $this->app->environment() === Environment::PRODUCTION;

        $data = [
            'api_host'                          => $this->config['url.api.production'],
            'payout_link_id'                    => $payoutLink->getPublicId(),
            'payout_link_status'                => $payoutLink->getStatus(),
            'amount'                            => $payoutLink->getAmount(),
            'currency'                          => $payoutLink->getCurrency(),
            'user_name'                         => $payoutLink->getContactName(),
            'description'                       => $payoutLink->getDescription(),
            'user_email'                        => $maskedEmail,
            'user_phone'                        => $maskedPhone,
            'receipt'                           => $payoutLink->getReceipt(),
            'merchant_logo_url'                 => $this->merchant->getFullLogoUrlWithSize(),
            'payout_link_description'           => $payoutLink->getDescription(),
            'primary_color'                     => $this->merchant->getBrandColorElseDefault(),
            'merchant_name'                     => $this->merchant->getBillingLabel(),
            'allow_upi'                         => $this->allowUpi($payoutLink, $settingsAttributeArray),
            'banking_url'                       => $this->config['applications.banking_service_url'],
            'is_production'                     => $isProduction,
            'fund_account_details'              => json_encode($fundAccountDetails),
            'purpose'                           => $payoutLink->getPurpose(),
            'payout_utr'                        => $payoutLink->getPayoutUtr(),
            'payout_mode'                       => $payoutLink->getPayoutMode(),
            'payout_links_custom_message'       => $settingsAttributeArray[Entity::CUSTOM_MESSAGE] ?? null,
            'support_contact'                   => $settingsAttributeArray[Entity::SUPPORT_CONTACT] ?? null,
            'support_email'                     => $settingsAttributeArray[Entity::SUPPORT_EMAIL] ?? null,
            'support_url'                       => $settingsAttributeArray[Entity::SUPPORT_URL] ?? null
        ];

        return $data;
    }

    protected function getBalance(array $input)
    {
        $balanceId = array_pull($input, Entity::BALANCE_ID);

        $balance = $this->repo->balance->findByPublicIdAndMerchant($balanceId, $this->merchant);

        return $balance;
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

        if ((empty($phoneNumber) === true) and
            (empty($email) === true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL,
                                          null,
                                          [
                                              ContactEntity::ID      => $payoutLink->getContactId(),
                                              ContactEntity::NAME    => $payoutLink->getContactName(),
                                              ContactEntity::CONTACT => $payoutLink->getContactPhoneNumber(),
                                              ContactEntity::EMAIL   => $payoutLink->getContactEmail(),
                                              'payout_link_id'       => $payoutLink->getPublicId()
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
            } catch (\Exception $e)
            {
                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::PAYOUT_LINK_CUSTOMER_OTP_SMS_FAILED,
                                             [
                                                 Entity::CONTACT_ID           => $payoutLink->getContactId(),
                                                 Entity::CONTACT_NAME         => $payoutLink->getContactName(),
                                                 'payout_link_id'             => $payoutLink->getPublicId(),
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
            } catch (\Exception $e)
            {
                $this->trace->traceException($e,
                                             Trace::ERROR,
                                             TraceCode::PAYOUT_LINK_CUSTOMER_OTP_MAIL_FAILED,
                                             [
                                                 Entity::CONTACT_ID    => $payoutLink->getContactId(),
                                                 Entity::CONTACT_NAME  => $payoutLink->getContactName(),
                                                 Entity::CONTACT_EMAIL => $payoutLink->getContactEmail(),
                                                 'payout_link_id'      => $payoutLink->getPublicId()
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
                    'payout_link_id'             => $payoutLink->getPublicId()
                ]
            );
        }
    }

    protected function getSmsPayload(Entity $payoutLink, string $otp): array
    {
        $payload = [
            Entity::PARAMS   => [
                Entity::MERCHANT_NAME  => $this->merchant->getBillingLabel(),
                Entity::OTP            => $otp,
                Entity::PAYOUT_PURPOSE => $payoutLink->getPurpose()
            ],
            Entity::TEMPLATE => self::SMS_TEMPLATE,
            Entity::SOURCE   => Entity::API_POUT_LNK_SRC,
            Entity::RECEIVER => $payoutLink->getContactPhoneNumber()
        ];

        return $payload;
    }

    protected function generateOtp(Entity $payoutLink, string $context = null)
    {
        $receiver = $this->getReceiver($payoutLink);

        $requestContext = $this->processContext($payoutLink->getPublicId(), $context);

        $payload = [
            Entity::RECEIVER => $receiver,
            Entity::CONTEXT  => $requestContext,
            Entity::SOURCE   => Entity::API_POUT_LNK_SRC
        ];

        $response = $this->raven->generateOtp($payload);

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
        $otp = array_pull($response, Entity::OTP);

        return $otp;
    }

    protected function generateAndSetShortUrl(Entity &$payoutLink)
    {
        $targetUrl = sprintf(self::LONG_URL_FORMAT,
                             $this->config['applications.payout_links.url'],
                             $payoutLink->getPublicId());
        $params = [
            'metadata' => [
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
        } catch (\Exception $e)
        {
            // in case of a problem with elfin, the short url will be same as the target url.
            $shortUrl = $targetUrl;

            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYOUT_LINK_SHORT_URL_GENERATION_FAILED,
                [
                    self::MESSAGE    => $e->getMessage(),
                    'payout_link_id' => $payoutLink->getId(),
                    'target_url'     => $targetUrl
                ]
            );
        }

        $payoutLink->setShortUrl($shortUrl);
    }

    protected function getSettingsAccessor($merchant)
    {
        $settingsAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        return $settingsAccessor;
    }

    /**
     *
     * @param MerchantEntity $merchant
     * @return array
     */
    public function getMerchantSupportSettings(MerchantEntity $merchant): array
    {
        $settingsAttributeArray = $this->getSettingsAttributeArray($merchant);

        return [
            Entity::SUPPORT_CONTACT => $settingsAttributeArray[Entity::SUPPORT_CONTACT] ?? null,
            Entity::SUPPORT_EMAIL   => $settingsAttributeArray[Entity::SUPPORT_EMAIL] ?? null,
            Entity::SUPPORT_URL     => $settingsAttributeArray[Entity::SUPPORT_URL] ?? null,
            self::CONTRAST_COLOR    => $merchant->getContrastOfBrandColor(),
        ];
    }

    protected function getSettingsAttributeArray($merchant)
    {
        $settingsAccessor = $this->getSettingsAccessor($merchant);

        $settingsAttributeArray = $settingsAccessor->all()->toArray();

        $this->populatePayoutModesDefaultValue($settingsAttributeArray);

        return $settingsAttributeArray;
    }

    protected function populatePayoutModesDefaultValue(&$settingsAttributeArray)
    {
        if (key_exists(Entity::IMPS, $settingsAttributeArray) === false)
        {
            $settingsAttributeArray[Entity::IMPS] = 1;
        }

        if (key_exists(Entity::NEFT, $settingsAttributeArray) === false)
        {
            $settingsAttributeArray[Entity::NEFT] = 1;
        }

        if (key_exists(Entity::UPI, $settingsAttributeArray) === false)
        {
            $settingsAttributeArray[Entity::UPI] = 1;
        }
    }

    protected function pushStatusUpdateNotification(Entity $payoutLink)
    {
        if ($payoutLink->getStatus() === Status::PROCESSED)
        {
            PayoutLinkNotification::dispatch($this->mode,
                                             NotificationType::PAYOUT_LINK_PROCESSING_SUCCESSFUL,
                                             $payoutLink->getPublicId());
        }
        else if ($payoutLink->getStatus() === Status::ATTEMPTED)
        {
            PayoutLinkNotification::dispatch($this->mode,
                                             NotificationType::PAYOUT_LINK_PROCESSING_FAILED,
                                             $payoutLink->getPublicId());
        }
    }

    /**
     * Checks if the given key's value is changed in the new array or not. If the value in new array is same as the
     * default value passed, it is assumed that the value is not changed.
     * @param string    $key
     * @param array     $oldArray
     * @param array     $newArray
     * @return bool
     */
    protected function isBooleanValueChanged($key, $oldArray, $newArray)
    {
        $isValueChanged = false;

        $oldArrayValue = array_pull($oldArray, $key, true);

        $newArrayValue = array_pull($newArray, $key, $oldArrayValue);

        if (boolval($oldArrayValue) !== boolval($newArrayValue))
        {
            $isValueChanged = true;
        }

        return $isValueChanged;

    }

    /**
     * Pushes a notification to slack channel "operations_log" whenever a Payout Mode is enabled/disabled for a
     * merchant
     * @param string    $merchantId
     * @param array     $oldSettings
     * @param array     $newSettings
     * @return void
     */
    protected function notifyPayoutModeSettingChangeOnSlack($merchantId, $oldSettings, $newSettings)
    {
        if(key_exists(Entity::IMPS, $newSettings) === true)
        {
            $isImpsModeChanged = $this->isBooleanValueChanged(Entity::IMPS,
                                                              $oldSettings,
                                                              $newSettings);

            if($isImpsModeChanged === true)
            {
                $this->sendPayoutModeNotificationOnSlack($merchantId,
                                                         Entity::IMPS,
                                                         boolval($newSettings[Entity::IMPS]));
            }
        }

        if(key_exists(Entity::UPI, $newSettings) === true)
        {
            $isUPIModeChanged = $this->isBooleanValueChanged(Entity::UPI,
                                                             $oldSettings,
                                                             $newSettings);

            if($isUPIModeChanged === true)
            {
                $this->sendPayoutModeNotificationOnSlack($merchantId,
                                                         Entity::UPI,
                                                         boolval($newSettings[Entity::UPI]));
            }
        }
    }

    /**
     * Places the notification message on the slack app queue
     * @param string    $merchantId
     * @param string    $payoutMode
     * @param bool      $modeEnabled
     */
    protected function sendPayoutModeNotificationOnSlack($merchantId, $payoutMode, $modeEnabled)
    {
        $message = 'Payout Mode ';

        $message .= $payoutMode;

        if ($modeEnabled === true)
        {
            $message .= ' enabled for ';
        }
        else
        {
            $message .= ' disabled for ';
        }

        $user = $this->getInternalUsernameOrEmail();

        $message .= $merchantId . ' by ' . $user;

        $this->trace->info(
            TraceCode::PAYOUT_LINK_SETTINGS_UPDATE,
            [
                'merchant_id' => $merchantId,
            ]
        );

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    // this will get the count of PayoutLinks filtered with status and merchant_id
    public function getPayoutLinksByMerchantAndStatus(string $merchantId, string $status)
    {
        return $this->repo->payout_link->getPayoutLinkByStatus($merchantId, $status);
    }

    // this will get the count of PayoutLinks filtered with merchant_id
    public function getTotalLinksByMerchant(string $merchantId)
    {
        return $this->repo->payout_link->getPayoutLinkByMerchant($merchantId);
    }
}
