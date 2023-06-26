<?php

namespace RZP\Models\BankingAccount;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Contact;
use RZP\Diag\EventCode;
use RZP\Models\Counter;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Admin\Admin;
use RZP\Models\User;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\Schedule\Type;
use RZP\Models\Schedule\Task;
use RZP\Models\VirtualAccount;
use RZP\Models\Schedule\Period;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Activate;
use RZP\Models\Settlement\Channel;
use RZP\Services\SalesForceClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\State;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\IntegrationException;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\SalesForce\SalesForceService;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccount\Channel as BAChannel;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\SalesForce\SalesForceEventRequestDTO;
use RZP\Models\SalesForce\SalesForceEventRequestType;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Mail\BankingAccount\DocketMail\DocketMail;
use RZP\Models\BankingAccountService\Service as BasService;
use RZP\Services\BankingAccountService as BAS;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Detail as BankingAccountDetail;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;
use \RZP\Models\Merchant\Attribute\Type as MerchantAttributeType;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\PushNotifications\CurrentAccount\StatusUpdate as StatusUpdatePN;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;
use RZP\Models\BankingAccountStatement\Details\Core as BankingAccountStatementDetailsCore;
use RZP\Models\BankingAccount\Activation as Activation;

class Core extends Base\Core
{
    const GATEWAY   = 'gateway';
    const PROCESSOR = 'processor';

    const DEFAULT_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = 1000;

    // Limits for gateway balance update for CA.
    const CA_BALANCE_UPDATE_TIME_LIMIT           = 'ca_balance_update_time_limit';
    const CA_BALANCE_UPDATE_RATE_LIMIT           = 'ca_balance_update_rate_limit';
    const CA_MANDATORY_BALANCE_UPDATE_RATE_LIMIT = 'ca_mandatory_balance_update_rate_limit';

    const DEFAULT_CA_BALANCE_UPDATE_LIMITS = [
        self::CA_BALANCE_UPDATE_TIME_LIMIT           => 1800,
        self::CA_BALANCE_UPDATE_RATE_LIMIT           => 100,
        self::CA_MANDATORY_BALANCE_UPDATE_RATE_LIMIT => 150
    ];

    // Different rules used in gateway balance update for CA.
    const MADE_PAYOUT_RULE = 'made_payout';
    const BALANCE_CHANGE_RULE = 'balance_change';
    const MANDATORY_UPDATE_RULE = 'mandatory_update_rule';

    // Values for default Fee Recovery Schedule
    const DEFAULT_SCHEDULE_PERIOD   = Period::DAILY;
    const DEFAULT_SCHEDULE_INTERVAL = 7;

    /** @var ActivationDetail\Service $activationDetailService */
    protected $activationDetailService;

    /**
     * @var Notifier
     */
    protected $notifier;

    public static $notificationStatuses = [
        Status::PICKED,
        Status::INITIATED,
        Status::PROCESSING,
        Status::CANCELLED,
        Status::ACTIVATED,
        Status::UNSERVICEABLE,
        Status::REJECTED,
        Status::ARCHIVED,
    ];

    const PICKED_PN_TITLE           = "Your a/c is getting ready 🚀";
    const INITIATED_PN_TITLE        = "KYC: One step closer!";
    const PROCESSING_PN_TITLE       = "KYC in progress! 🕐";
    const CANCELLED_PN_TITLE        = "We're sad to see you go 😔";
    const ACTIVATED_PN_TITLE        = "Your current a/c is ready 🎉";
    const UNSERVICEABLE_PN_TITLE    = "We can’t open your account 😔";
    const REJECTED_PN_TITLE         = "KYC not approved 🙁";

    const PICKED_PN_BODY            = "Our team will call you for KYC docs. See you on board soon! 🤩";
    const INITIATED_PN_BODY         = "Your application has been sent to the bank for KYC verification 🙂";
    const PROCESSING_PN_BODY        = "Our partner bank may call you for clarifications if needed.";
    const CANCELLED_PN_BODY         = "We've cancelled your account application as you asked.";
    const ACTIVATED_PN_BODY         = "Your account is now active. Welcome to the future of banking! 🚀";
    const UNSERVICEABLE_PN_BODY     = "Your current location can't be serviced by our partner bank.️";
    const REJECTED_PN_BODY          = "Oh no! Our partner bank has not approved your KYC documents.️";

    public static $statusSubstatusUpdatePnContentMap = [
        Status::PICKED	        =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::PICKED_PN_TITLE,
                self::PICKED_PN_BODY
            ],
        ],
        Status::INITIATED	    =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::INITIATED_PN_TITLE,
                self::INITIATED_PN_BODY
            ],
        ],
        Status::PROCESSING	    =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::PROCESSING_PN_TITLE,
                self::PROCESSING_PN_BODY
            ],
        ],
        Status::ACCOUNT_OPENING	    =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::PROCESSING_PN_TITLE,
                self::PROCESSING_PN_BODY
            ],
        ],
        Status::CANCELLED	    =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::CANCELLED_PN_TITLE,
                self::CANCELLED_PN_BODY
            ],
        ],
        Status::ACTIVATED	    =>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::ACTIVATED_PN_TITLE,
                self::ACTIVATED_PN_BODY
            ],
        ],
        Status::UNSERVICEABLE	=>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::UNSERVICEABLE_PN_TITLE,
                self::UNSERVICEABLE_PN_BODY
            ],
        ],
        Status::REJECTED    	=>	[
            Status::ALL_SUBSTATUSES       =>  [
                self::REJECTED_PN_TITLE,
                self::REJECTED_PN_BODY
            ],
        ],
        Status::ARCHIVED        => [
            Status::CANCELLED => [
                self::CANCELLED_PN_TITLE,
                self::CANCELLED_PN_BODY,
            ],
            Status::NOT_SERVICEABLE => [
                self::UNSERVICEABLE_PN_TITLE,
                self::UNSERVICEABLE_PN_BODY,
            ],
            Status::NEGATIVE_PROFILE_SVR_ISSUE => [
                self::REJECTED_PN_TITLE,
                self::REJECTED_PN_BODY,
            ],
        ]
    ];

    public static $directChannelsForConnectBanking = [
        Channel::YESBANK,
        Channel::AXIS,
        Channel::ICICI,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');

        $this->activationDetailService =  resolve(Activation\Detail\Service::class);

        $this->notifier = new Notifier;
    }

    public static function getStatusUpdatePushNotificationContent($status, $substatus)
    {
        $notificationContent = self::$statusSubstatusUpdatePnContentMap[$status];

        if (array_key_exists($substatus, $notificationContent) === true)
        {
            $notificationContent = $notificationContent[$substatus];
        }
        else if (array_key_exists(Status::ALL_SUBSTATUSES, $notificationContent) === true)
        {
            $notificationContent = $notificationContent[Status::ALL_SUBSTATUSES];
        }
        else {
            $notificationContent = null;
        }

        if ($notificationContent === null)
        {
            $notificationContent = [null, null];
        }

        return $notificationContent;
    }

    public function createOrFetchSharedBankingAccountFromVA(VirtualAccount\Entity $virtualAccount): array
    {
        // Virtual account has to be with receiver_type bank account
        if ($virtualAccount->hasBankAccount() === false)
        {
            throw new LogicException(
                'Banking accounts can only be create on bank type virtual accounts',
                null,
                ['virtual_account_id' => $virtualAccount->getId()]);
        }

        $bankAccount = $virtualAccount->bankAccount;
        $bankCode    = $bankAccount->getBankCode();
        $channel     = $this->isLiveMode() ?
            array_flip(VirtualAccount\Provider::IFSC)[$bankAccount->getIfscCode()] : Channel::YESBANK;

        $allowedChannels = BAChannel::getAllowedSharedChannels();
        $isChannelValid  = (in_array($channel, $allowedChannels) === true);

        //
        // Only whitlisted bank accounts are allowed as shared banking accounts on live mode, for now
        // For test mode we create the accounts using bt_dashboard terminal
        // In case of test mode, the bank code will be `RAZR`
        //
        if (($this->isLiveMode() === true) and
            ($isChannelValid === false))
        {
            throw new LogicException(
                'Only whitelisted channels on virtual accounts are supported',
                null,
                [
                    'bank_code'         => $bankCode,
                    'channel'           => $channel,
                    'allowed_channel'   => $allowedChannels,
                ]);
        }

        $balanceId = $virtualAccount->getBalanceId();

        //
        // If a banking_account already exists for a balance_id, return that instead
        // of creating a new one.
        //
        $existingBankingAcc = $this->repo->banking_account->getFromBalanceId($balanceId);

        if ($existingBankingAcc !== null)
        {
            return [$existingBankingAcc, false];
        }

        $bankingAccountInput = [
            Entity::ACCOUNT_IFSC              => $bankAccount->getIfscCode(),
            Entity::CHANNEL                   => $channel,
            Entity::ACCOUNT_NUMBER            => $bankAccount->getAccountNumber(),
            Entity::FTS_FUND_ACCOUNT_ID       => $bankAccount->getFtsFundAccountId(),
            Entity::ACCOUNT_TYPE              => AccountType::NODAL, // TODO: check how to figure out CA here, maybe change to Pool?
            Entity::STATUS                    => Status::CREATED,
            Entity::BENEFICIARY_EMAIL         => $bankAccount->getBeneficiaryEmail(),
            Entity::BENEFICIARY_MOBILE        => $bankAccount->getBeneficiaryMobile(),
            Entity::BENEFICIARY_CITY          => $bankAccount->getBeneficiaryCity(),
            Entity::BENEFICIARY_STATE         => $bankAccount->getBeneficiaryState(),
            Entity::BENEFICIARY_COUNTRY       => $bankAccount->getBeneficiaryCountry(),
            Entity::BENEFICIARY_NAME          => $bankAccount->getBeneficiaryName(),
            Entity::BENEFICIARY_ADDRESS1      => $bankAccount->getBeneficiaryAddress1(),
            Entity::BENEFICIARY_ADDRESS2      => $bankAccount->getBeneficiaryAddress2(),
            Entity::BENEFICIARY_PIN           => $bankAccount->getBeneficiaryPin(),
            Entity::BENEFICIARY_ADDRESS3      => $bankAccount->getBeneficiaryAddress3() . ' ' .
                                                 $bankAccount->getBeneficiaryAddress4(),
        ];

        $bankingAccount = $this->createSharedBankingAccount($bankingAccountInput,
                                                            $virtualAccount->merchant,
                                                            $virtualAccount->balance);

        $merchantId = $virtualAccount->merchant;

        $sharedBankingAccounts = $this->repo->banking_account->fetchByMerchantIdAndAccountType($merchantId,
                                                                                    Balance\AccountType::SHARED);

        if ($sharedBankingAccounts->count() > 1)
        {
            throw new LogicException(
                'More than 1 shared virtual account can not be created for X',
                null,
                [
                    'merchant_id'       => $merchantId,
                    'count'             => count($sharedBankingAccounts),
                ]);
        }

        return [$bankingAccount, true];
    }

    /**
     * This email is sent to ops to notify them about the interest merchant has shown in
     * X Pro plan, currently that is RBL current account
     *
     * @param array $bankingAccount
     */
    public function notifyOpsAboutProActivation(array $bankingAccount)
    {
        try
        {
            $activationDetail = $bankingAccount[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];

            $reviewer = ['reviewer_name' => ""];

            if ($activationDetail != null)
            {
                $reviewer = ['reviewer_name' => $activationDetail[Activation\Detail\Entity::ASSIGNEE_NAME] ?? ''];
            }

            /** @var Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->findOrFail($bankingAccount[Entity::MERCHANT_ID]);

            $merchantArr = ['merchant' => $merchant->toArray()];

            $mailer = new XProActivation(array_merge($bankingAccount, $reviewer, $merchantArr));

            Mail::queue($mailer);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_X_PRO_ACTIVATION_NOTIFICATION,
                [
                    'banking_account_id' => $bankingAccount[Entity::ID],
                    'merchant_id'        => $bankingAccount[Entity::MERCHANT_ID],
                    'status'             => $bankingAccount[Entity::STATUS],
                    'message'            => 'Mail Sent'
                ]);

            $this->app['diag']->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_FRESHDESK_TICKET_CREATE, $merchant, null, [
                'banking_account_id' => $bankingAccount[Entity::ID],
                'status'             => $bankingAccount[Entity::STATUS]
            ]);

        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_X_PRO_ACTIVATION_NOTIFICATION_FAILED,
                [
                    'banking_account_id' => $bankingAccount[Entity::ID],
                    'merchant_id'        => $bankingAccount[Entity::MERCHANT_ID],
                    'status'             => $bankingAccount[Entity::STATUS],
                    'error'              => $e->getMessage(),
                ]);
        }
    }

    public function notifyMerchantAboutUpdatedStatus(array $bankingAccount)
    {
        // Product has asked to pause merchant notifications

//        try
//        {
//            $mailer = StatusUpdateMailerFactory::getMailer($bankingAccount);
//
//            Mail::queue($mailer);
//
//            $this->trace->info(
//                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION,
//                [
//                    'banking_account_id' => $bankingAccount[Entity::ID],
//                    'merchant_id'        => $bankingAccount[Entity::MERCHANT_ID],
//                    'status'             => $bankingAccount[Entity::STATUS],
//                    'message'            => 'Mail Sent'
//                ]);
//        }
//        catch(\Exception $e)
//        {
//            $this->trace->traceException(
//                $e,
//                Trace::ERROR,
//                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION_FAILED,
//                [
//                    'banking_account_id' => $bankingAccount[Entity::ID],
//                    'merchant_id'        => $bankingAccount[Entity::MERCHANT_ID],
//                    'status'             => $bankingAccount[Entity::STATUS],
//                    'error'              => $e->getMessage(),
//                ]);
//        }
    }

    public function notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification(array $bankingAccount)
    {
        $status = $bankingAccount[Entity::STATUS];

        $substatus = $bankingAccount[Entity::SUB_STATUS] ?? null;

        $statusList = self::$notificationStatuses;

        if (in_array($status, $statusList, true) === false)
        {
            return;
        }

        $notificationContent = self::getStatusUpdatePushNotificationContent($status, $substatus);

        $pushNotificationTitle = $notificationContent[0];

        $pushNotificationBody =  $notificationContent[1];

        if (empty($pushNotificationTitle) || empty($pushNotificationBody))
        {
            return;
        }

        $pushNotificationTag = "ca_onboarding_" .  $status;

        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFail($bankingAccount[Entity::MERCHANT_ID]);

        $users = $merchant->ownersAndAdmins(Product::BANKING);

        foreach ($users as $user)
        {
            $userId = $user->getId();

            $notificationData = array(
                'ownerId'       => $bankingAccount[Entity::MERCHANT_ID],
                'ownerType'     => 'merchant',
                'title'         => $pushNotificationTitle,
                'body'          => $pushNotificationBody,
                'status'        => $status,
                'identityList'  => [$userId],
                'tags'          => array(
                    'merchantId'            => $bankingAccount[Entity::MERCHANT_ID],
                    'userId'                => $userId,
                    'notificationPurpose'   => $pushNotificationTag,
                ),
                'tagGroup'      => $pushNotificationTag,
            );

            try
            {
                $pushNotification = new StatusUpdatePN($notificationData);
                $pushNotification->send();
                $this->trace->info(TraceCode::PUSH_NOTIFICATION_DISPATCHED_FOR_CA_STATUS_UPDATE, [$notificationData]);
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::INFO,
                    TraceCode::PUSH_NOTIFICATION_FAILURE_FOR_CA_STATUS_UPDATE,
                    [$notificationData]
                );
            }
        }
    }

    public function extractAndValidateActivationDetailInput(array &$input, $entity = null, bool $fromPartnerDashboard = false)
    {
        if (isset($input['activation_detail']) === true)
        {
            $auth = $this->app['basicauth'];

            // if comment is passed and updater entity is merchant, can't add comment as
            // commenter is figured out from admin, except from Partner LMS.
            // If the caller is master-onboarding, we allow addition of comment
            if ((empty($entity) === false)
                and (isset($input['activation_detail'][ActivationDetail\Entity::COMMENT]) === true)
                and ($fromPartnerDashboard !== true && $entity->getEntity() !== 'admin')
                and $this->app['basicauth']->isMobApp() === false)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_DETAILS_ONLY_ON_ADMIN_AUTH);
            }

            $activationDetailInput = array_pull($input, 'activation_detail');

            return $activationDetailInput;
        }

        return null;
    }

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param array|null      $activationDetailInput
     * @param string          $validatorOp
     *
     * @return Entity
     * @throws BadRequestException
     * @throws LogicException
     */
    public function createBankingAccount(array $input, Merchant\Entity $merchant, ?array $activationDetailInput, string $validatorOp): Entity
    {
        $channel = $input[Entity::CHANNEL];

        $clarityContextInput = array_pull($input,Entity::CLARITY_CONTEXT);

        $clarityContextEnabled = !empty($clarityContextInput) and $clarityContextInput === '1';

        // Currently we are just checking if there exists even one account of the merchant for the selected
        // channel. If we find any such account we will just return the account and wont create a new one.
        // But later when a merchant will start having more than one current account in the same channel
        // this logic will have to be handled.

        $bankingAccount = $this->repo->banking_account->getBankingAccountOfMerchant($merchant, $channel);

        if ($bankingAccount !== null)
        {
            // Throwing an error here in case admin dashboard user
            // attempts to create another BankingAccount for same MID, channel.
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_EXISTS,
                Entity::MERCHANT_ID,
                [
                    'merchant_id' => $merchant->getPublicId(),
                    'banking_account_id' => $bankingAccount->getPublicId()
                ],
                'Current Account already exists for MID on channel '. $channel);
        }

        $bankingAccount = new Entity;

        $processor = $this->getProcessor($channel);

        $bankContent = $processor->validateAndPreProcessInputForAccountCreation($input);

        $input = array_merge($input, $bankContent);

        // we want the setStatus method to handle all the status validations
        // also we might add logic around updating other columns based on
        // change of status. So moving status out of input and explicitly
        // calling setStatus

        array_pull($input, Entity::STATUS);

        (new Validator)->validateInput($channel . 'Create', $input);

        $input[Entity::ACCOUNT_TYPE] = AccountType::CURRENT;

        $bankingAccount->build($input);

        $bankingAccount->setStatus($bankContent[Entity::STATUS]);

        $bankingAccount->merchant()->associate($merchant);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ENTITY_CREATED,
            [
                $bankingAccount->toArray(),
            ]);

        $this->repo->transaction(function() use ($bankingAccount, $merchant, $bankContent, $activationDetailInput, $validatorOp)
        {
            $this->repo->saveOrFail($bankingAccount);

            if ($activationDetailInput !== null)
            {
                $this->activationDetailService->createForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput, $validatorOp);
            }

            $stateCore = new State\Core;

            $stateCore->captureNewBankingAccountState($bankingAccount, $merchant);

        });

        if ($clarityContextEnabled === true)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_CLARITY_CONTEXT_ENABLED,
                [
                    'merchant_id'               => $merchant->getId(),
                    'clarity_context_enabled'   => true,
                ]);

            $this->sendClarityContextEnabledEventToSF($merchant);
        }

        $this->shouldNotifyOpsAboutProActivation($validatorOp, $bankingAccount->toArray(), $clarityContextEnabled);

        $this->sendSegmentEvent($bankingAccount->toArray(), $merchant);

        return $bankingAccount;
    }

    protected function isAccountInfoWebhookAlreadyProcessed(Entity $bankingAccount)
    {
        return $bankingAccount->isAccountActivationDateFilled();
    }

    protected function handleDuplicateWebhook($input, $channel, Entity $bankingAccount)
    {
        $this->trace->info(
            TraceCode::DUPLICATE_ACCOUNT_INFO_WEBHOOK,
            [
                'input'     => $input,
                'channel'   => $channel,
                'rzp_ref_no'=> $bankingAccount->getBankReferenceNumber()
            ]);

        // If we receive duplicate webhook, we want to throw an error, and
        // subsequently return `Failure` in the response.
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_WEBHOOK_ALREADY_PROCESSED,
            null,
            [
                'rzp_ref_no' => $bankingAccount->getBankReferenceNumber(),
                'channel'    => $channel,
            ],
            'Webhook already processed for RZP Ref No: '. $bankingAccount->getBankReferenceNumber()
        );
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_INFO_WEBHOOK_REQUEST,
            [
                'input'         => $input,
                'gateway'       => $channel,
            ]);

        Channel::validate($channel);

        $processor = $this->getProcessor($channel);

        $bankingAccount = null;
        try
        {
            $processor->preProcessAccountInfoNotification($input);

            $attributes = $processor->processAccountInfoNotification($input);

            $bankingAccount = $this->getBankingAccountByBankReferenceAndChannel($channel, $attributes[Entity::BANK_REFERENCE_NUMBER]);

            if ($bankingAccount === null)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                    [
                        'input'   => $input,
                        'route'   => $this->app['router']->currentRouteName(),
                    ]);

                return (new BasService())->processAccountOpeningWebhookForRbl($input);
            }

            $this->handleStateFromAccountOpeningWebhook($bankingAccount, $attributes);

            // if data validation of pincode or business beneficiary name failed, trigger the internal email
            if($this->isDataValidForBankingAccount($bankingAccount, $attributes[Entity::BENEFICIARY_PIN], $attributes[Entity::BENEFICIARY_NAME]) === false)
            {
                // trigger the internal email
                $eventProperties = [
                    Entity::BENEFICIARY_NAME            => $attributes[Entity::BENEFICIARY_NAME],
                    Entity::BENEFICIARY_PIN             => $attributes[Entity::BENEFICIARY_PIN],
                    Entity::BENEFICIARY_CITY            => $attributes[Entity::BENEFICIARY_CITY],
                    Entity::BENEFICIARY_ADDRESS1        => $attributes[Entity::BENEFICIARY_ADDRESS1] . ' ' . $attributes[Entity::BENEFICIARY_ADDRESS2] . ' ' . $attributes[Entity::BENEFICIARY_ADDRESS3],
                    Entity::BANK_REFERENCE_NUMBER       => $attributes[Entity::BANK_REFERENCE_NUMBER],
                    Entity::BENEFICIARY_EMAIL           => $attributes[Entity::BENEFICIARY_EMAIL],
                    Entity::BENEFICIARY_MOBILE          => $attributes[Entity::BENEFICIARY_MOBILE]
                ];
                $this->notifier->notify($bankingAccount->toArray(), Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY, Event::ALERT, $eventProperties);
            }

            if ($this->isAccountInfoWebhookAlreadyProcessed($bankingAccount) === true)
            {
                $this->handleDuplicateWebhook($input, $channel, $bankingAccount);
            }

            $this->checkIfAccountNumberWithChannelAlreadyExists($attributes[Entity::ACCOUNT_NUMBER], $channel);

            $this->updateBankingAccount($bankingAccount, $attributes, $bankingAccount->merchant, true);

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::PROCESSED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $this->trace->error(TraceCode::BANKING_ACCOUNT_ACCOUNT_INFO_WEBHOOK_FAILURE,
                [
                    'input'          => $input,
                    'failure_reason' => $e->getMessage()
                ]);

            $processor->postProcessNotifyWebhookFailureToOps($input,$e->getMessage(),$bankingAccount);

            $response = $processor->postProcessAccountInfoNotificationResponse($input, Status::CANCELLED);
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_INFO_WEBHOOK_RESPONSE,
            [
                'response'  => $response,
                'gateway'   => $channel,
            ]);

        return $response;
    }

    public function checkIfAccountNumberWithChannelAlreadyExists(string $bankAccountNumber, string $channel)
    {
        $bankingAccount =  $this->repo->banking_account->fetchByAccountNumberAndChannel($bankAccountNumber, $channel);

        if($bankingAccount != null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_EXIST_WITH_THE_CHANNEL_ASSOCIATED,
                null,
                [
                    'account_number' => $bankingAccount->getAccountNumber(),
                    'channel'        => $bankingAccount->getChannel(),
                ],
                'Account Number for the channel: ' . $bankingAccount->getChannel() . ' sent in the payload is already present in our system.'
            );
        }
    }

    public function fetchByBankReferenceAndChannel(string $channel, string $bankReference)
    {
        $bankingAccount = $this->getBankingAccountByBankReferenceAndChannel($channel, $bankReference);

        if ($bankingAccount === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_WEBHOOK_INCORRECT_RZP_REF_NO,
                null,
                [
                    'rzp_ref_no' => $bankReference,
                    'channel'    => $channel,
                ],
                'No records found for Channel:' . $channel . ', with RZP Ref No: '. $bankReference
            );
        }

        return $bankingAccount;
    }

    public function getBankingAccountByBankReferenceAndChannel(string $channel, string $bankReference)
    {
        return $this->repo
                    ->banking_account
                    ->findByBankReferenceAndChannel($channel, $bankReference);
    }

    /**
     * input =
     * {
     *      'status' : 'processing',
     *      'sub_status' : 'merchant_not_available',
     *      'activation_detail' : {
     *              'assignee_team' : 'ops',
     *              'comment' : {
     *                  'comment' : 'sample comment',
     *                  'type' : 'external',
     *              }
     *      }
     * }
     *
     * @param Entity                 $bankingAccount
     * @param array                  $input
     * @param Base\PublicEntity|null $entity
     * @param bool                   $isAutomatedUpdate
     * @param bool                   $fromDashboard
     *
     * @return Entity
     * @throws BadRequestException
     * @throws LogicException
     */
    public function updateBankingAccount(
        Entity $bankingAccount,
        array $input,
        Base\PublicEntity $entity = null,
        bool $isAutomatedUpdate = false,
        bool $fromDashboard = false,
        bool $fromPartnerDashboard = false
    )
    {
        $channel = $bankingAccount->getChannel();

        $traceRequest = $input;

        // details array may contain sensitive information
        // like merchant password and other gateway specific
        // fields. These will be handled by the gateway module
        // hence redacting from here.
        unset($traceRequest[Entity::DETAILS]);
        unset($traceRequest[Entity::PASSWORD]);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $traceRequest,
            ]);

        // banking_account update is not permitted for terminated status
        (new Validator())->validateAccountNotTerminated($bankingAccount, ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_UPDATE_NOT_PERMITTED);

        $isRevivedLead = $this->checkIfRevivedLead($input,$bankingAccount);

        if ($isRevivedLead === true)
        {
            $this->updateRevivedFlag($input);
        }

        $this->resetFieldsForSentToBank($input);

        $this->updateInputAssigneeTeamBasedOnStatusOrSubStatusChange($bankingAccount, $input, $isAutomatedUpdate);

        $activationDetailInput = $this->extractAndValidateActivationDetailInput($input, $entity, $fromPartnerDashboard);

        $this->validateAndSetDropOffDate($bankingAccount, $activationDetailInput, $input);

        $processor = $this->getProcessor($channel);

        $processor->validateAccountBeforeUpdating($input);

        $input = $processor->formatInputParametersIfRequired($input);

        $oldStatus = $bankingAccount->getStatus();

        if($fromDashboard === true)
        {
            $bankingAccount->edit($input, 'edit_Dashboard');
        }
        else
        {
            $bankingAccount->edit($input);
        }

        // we need to store change log only when the
        // status has changed.
        $bankInternalStatusChanged = $bankingAccount->isDirty(Entity::BANK_INTERNAL_STATUS);

        $bankingAccountStatusChanged = $bankingAccount->isDirty(Entity::STATUS);

        $bankingAccountSubStatusChanged = $bankingAccount->isDirty(Entity::SUB_STATUS);

        // Setting Status
        if (empty($input[Entity::STATUS]) === false)
        {
            $bankingAccount->setStatus($input[Entity::STATUS]);

            // if actual update in status is happening, and substatus is not passed,
            // pick default substatus
            if (($bankingAccountStatusChanged === true) and (array_key_exists(Entity::SUB_STATUS, $input) === false))
            {
                $input[Entity::SUB_STATUS] = Status::getDetaultSubStatus($input[Entity::STATUS]);
            }
        }

        // Setting Substatus
        // Not using empty because empty(NULL)=true and NULL is a valid value.
        // Not using isset here because  isset will return false if array('key'=>NULL) and NULL is a valid value.
        // (sub_status can be set/defaulted to null for some statuses)
        if (array_key_exists(Entity::SUB_STATUS, $input) === true)
        {
            $bankingAccount->setSubStatus($input[Entity::SUB_STATUS]);
        }

        // Validating Bank Internal Status
        if (empty($input[Entity::BANK_INTERNAL_STATUS]) === false)
        {
            $processor->validateStatusMapping($input[Entity::BANK_INTERNAL_STATUS], $bankingAccount->getStatus(), $bankingAccount->getSubStatus());

            $bankingAccount->setBankInternalStatus($input[Entity::BANK_INTERNAL_STATUS]);
        }

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        $admin = $admin !== null ? $admin : $this->getAdminFromHeadersForMobApp();

        (new Validator())->validateUpdatePermissions($bankingAccount, $admin);

        $this->repo->transaction(function()
            use ($bankingAccount,
                $input,
                $processor,
                $activationDetailInput,
                $entity,
                $bankingAccountStatusChanged,
                $bankInternalStatusChanged,
                $bankingAccountSubStatusChanged,
                $isAutomatedUpdate,
                $fromDashboard,
                $fromPartnerDashboard)
        {
            // Updating BankingAccount
            $this->repo->saveOrFail($bankingAccount);

            // Updating BankingAccountDetails
            if ((isset($input[Entity::DETAILS]) === true) and
                (empty($input[Entity::DETAILS])) === false)
            {
                if (in_array($bankingAccount->getStatus(), Status::$allowedStatusForDetails, true) === false)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_DETAILS_UPDATE_NOT_ALLOWED_ON_CURRENT_STATUS,
                        null,
                        [
                            BankingAccountDetail\Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId(),
                            Entity::STATUS                                  => $bankingAccount->getStatus(),
                        ],
                        'Account Details cannot be saved for given account status');
                }

                (new BankingAccountDetail\Core)->updateBankingAccountDetails($input[Entity::DETAILS],
                                                                             $bankingAccount,
                                                                             $processor);
            }

            $isAssigneeChanged = $this->isAssigneeTeamChanged($activationDetailInput, $bankingAccount->getId());

            // Updating BankingAccountActivation Details
            if (empty($activationDetailInput) === false)
            {
                // if ActivationDetail is passed with comment in input, entity will always be admin, not merchant.
                $this->activationDetailService->updateForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput, $isAutomatedUpdate, $entity,false);
            }

            if (($bankInternalStatusChanged === true) or
                ($bankingAccountStatusChanged === true) or
                ($bankingAccountSubStatusChanged === true) or
                ($isAssigneeChanged === true))
            {
                $stateCore = new State\Core;

                $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccount->getPublicId());

                $stateCore->captureNewBankingAccountState($bankingAccount, $entity);

                if($isAssigneeChanged === true)
                {
                    $this->notifier->notify($bankingAccount->toArray(), Event::ASSIGNEE_CHANGE, Event::ALERT);
                }
            }

            $this->notifyIfStatusChanged($bankingAccount->toArray(), $bankingAccountStatusChanged, $bankingAccountSubStatusChanged);

            // storing state change
            if (($bankInternalStatusChanged === true) or
                ($bankingAccountStatusChanged === true) or
                ($bankingAccountSubStatusChanged === true))
            {

                // This block of code changes status and substatus to
                // to next status and default sub-status in normal sequence
                // if we are changing sub-status to a terminal sub-status
                if (
                    array_key_exists(Entity::SUB_STATUS, $input) === true &&
                    empty($input[Entity::SUB_STATUS]) === false
                )
                {

                    $subStatus = $input[Entity::SUB_STATUS];
                    $status = $bankingAccount->getStatus();

                    if (empty($input[Entity::STATUS]) === false)
                    {
                        $status = $input[Entity::STATUS];
                    }

                    if (
                        Status::hasReachedTerminalSubStatus($status, $subStatus) === true &&
                        empty(Status::getNextStatusInSequence($status)) === false
                    )
                    {
                        $status = Status::getNextStatusInSequence(($status));
                        $subStatus = Status::getInitialSubStatus($status);

                        if (empty($status) === false && empty($subStatus) === false)
                        {
                            $nextInput = [
                                Entity::STATUS => $status,
                                Entity::SUB_STATUS => $subStatus,
                            ];

                            $bankingAccount = $this->updateBankingAccount($bankingAccount, $nextInput, $entity, $isAutomatedUpdate, $fromDashboard, $fromPartnerDashboard);
                        }
                    }
                }
            }

            $activationDetailInput = $this->setBankDueDateIfApplicable($bankingAccount);

            if (empty($activationDetailInput) === false)
            {
                // Again updating since the calculation of due date is dependent on banking account's latest data
                $this->activationDetailService->updateForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput, $isAutomatedUpdate, $entity, false);
            }
        });

        // re-fetch banking-account to handle case where it is updated during freshdeskticket creation
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccount->getPublicId());

        // We need to populate banking account details using
        // toArrayPublic, which populates only the pre fetched
        // relations. So explicitly fetching this relation here
        $bankingAccount->load('bankingAccountDetails');

        $bankingAccount->load('bankingAccountActivationDetails');

        return $bankingAccount;
    }

    protected function resetFieldsForSentToBank(&$input)
    {
        if (!(isset($input[Entity::STATUS]) &&
            $input[Entity::STATUS] === Status::INITIATED &&
            isset($input[Entity::SUB_STATUS]) &&
            $input[Entity::SUB_STATUS] === Status::NONE))
        {
            return;
        }

        $input[Entity::ACTIVATION_DETAIL] = [];

        $input[Entity::ACTIVATION_DETAIL] = array_merge($input[Entity::ACTIVATION_DETAIL], [

                ActivationDetail\Entity::ACCOUNT_LOGIN_DATE => null,
                ActivationDetail\Entity::ACCOUNT_OPEN_DATE => null,
                ActivationDetail\Entity::DOC_COLLECTION_DATE => null,
                ActivationDetail\Entity::ACCOUNT_OPENING_IR_CLOSE_DATE => null,
                ActivationDetail\Entity::ACCOUNT_OPENING_FTNR => null,
                ActivationDetail\Entity::ACCOUNT_OPENING_FTNR_REASONS => null,
                ActivationDetail\Entity::LDAP_ID_MAIL_DATE => null,
                ActivationDetail\Entity::API_IR_CLOSED_DATE => null,
                ActivationDetail\Entity::DROP_OFF_DATE => null,
                ActivationDetail\Entity::RZP_CA_ACTIVATED_DATE => null,
                ActivationDetail\Entity::API_ONBOARDING_FTNR => null,
                ActivationDetail\Entity::RM_PHONE_NUMBER => null,
                ActivationDetail\Entity::RM_NAME => null,
                ActivationDetail\Entity::RM_EMPLOYEE_CODE => null,
                ActivationDetail\Entity::API_ONBOARDING_FTNR_REASONS => null,
                ActivationDetail\Entity::BRANCH_CODE => null,
                ActivationDetail\Entity::UPI_CREDENTIAL_RECEIVED_DATE => null,

                ActivationDetail\Entity::ADDITIONAL_DETAILS => [

                    ActivationDetail\Entity::MID_OFFICE_POC_NAME => null,
                    ActivationDetail\Entity::API_ONBOARDING_LOGIN_DATE => null,
                    ActivationDetail\Entity::API_ONBOARDED_DATE => null,
                    ActivationDetail\Entity::ACCOUNT_OPENING_WEBHOOK_DATE => null,
                ],

                ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [

                    ActivationDetail\Entity::ACCOUNT_OPENING_IR_NUMBER => null,
                    ActivationDetail\Entity::LEAD_IR_NUMBER => null,
                    ActivationDetail\Entity::SR_NUMBER => null,
                    ActivationDetail\Entity::API_IR_NUMBER => null,
                    ActivationDetail\Entity::UPI_CREDENTIAL_NOT_DONE_REMARKS => null,
                    ActivationDetail\Entity::PROMO_CODE => null,
                    ActivationDetail\Entity::IP_CHEQUE_VALUE => null,
                    ActivationDetail\Entity::LEAD_REFERRED_BY_RBL_STAFF => null,
                    ActivationDetail\Entity::OFFICE_DIFFERENT_LOCATIONS => null,
                    ActivationDetail\Entity::API_DOCS_RECEIVED_WITH_CA_DOCS => null,
                    ActivationDetail\Entity::REVISED_DECLARATION => null,
                    ActivationDetail\Entity::CASE_LOGIN_DIFFERENT_LOCATIONS => null,
                    ActivationDetail\Entity::API_DOCS_DELAY_REASON => null,
                    ActivationDetail\Entity::BANK_POC_ASSIGNED_DATE => null,
                    ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION => null,
                    ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION_REASON => null,
                    ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION => null,
                    ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION_REASON => null,
                    ActivationDetail\Entity::BANK_DUE_DATE => null
                ]
            ]
        );
    }

    public function updateRevivedFlag(&$input)
    {
        $input[Entity::ACTIVATION_DETAIL]
            [ActivationDetail\Entity::ADDITIONAL_DETAILS]
                [ActivationDetail\Entity::REVIVED_LEAD] = true;

    }

    public function checkIfRevivedLead($input, $bankingAccount)
    {

        // current state should be archived and new state should be either Sent To Bank or Razorpay Processing

        if($bankingAccount->getStatus() != Status::ARCHIVED)
        {
            return false;
        }

        if (isset($input[Entity::STATUS]) === false)
        {
            return false;
        }

        if(Status::checkStatusForRevival($input[Entity::STATUS]) === false)
        {
            return false;
        }

        $sendToBankState = $this->repo->banking_account_state->getFirstStatusChangeLog($bankingAccount->getId(), Status::INITIATED);

        // any of the previous states should be Sent To Bank

        if($sendToBankState != null)
        {
            return true;
        }

        return false;

    }

    private function isAssigneeTeamChanged($activationDetailInput, $bankingAccountId): bool
    {

        if (empty($activationDetailInput) === true)
            return false;

        $isAssigneeChanged = false;

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccountId);

        $inputHasAssigneeTeam = isset($activationDetailInput[ActivationDetail\Entity::ASSIGNEE_TEAM]);

        // if input has assignee team and no assignee team was there previously
        if ($activationDetail == null) {
            if ($inputHasAssigneeTeam) {

                $isAssigneeChanged = true;
            }
        } else if ($inputHasAssigneeTeam) {

            $newAssigneeTeam = $activationDetailInput[ActivationDetail\Entity::ASSIGNEE_TEAM];
            // input has assignee team and is different from the previous one
            if ($newAssigneeTeam != $activationDetail->getAssigneeTeam()) {

                $isAssigneeChanged = true;
            }
        }

        return $isAssigneeChanged;

    }

    /**
     * Inject assignee team into input automatically on status or sub-status change
     *
     * @param array $input
     */
    private function updateInputAssigneeTeamBasedOnStatusOrSubStatusChange(Entity $bankingAccount, array & $input, bool & $isAutomatedUpdate)
    {
        // skip if manually changing assignee team
        if (empty($input[Entity::ASSIGNEE_TEAM]) === false)
        {
            return;
        }

        $changingStatus = empty($input[Entity::STATUS]) === false;
        $changingSubstatus = empty($input[Entity::SUB_STATUS]) === false;

        // automatic assignee change happens only on status or sub-status change
        if ($changingStatus === false && $changingSubstatus ===  false)
        {
            return;
        }

        $newStatus = array_key_exists(Entity::STATUS, $input) ? $input[Entity::STATUS] : $bankingAccount->getStatus();
        $newSubStatus = array_key_exists(Entity::SUB_STATUS, $input) ? $input[Entity::SUB_STATUS] : Status::getInitialSubStatus($newStatus);

        $newAssigneeTeam = Status::getDefaultAssigneeTeam($newStatus, $newSubStatus);

        if ($newAssigneeTeam === false)
        {
            return;
        }

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        if ($newAssigneeTeam != $activationDetail[Entity::ASSIGNEE_TEAM])
        {
            $input[Entity::ACTIVATION_DETAIL][Entity::ASSIGNEE_TEAM] = $newAssigneeTeam;
            $isAutomatedUpdate = true;
        }
    }

    public function checkAndSendFreshDeskEmailIfFormIsSubmitted(Entity $bankingAccount, array $activationDetailInput)
    {
        /** @var ActivationDetail\Entity $activationDetail */
        $activationDetail = $bankingAccount->bankingAccountActivationDetails;

        $caOnboardingFlow = null;

        try
        {
            // This has been added so that sales_pitch_completed check can be
            // applied for older flows as well by changing the default value to true
            // since older flows don't use this flag yet
            // This will be  removed once LMS changes are made live for all CA flows
            $attributeCore = new Merchant\Attribute\Core;

            $caOnboardingFlow = $attributeCore->fetch($bankingAccount->merchant,
                                                      Product::BANKING,
                                                      Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS,
                                                      Merchant\Attribute\Type::CA_ONBOARDING_FLOW)->getValue();

            $isOneCaFlow = ($caOnboardingFlow === MerchantAttributeType::ONE_CA);
        }
        catch (\Throwable $e)
        {
            $isOneCaFlow = false;
        }

        $oldAdditionalDetails = optional($activationDetail)->getAdditionalDetails() ?? '{}';

        $oldAdditionalDetails = json_decode($oldAdditionalDetails, true);

        // since this attribute is being read from a json, it is returned as a string
        $oldSalesPitchCompleted = ($oldAdditionalDetails[ActivationDetail\Entity::SALES_PITCH_COMPLETED] ?? null) === 1;

        // If non one_ca flow, mark sales_pitch as completed
        $oldSalesPitchCompleted = $isOneCaFlow ? $oldSalesPitchCompleted : true;

        $oldDeclarationStepCompleted = ((optional($activationDetail)->getDeclarationStep()) ?? null) === 1;

        // values sent in the activation input
        $newDeclarationStepCompleted = ($activationDetailInput[ActivationDetail\Entity::DECLARATION_STEP] ?? null) == 1;

        $newSalesPitchCompleted = ($activationDetailInput[ActivationDetail\Entity::ADDITIONAL_DETAILS][ActivationDetail\Entity::SALES_PITCH_COMPLETED] ?? null) === 1;

        $newSalesPitchCompleted = $isOneCaFlow ? $newSalesPitchCompleted : true;

        $declarationStepCompleted = ($oldDeclarationStepCompleted or $newDeclarationStepCompleted);
        $salesPitchCompleted = ($oldSalesPitchCompleted or $newSalesPitchCompleted);

            // check is form is submitted
        // submitted => $declarationStepCompleted and $salesPitchCompleted
        $readyToBePickedForRazorpayProcessing = ($declarationStepCompleted and $salesPitchCompleted);
        $formWasAlreadySubmittedEarlier = ($oldDeclarationStepCompleted and $oldSalesPitchCompleted);

        // check if form is readyToBePickedForRazorpayProcessing(i.e.. declarationStep & salesPitch steps are completed)
        // and also check if this the first submission post form completion
        if ($readyToBePickedForRazorpayProcessing and !$formWasAlreadySubmittedEarlier)
        {
            $this->sendFreshDeskTicketAndMoveApplicationToPicked($bankingAccount);
            return;
        }

        // If application is initiated in saled_led flow, there's no concept of declaration_step. Hence, on
        // each application update check if sufficient information is available. If yes, create FD ticket and move application to
        // picked status. Perform this check only if application is in created state
        if ($bankingAccount->getStatus() === Status::CREATED &&
            $caOnboardingFlow === MerchantAttributeType::SALES_LED &&
            $this->shouldSendFreshDeskTicketForSalesLed($bankingAccount, $activationDetail, $activationDetailInput))
        {
            $this->sendFreshDeskTicketAndMoveApplicationToPicked($bankingAccount);
        }
    }

    public function resetAccountInfoWebhookData(Entity $bankingAccount, State\Entity $stateChangeLogBeforeProcessedState, Base\PublicEntity $entity = null)
    {
        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_WEBHOOK_DATA_RESET,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel
            ]);

        $processor = $this->getProcessor($channel);

        $inputToUpdate = $processor->getWebhookDataToReset($bankingAccount, $stateChangeLogBeforeProcessedState);

        // We need activation detail input too here, as when we are resetting the webhook data we are -
        // -> adding a system generated comment
        // -> updating the assignee_team
        //So Logging this step will be useful for auditing purposes.
        $activationDetailInput = $this->extractAndValidateActivationDetailInput($inputToUpdate, $entity);

        $bankingAccount->fill($inputToUpdate);

        // Validating Bank Internal Status
        if (empty($input[Entity::BANK_INTERNAL_STATUS]) === false)
        {
            $processor->validateStatusMapping($input[Entity::BANK_INTERNAL_STATUS], $bankingAccount->getStatus(), $bankingAccount->getSubStatus());

            $bankingAccount->setBankInternalStatus($input[Entity::BANK_INTERNAL_STATUS]);
        }

        $this->repo->transaction(function()
            use ($bankingAccount,
                $activationDetailInput,
                $entity)
        {
            $this->repo->saveOrFail($bankingAccount);

            // storing state change
            $stateCore = new State\Core;

            $stateCore->captureNewBankingAccountState($bankingAccount, $entity);

            if (empty($activationDetailInput) === false) {
                // if ActivationDetail is passed with comment in input, entity will always be admin, not merchant.
                $this->activationDetailService->updateForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput,false, $entity);
            }
        });

        $bankingAccount->load('bankingAccountDetails');

        return $bankingAccount;
    }

    public function updateBankingAccountWithFtsId(Entity $bankingAccount, $ftsFundAccountId)
    {
        $bankingAccount->setFtsFundAccountId($ftsFundAccountId);

        $this->repo->saveOrFail($bankingAccount);
    }

    public function getBankingAccountEntity(string $id)
    {
        return $this->repo->banking_account->find($id);
    }

    public function addServiceablePincodes(array $pincodes, string $channel)
    {
        $this->trace->info(
            TraceCode::ADD_SERVICEABLE_PINCODES,
            [
                'pincodes' => $pincodes,
                'channel'  => $channel,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->addServiceablePincodes($pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes, string $channel)
    {
        $this->trace->info(
            TraceCode::REMOVE_SERVICEABLE_PINCODES,
            [
                'pincodes' => $pincodes,
                'channel'  => $channel,
            ]);

        $processor = $this->getProcessor($channel);

        $processor->deleteServiceablePincodes($pincodes);
    }

    public function activate(Entity $bankingAccount, array $input, Admin\Entity $admin)
    {
        //
        // This is in a transaction because, BankingAccount entity update
        // and Balance entity creation, both should succeed or fail
        //
        list($bankingAccount, $basDetailEntity, $balance) = $this->repo->transaction(function () use ($bankingAccount, $input, $admin)
        {
            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $bankingAccount = $processor->activate($bankingAccount, $input);

            $merchant = $bankingAccount->merchant;

            $mode = $this->app['rzp.mode'];

            $balanceInfo = $this->getBalanceAttributesToSave($bankingAccount);

            $balance = (new Merchant\Balance\Core)->createBalanceForCurrentAccount($merchant, $balanceInfo, $mode);

            // Creating a contact of type 'rzp_fees' and a fund account related to it. To be used for fees recovery.
            $this->createRZPFeesContactAndFundAccount($merchant, $balance->getChannel());

            $bankingAccount->balance()->associate($balance);

            $this->repo->saveOrFail($bankingAccount);

            // create banking account statement details entity
            $basDetailInput = array(
                BASDetails\Entity::ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
                BASDetails\Entity::CHANNEL          => $bankingAccount->getChannel(),
                BASDetails\Entity::MERCHANT_ID      => $bankingAccount->getMerchantId(),
                BASDetails\Entity::BALANCE_ID       => $bankingAccount->getBalanceId(),
                BASDetails\Entity::STATUS           => BASDetails\Status::ACTIVE
            );

            $basDetailEntity = (new BASDetails\Core)->createOrUpdate($basDetailInput);

            $this->createScheduleTaskForFeeRecovery($balance, $merchant);

            (new Counter\Core)->fetchOrCreate($balance);

            $stateCore = new State\Core;

            $stateCore->captureNewBankingAccountState($bankingAccount, $admin);

            // updating assignee to OPS for UPI Creds Pending
            $updateInput = [
                'activation_detail' => [
                    ActivationDetail\Entity::ASSIGNEE_TEAM => ActivationDetail\Entity::OPS,
                ]
            ];

            $this->updateBankingAccount($bankingAccount, $updateInput, $admin, true);

            // For Adding payout feature without RZP KYC
            (new Activate())->addPayoutFeatureIfApplicable($bankingAccount->merchant, Mode::LIVE, true);

            (new Activate())->addEnableIpWhitelistFeatureOnX($merchant, Mode::LIVE);

            $this->trace->info(TraceCode::PAYOUT_FEATURE_ADDED, [
                Merchant\Constants::MERCHANT_ID => $merchant->getId()
            ]);

            return [$bankingAccount, $basDetailEntity, $balance];
        });

        (new Merchant\Core())->addHasKeyAccessToMerchantIfApplicable($bankingAccount->merchant);

        // check experiment and onboard DA to ledger in reverse shadow or shadow mode
        $merchant = $bankingAccount->merchant;

        if ($this->onBoardDAMerchantOnLedgerInShadow($bankingAccount->merchant, $this->app['rzp.mode']) === true)
        {
            // assign DA_LEDGER_JOURNAL_WRITES feature for the merchant to be onboarded in
            // reverse shadow mode for direct accounting
            $this->assginLedgerFeatureForMerchant($merchant, Feature\Constants::DA_LEDGER_JOURNAL_WRITES);
            (new Merchant\Balance\Ledger\Core)->createXLedgerAccountForDirect($merchant, $basDetailEntity, $this->app['rzp.mode'], $balance->getBalance(), 0, false);
        }


        $this->sendBankingCaActivationSmsIfApplicable($bankingAccount);

        $this->sendNotificationAfterCAActivation($bankingAccount);

        return $bankingAccount;
    }

    // Assign feature as concluded by the experiment and clean up existing manually assigned feature by ops
    public function assginLedgerFeatureForMerchant($merchant, string $featureToAssign)
    {
        if ($merchant->isFeatureEnabled($featureToAssign) === false)
        {
            (new Feature\Core)->create(
                [
                    Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                    Feature\Entity::ENTITY_ID   => $merchant->getId(),
                    Feature\Entity::NAME        => $featureToAssign,
                ]);

            $this->trace->info(
                TraceCode::DA_LEDGER_FEATURE_ASSIGNED,
                [
                    'merchant_id'       => $merchant->getId(),
                    'mode'              => $this->app['rzp.mode'],
                    'feature_name'      => $featureToAssign
                ]);
        }
    }

    // Returns true if experiment and env variable to onboard direct accounting merchant on ledger in shadow is running.
    protected function onBoardDAMerchantOnLedgerInShadow($merchant, string $mode): bool
    {
        $variant = $this->app->razorx->getTreatment($merchant->getId(),
            Merchant\RazorxTreatment::DA_LEDGER_ONBOARDING,
            $mode
        );

        return (strtolower($variant) === 'on');
    }

    /**
     * Sends banking current account activation sms to the merchant
     *
     * @param Entity $bankingAccount
     *
     */
    public function sendBankingCaActivationSmsIfApplicable(Entity $bankingAccount)
    {
        $this->trace->info(TraceCode::BANKING_ACTIVATION_CONFIRMATION_SMS_CA_REQUEST,
            [
                'merchant_id' => $bankingAccount->merchant->getId(),
            ]);

        try
        {
            $users = $bankingAccount->merchant->ownersAndAdmins(Product::BANKING);

            if ($users === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_NOT_PRESENT);
            }

            // Mask Account Number
            $accountNumber = $bankingAccount->getAccountNumber();
            $accountNumberLength = strlen($accountNumber);

            $accountNumberMasked = str_pad(substr($accountNumber, ($accountNumberLength - 4), $accountNumberLength),
                                "6", "X", STR_PAD_LEFT);

            foreach ($users as $user)
            {
                $payload = [
                    'receiver' => $user->getContactMobile(),
                    'source'   => "api",
                    'template' => 'sms.account.activate_banking_ca',
                    'params'   => [
                        'account_number' => $accountNumberMasked,
                    ],
                ];

                $this->app->raven->sendSms($payload);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BANKING_ACTIVATION_CONFIRMATION_SMS_CA_FAILED,
                [
                    'merchant_id' => $bankingAccount->merchant->getId(),
                ]);
        }
    }

    public function createRZPFeesContactAndFundAccount(Merchant\Entity $merchant, string $channel)
    {
        $rzpFeesContacts = $this->repo->contact->fetch([
                                                           Contact\Entity::TYPE => Contact\Type::RZP_FEES
                                                       ],
                                                       $merchant->getId());

        if ($rzpFeesContacts->count() === 0)
        {
            $rzpFeesContact = (new Contact\Core)->createRZPFeesContact($merchant);
        }
        else
        {
            $rzpFeesContact = $rzpFeesContacts->first();
        }

        (new FundAccount\Core)->createRZPFeesFundAccount($merchant, $rzpFeesContact, $channel);
    }

    public function createScheduleTaskForFeeRecovery(Merchant\Balance\Entity $balance,
                                                        Merchant\Entity $merchant)
    {
        $defaultFeeRecoverySchedule = $this->repo->schedule->getScheduleByPeriodIntervalAnchorDelayAndType(
                                                                self::DEFAULT_SCHEDULE_PERIOD,
                                                                self::DEFAULT_SCHEDULE_INTERVAL,
                                                                null,
                                                                0,
                                                                Type::FEE_RECOVERY);

        if (empty($defaultFeeRecoverySchedule) === true)
        {
            $errorMessage = 'Default Fee Recovery schedule does not exist';

            $this->sendSlackAlert($errorMessage, []);

            throw new LogicException($errorMessage,
                                     ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_DEFAULT_SCHEDULE_DOES_NOT_EXIST,
                                     null);
        }

        $input = [
            Task\Entity::TYPE          => Task\Type::FEE_RECOVERY,
            Task\Entity::SCHEDULE_ID   => $defaultFeeRecoverySchedule->getId(),
        ];

        $task = (new Task\Core)->create($merchant, $balance, $input);

        $oneWeekLaterTimeStamp = Carbon::now(Timezone::IST)->addWeek()->getTimestamp();

        $task->setNextRunAt($oneWeekLaterTimeStamp);

        $this->repo->schedule_task->saveOrFail($task);

        $this->trace->info(TraceCode::FEE_RECOVERY_SCHEDULE_TASK_CREATED,
            [
                'merchant_id'   => $merchant->getId(),
                'balance_id'    => $balance->getId(),
                'task_id'       => $task->getId()
            ]);
    }

    public function bulkCreateBankingAccountsForYesbank(array $input)
    {
        $limit = $input['limit'];

        unset($input['limit']);

        $bankAccounts = $this->repo->bank_account->fetchAccountsNotPresentInBankingAccountsForYesbank($limit);

        $this->trace->info(
            TraceCode::BULK_CREATE_BANKING_ACCOUNTS_REQUEST,
            [
                'input' => $input,
                'bank_account_ids' => $bankAccounts->pluck(Entity::ID)
            ]);

        $successCount = $failedCount = 0;

        $failedIds = [];

        if (count($bankAccounts) !== 0)
        {
            foreach ($bankAccounts as $bankAccount)
            {
                try
                {
                    /** @var Merchant\Entity $merchant */
                    $merchant = $bankAccount->virtualAccount->merchant;

                    $balance = $bankAccount->virtualAccount->balance;

                    $attributes = $this->getSharedAccountAttributes($bankAccount);

                    $this->createSharedBankingAccount($attributes, $merchant, $balance);

                    $successCount++;
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::INFO,
                        TraceCode::BANKING_ACCOUNT_YESBANK_CREATE_FAILED,
                        [
                            'bank_account_id' => $bankAccount->getId()
                        ]);

                    $failedIds[] = $bankAccount->getId();

                    $failedCount++;
                }
            }
        }

        $response = [
            'total_count'       => count($bankAccounts),
            'success_count'     => $successCount,
            'failed_count'      => $failedCount,
            'failed_ids'        => $failedIds,
        ];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_YESBANK_BULK_CREATE_RESPONSE,
            [
                'response'  => $response,
                'range'     => $limit,
                'input'     => $input,
                'channel'   => Channel::YESBANK,
            ]);

        return $response;
    }

    // returns true if gateway balance update workers have to delete/flush messages.
    public function gatewayBalanceUpdateDeleteMode(string $channel)
    {
        switch ($channel)
        {
            case Channel::RBL:
                return (new AdminService)->getConfigKey(
                    ['key' => ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE]) ?? false;

            case Channel::ICICI:
                return (new AdminService)->getConfigKey(
                        ['key' => ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE]) ?? false;

            case Channel::AXIS:
            case Channel::YESBANK:
                return (new AdminService)->getConfigKey(
                    ['key' => ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE]) ?? false;

            default:
                return false;
        }
    }

    public function fetchAndUpdateGatewayBalanceWrapper(array $input)
    {
        $validator = new Validator();

        $validator->setStrictFalse()->validateInput(Validator::FETCH_GATEWAY_BALANCE, $input);

        $channel    = $input[Entity::CHANNEL];
        $merchantId = $input[Entity::MERCHANT_ID];

        $basDetails = $this->repo->banking_account_statement_details
            ->getDirectBasDetailEntityByMerchantIdAndChannel($merchantId, $channel);

        $response = $this->fetchAndUpdateGatewayBalance($basDetails);

        return $response;
    }

    /**
     * for CA, balance needs to be fetched from balance api provided by respective banks/gateways at regular frequency
     * which is agreed upon in SLA. This function will be used to fetch balance from gateway before making normal/queued
     * payouts depending upon balance_last_fetched_at.
     *
     * @param BASDetails\Entity $basDetails
     *
     * @return mixed
     */
    public function fetchAndUpdateGatewayBalance(BASDetails\Entity $basDetails)
    {
        $channel = $basDetails->getChannel();

        $merchantId = $basDetails->getMerchantId();

        $accountNumber = $basDetails->getAccountNumber();

        $processorParams = [
            Entity::CHANNEL        => $channel,
            Entity::MERCHANT_ID    => $merchantId,
            Entity::ACCOUNT_NUMBER => $accountNumber,
        ];

        $gatewayProcessor = $this->getProcessor($channel, $processorParams);

        // every gateway processor must implement fetchGatewayBalance function. This function sends Mozart request
        // to fetch balance from gateway and return balance.
        try
        {
            $balance = $gatewayProcessor->fetchGatewayBalance();

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_SUCCEEDED,
                [
                    Entity::CHANNEL                 => $channel,
                    Entity::MERCHANT_ID             => $basDetails->getMerchantId(),
                    Entity::ACCOUNT_NUMBER          => $basDetails->getAccountNumber(),
                    Entity::GATEWAY_BALANCE         => $basDetails->getGatewayBalance(),
                    Entity::BALANCE_LAST_FETCHED_AT => $basDetails->getBalanceLastFetchedAt(),
                ]);

            // Once gateway balance is fetched, this has to be updated in BAS Details table as well. Statement fetch will be initiated based on that table.
            $basDetailInput = array(
                BASDetails\Entity::ACCOUNT_NUMBER   => $basDetails->getAccountNumber(),
                BASDetails\Entity::CHANNEL          => $basDetails->getChannel(),
                BASDetails\Entity::MERCHANT_ID      => $basDetails->getMerchantId(),
                BASDetails\Entity::BALANCE_ID       => $basDetails->getBalanceId(),
                BASDetails\Entity::GATEWAY_BALANCE  => $balance
                );

            $basDetails = (new BASDetails\Core)->createOrUpdate($basDetailInput);
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_FAILED,
                [
                    Entity::CHANNEL                 => $channel,
                    Entity::MERCHANT_ID             => $basDetails->getMerchantId(),
                    Entity::ACCOUNT_NUMBER          => $basDetails->getAccountNumber(),
                    Entity::GATEWAY_BALANCE         => $basDetails->getGatewayBalance(),
                    Entity::BALANCE_LAST_FETCHED_AT => $basDetails->getBalanceLastFetchedAt(),
                ]);

            Tracer::startSpanWithAttributes(HyperTrace::BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_FAILED,
                                            [
                                                Entity::CHANNEL                 => $channel,
                                                Entity::MERCHANT_ID             => $basDetails->getMerchantId(),
                                            ]);
        }

        return $basDetails;
    }

    public function getActivationStatusChangeLog(Entity $bankingAccount)
    {
        $statusChangeLog = $bankingAccount->getActivationStatusChangeLog();

        return $statusChangeLog;
    }

    protected function getBalanceAttributesToSave(Entity $bankingAccount)
    {
        $attributes = [
            Merchant\Balance\Entity::ACCOUNT_TYPE        => Merchant\Balance\AccountType::DIRECT,
            Merchant\Balance\Entity::CHANNEL             => $bankingAccount->getChannel(),
            Merchant\Balance\Entity::ACCOUNT_NUMBER      => $bankingAccount->getAccountNumber(),
        ];

        return $attributes;
    }

    protected function getSegmentEventPropertiesForBankingAccountStatusChange($bankingAccountStatus, $bankingAccountSubStatus): array
    {
        return [
            'status' => $bankingAccountStatus,
            'subStatus' => $bankingAccountSubStatus
        ];
    }

    /**
     * @param array $bankingAccount
     * @param bool $bankingAccountStatusChanged
     * @param bool $bankingAccountSubStatusChanged
     */
    public function notifyIfStatusChanged(array $bankingAccount, bool $bankingAccountStatusChanged, bool $bankingAccountSubStatusChanged): void
    {
        if ((new Service())->isNeoStoneExperiment($bankingAccount) === true)
        {
            $channel = Entity::Neostone;

            $this->fireHubspotEventForStatusChange($bankingAccountStatusChanged, $bankingAccountSubStatusChanged, $bankingAccount, $channel);
        }

        else
        {
            if ($bankingAccountStatusChanged === true)
            {
                $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
            }
            if ($bankingAccountSubStatusChanged === true)
            {
                $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);

                $this->notifyForMerchantNotAvailableToSPOC($bankingAccount);
            }
        }

        if (($bankingAccountStatusChanged === true) or
            ($bankingAccountSubStatusChanged === true))
        {

            /** @var \RZP\Models\Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->findOrFail($bankingAccount[Entity::MERCHANT_ID]);

            $this->sendSegmentEvent($bankingAccount, $merchant);
        }
    }

    /*
     * Why are we moving substatus to None (During FD Creation) and then to Docket Initiated
     *  This is to ensure we are following the state machine
     * Since we are checking if skip_dwt is 1, we are implicitly assuming declaration_step is 1
     */
    public function moveSubstatusIfSkipDwtExpEligible(Entity $bankingAccount, array $input)
    {
        /** @var Detail\Entity $activationDetail */
        $activationDetail = $bankingAccount->bankingAccountActivationDetails;

        $additionalDetails = optional($activationDetail)->getAdditionalDetails() ?? '{}';

        $additionalDetails = json_decode($additionalDetails, true);

        $skipDwt = $additionalDetails['skip_dwt'] ?? null;

        if ($bankingAccount->getStatus() === Status::PICKED &&
            $bankingAccount->getSubStatus() === Status::NONE &&
            is_null($skipDwt) === false)
        {
            $subStatus = $skipDwt === 1 ? Status::INITIATE_DOCKET : Status::DWT_REQUIRED;

            $this->trace->info(TraceCode::BANKING_ACCOUNT_AUTOMATIC_SUB_STATUS_UPDATE, [
                'old_sub_status'  => $bankingAccount->getSubStatus(),
                'new_sub_status'  => $subStatus
            ]);

            $this->updateBankingAccount($bankingAccount,
                [
                    Entity::SUB_STATUS => $subStatus
                ],
                $bankingAccount->merchant);
        }

    }

    public function moveSubstatusToInitiateDocketIfDwtCompletedTimestampFilled(Entity $bankingAccount, ActivationDetail\Entity $activationDetail)
    {
        $additionalDetails = optional($activationDetail)->getAdditionalDetails() ?? '{}';

        $additionalDetails = json_decode($additionalDetails, true);

        $dwtCompletedTimestamp = $additionalDetails['dwt_completed_timestamp'] ?? null;

        if ($bankingAccount->getStatus() === Status::PICKED &&
            $bankingAccount->getSubStatus() === Status::DWT_REQUIRED &&
            empty($dwtCompletedTimestamp) === false)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_AUTOMATIC_SUB_STATUS_UPDATE, [
                'old_sub_status'  => $bankingAccount->getSubStatus(),
                'new_sub_status'  => Status::INITIATE_DOCKET
            ]);

            $this->updateBankingAccount($bankingAccount,
                [
                    Entity::SUB_STATUS => Status::INITIATE_DOCKET
                ],
                $bankingAccount->merchant);
        }
    }

    /**
     * This method is responsible for checking that unless the merchant is L2 activated, no one can update
     * the status of current account to activated. This to avoid cases of manual error by Bizops.
     *
     * @param Entity $bankingAccount
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    protected function checkMerchantIsActivatedBeforeAccountActivation(Entity $bankingAccount)
    {
        $merchant = $bankingAccount->merchant;

        $merchantActivationStatus = $merchant->merchantDetail->getActivationStatus();

        if ($merchantActivationStatus !== Detail\Status::ACTIVATED)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_NOT_PERMITTED,
                Entity::STATUS,
                [
                    'merchant_activation_status' => $merchant->merchantDetail->getActivationStatus(),
                    'banking_account'            => $bankingAccount->getId(),
                ]);
        }
    }

    public function createCapitalCorpCardBankingAccount(array $input, Merchant\Entity $merchant,
                                                        Merchant\Balance\Entity $balance): Entity
    {
        $this->trace->info(
            TraceCode::CREATE_CORP_CARD_BANKING_ACCOUNT,
            [
                'channel' => $input[Entity::CHANNEL],
                'input'   => $this->scrubBankingAccountSensitiveDetails($input),
            ]);

        (new Validator)->validateInput(Validator::CORP_CARD_CREATE, $input);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->balance()->associate($balance);

        $bankingAccount->setStatus(Status::ACTIVATED);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    protected function createSharedBankingAccount(
        array $input,
        Merchant\Entity $merchant,
        Merchant\Balance\Entity $balance): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'channel' => $input[Entity::CHANNEL],
                'input'   => $this->scrubBankingAccountSensitiveDetails($input),
            ]);

        (new Validator)->validateInput(Validator::SHARED_CREATE, $input);

        $bankingAccount = new Entity;

        $bankingAccount->build($input);

        $bankingAccount->merchant()->associate($merchant);

        $bankingAccount->balance()->associate($balance);

        // Shared accounts are always created in the processed state
        $bankingAccount->setStatus(Status::ACTIVATED);

        $this->repo->saveOrFail($bankingAccount);

        return $bankingAccount;
    }

    public function scrubBankingAccountSensitiveDetails($input)
    {
        $scrubbedData = [];

        $sensitiveKeys = ['account_number','beneficiary_email','beneficiary_mobile','beneficiary_name','beneficiary_pin'];

        foreach ($input as $key => $value)
        {
            if (in_array($key, $sensitiveKeys, true) === true and
                empty($value) === false)
            {
                $value = 'SCRUBBED' . '(' . strlen($value) . ')';
            }

            $scrubbedData[$key] = $value;
        }

        return $scrubbedData;
    }

    public function getProcessor(string $channel, array $processorParams = []): Gateway\Processor
    {
        $processor = __NAMESPACE__ . '\\' . 'Gateway';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Processor';

        if (class_exists($processor) === true)
        {
            return new $processor($processorParams);
        }
        else
        {
            throw new LogicException(
                'Bad request, Gateway Processor class does not exist for the channel:' . $channel,
                ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_GATEWAY_PROCESSOR_CLASS_ABSENCE,
                [
                    'channel' => $channel,
                ]);
        }
    }

    protected function getSharedAccountAttributes(BankAccount\Entity $bankAccount)
    {
        $channel = $this->isLiveMode() ?
            array_flip(VirtualAccount\Provider::IFSC)[$bankAccount->getIfscCode()] : Channel::YESBANK;

        $attributes = [
            Entity::CHANNEL                   => $channel,
            Entity::ACCOUNT_NUMBER            => $bankAccount->getAccountNumber(),
            Entity::ACCOUNT_IFSC              => $bankAccount->getIfscCode(),
            Entity::BENEFICIARY_EMAIL         => $bankAccount->getBeneficiaryEmail(),
            Entity::BENEFICIARY_MOBILE        => $bankAccount->getBeneficiaryMobile(),
            Entity::BENEFICIARY_CITY          => $bankAccount->getBeneficiaryCity(),
            Entity::BENEFICIARY_STATE         => $bankAccount->getBeneficiaryState(),
            Entity::BENEFICIARY_COUNTRY       => $bankAccount->getBeneficiaryCountry(),
            Entity::BENEFICIARY_NAME          => $bankAccount->getBeneficiaryName(),
            Entity::BENEFICIARY_ADDRESS1      => $bankAccount->getBeneficiaryAddress1(),
            Entity::BENEFICIARY_ADDRESS2      => $bankAccount->getBeneficiaryAddress2(),
            Entity::BENEFICIARY_PIN           => $bankAccount->getBeneficiaryPin(),
            Entity::ACCOUNT_TYPE              => AccountType::NODAL,
            Entity::STATUS                    => Status::CREATED,
            Entity::BENEFICIARY_ADDRESS3      => $bankAccount->getBeneficiaryAddress3() . ' ' .
                                                 $bankAccount->getBeneficiaryAddress4(),
        ];

        return $attributes;
    }

    /**
     * @param string $reviewerId
     * @param array $bankingAccountIds
     * @return array
     */
    public function bulkAssignReviewer(string $reviewerId, array $bankingAccountIds) : array
    {
        $success     = 0;
        $failedItems = [];

        try
        {
            $this->repo->admin->findByPublicId($reviewerId);
        }
        catch (\Throwable $e)
        {
            $response = [
                'success' => 0,
                'failed'  => count($bankingAccountIds),
                'error'   => $e->getMessage(),
            ];

            return $response;
        }

        foreach ($bankingAccountIds as $bankingAccountId)
        {
            try
            {
                $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

                $this->addReviewerToBankingAccount($bankingAccount, $reviewerId);

                $success++;
            }
            catch (\Throwable $e)
            {
                $failedItems[] = [
                    Entity::BANKING_ACCOUNT_ID          => $bankingAccountId,
                    'error'             => $e->getMessage()
                ];
            }
        }

        $response = [
            'success'     => $success,
            'failed'      => count($failedItems),
            'failedItems' => $failedItems,
        ];

        return $response;
    }

    /**
     * @param Entity $bankingAccount
     * @param string $reviewerId
     */
    public function addReviewerToBankingAccount(Entity $bankingAccount, string $reviewerId)
    {
        $reviewer = $this->repo->admin->findByPublicId($reviewerId);

        $existingReviewer = $bankingAccount->reviewers()
                                           ->where(Entity::AUDITOR_TYPE, '=', 'reviewer')
                                           ->first();

        // If banking account already has a reviewer, detach the reviewer from the banking account.
        // The new reviewer will be attached to the banking account below,
        // effectively assigning the banking account the new reviewer.
        if (empty($existingReviewer) === false)
        {
            $reviewerId = $existingReviewer->pivot->admin_id;

            $bankingAccount->reviewers()->detach($reviewerId);
        }

        $bankingAccount->reviewers()->attach($reviewer, [Entity::AUDITOR_TYPE => 'reviewer']);

        $this->repo->saveOrFail($bankingAccount);
    }

    public function getAdminDetails(string $adminId) : Admin\Entity
    {
        if (!str_starts_with($adminId, 'admin_'))
        {
            $adminId = 'admin_' . $adminId;
        }
        return $this->repo->admin->findByPublicId($adminId);
    }

    public function getUserDetails(string $userId) : User\Entity
    {
        return $this->repo->user->findByPublicId($userId);
    }

    public function addSalesPOCToBankingAccount(Entity $bankingAccount, string $spocId)
    {
        $spoc = $this->repo->admin->findByPublicId($spocId);

        $existingSpoc = $bankingAccount->spocs()
                                       ->where(Entity::AUDITOR_TYPE, '=', 'spoc')
                                       ->first();

        // If banking account already has a spoc, detach the spoc from the banking account.
        // The new spoc will be attached to the banking account below,
        // effectively assigning the banking account the new reviewer.
        if (empty($existingSpoc) === false)
        {
            $spocId = $existingSpoc->pivot->admin_id;

            $bankingAccount->spocs()->detach($spocId);
        }

        $bankingAccount->spocs()->attach($spoc, [Entity::AUDITOR_TYPE => 'spoc']);

        $this->repo->saveOrFail($bankingAccount);
    }

    /**
     * @param Entity $bankingAccount
     * @param string $opsMxPOCId
     */
    public function addOpsMxPOCToBankingAccount(Entity $bankingAccount, string $opsMxPOCId)
    {
        $opsMxPOC = $this->repo->admin->findByPublicId($opsMxPOCId);

        $existingOpsMxPOC = $bankingAccount->opsMxPocs()
            ->where(Entity::AUDITOR_TYPE, '=', Entity::OPS_MX_POC)
            ->first();

        // If banking account already has an MX POC, detach the MX POC from the banking account.
        // The new MX POC will be attached to the banking account below,
        // effectively assigning the banking account the new MX POC.
        if (empty($existingOpsMxPOC) === false)
        {
            $existingOpsMxPOCId = $existingOpsMxPOC->pivot->admin_id;
            $bankingAccount->opsMxPocs()->detach($existingOpsMxPOCId);
        }

        $bankingAccount->opsMxPocs()->attach($opsMxPOC, [Entity::AUDITOR_TYPE => Entity::OPS_MX_POC]);

        $this->repo->saveOrFail($bankingAccount);
    }

    /**
     * @param Entity $bankingAccount
     * @param string $beneficiaryPin
     * @param string $beneficiaryName
     * @return bool
     */
    public function isDataValidForBankingAccount(Entity $bankingAccount, string $beneficiaryPin, string $beneficiaryName): bool
    {
        //similar_text - returns the number of matching chars percentage in both strings.
        //The number of matching characters is calculated by finding the longest first common substring, and
        //then doing this for the prefixes and the suffixes, recursively. The lengths of all found common substrings are added.

        $businessNameFromRblInLowerCase = strtolower(trim($beneficiaryName));

        $businessNameFromRazorpayInLowerCase = strtolower(trim($bankingAccount->merchant->merchantDetail->getBusinessName()));

        similar_text($businessNameFromRblInLowerCase, $businessNameFromRazorpayInLowerCase, $similarityPercent);
        if($beneficiaryPin !== $bankingAccount->getPincode() || $similarityPercent < 75.00)
        {
            return false;
        }
        return true;
    }

    protected function redactSecrets(array $input)
    {
        unset($input[Entity::PASSWORD]);
    }

    /**
     * This function is used by cron to dispatch job for each merchant(merchants selected based upon channel and ordered
     * by balance last fetched at).Job fetches balance from gateway and then update in banking account associated
     * with merchant
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function dispatchGatewayBalanceUpdateForMerchants(string $channel)
    {
        $validator = new Validator();

        $validator->validateInput(Validator::DISPATCH_GATEWAY_BALANCE, [Entity::CHANNEL => $channel]);

        $variant = $this->app->razorx->getTreatment($channel,
                                                    Merchant\RazorxTreatment::GATEWAY_BALANCE_FETCH_V2,
                                                    $this->app['rzp.mode']);

        if (strtolower($variant) === 'on')
        {
            return $this->dispatchGatewayBalanceUpdateForMerchantsV2($channel);
        }

        return $this->dispatchGatewayBalanceUpdateForMerchantsV1($channel);
    }

    public function dispatchGatewayBalanceUpdateForMerchantsV1(string $channel)
    {
        // different limit for each channel
        $limit = $this->getGatewayBalanceUpdateRateLimit($channel);

        // get list of merchants based upon channel and balance last fetched at
        $merchantIds = $this->repo->banking_account_statement_details
                                  ->getMerchantIdsByChannel($channel, $limit);

        foreach ($merchantIds as $merchantId)
        {
            $this->dispatchGatewayBalanceUpdateJob($channel, $merchantId);
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_DISPATCHED,
            [
                'merchant_ids' => $merchantIds
            ]);

        return $merchantIds;
    }

    // tech spec for this dispatch logic: https://docs.google.com/document/d/1rqTkDsnoYamSFDsEnnmgG_0aNf6jA8Y9Bglu6c_1tXM/edit#heading=h.lc0fi15c803g
    protected function dispatchGatewayBalanceUpdateForMerchantsV2(string $channel)
    {
        switch ($channel)
        {
            case Channel::RBL:
                $balanceUpdateLimits = (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_CA_BALANCE_UPDATE_LIMITS]);
                break;

            default:
                $balanceUpdateLimits = [];
        }

        if (empty($balanceUpdateLimits) === true)
        {
            $balanceUpdateLimits = self::DEFAULT_CA_BALANCE_UPDATE_LIMITS;
        }

        // time period to be used in made_payout rule and balance_change rule.
        $timePeriod                  = $balanceUpdateLimits[self::CA_BALANCE_UPDATE_TIME_LIMIT];
        // maximum number of merchants to select under balanced change rule.
        $limitForBalanceChangeRule   = $balanceUpdateLimits[self::CA_BALANCE_UPDATE_RATE_LIMIT];
        // maximum number of merchants to select under mandatory update rule.
        // This number should be set such that gateway balance is fetched at-least once for all merchants in 10 minutes.
        $limitForMandatoryUpdateRule = $balanceUpdateLimits[self::CA_MANDATORY_BALANCE_UPDATE_RATE_LIMIT];

        // initializing to empty array as further code will depend on count of these arrays.
        $merchantIdsToDispatch[self::MADE_PAYOUT_RULE]      = [];
        $merchantIdsToDispatch[self::BALANCE_CHANGE_RULE]   = [];
        $merchantIdsToDispatch[self::MANDATORY_UPDATE_RULE] = [];

        $currentTime = Carbon::now()->getTimestamp();

        // get list of distinct merchant ids who have done payouts in last $timePeriod seconds.
        $merchantIdsToDispatch[self::MADE_PAYOUT_RULE] = $this->repo->payout->getCAMerchantIdsWithAtleastOnePayout($channel, $currentTime - $timePeriod, $currentTime);

        $basDetails = $this->repo->banking_account_statement_details->fetchByChannelOrderByBalanceLastFetchedAt($channel);

        /** @var BASDetails\Entity $basDetailsEntity */
        foreach ($basDetails as $basDetailsEntity)
        {
            if (in_array($basDetailsEntity->getMerchantId(), $merchantIdsToDispatch[self::MADE_PAYOUT_RULE]) === false)
            {
                // merchants whose gateway balance changed in last $timePeriod seconds will be selected under this rule.
                if (($basDetailsEntity->getGatewayBalanceChangeAt() > $currentTime - $timePeriod) and
                    (count($merchantIdsToDispatch[self::BALANCE_CHANGE_RULE]) < $limitForBalanceChangeRule))
                {
                    $merchantIdsToDispatch[self::BALANCE_CHANGE_RULE][] = $basDetailsEntity->getMerchantId();
                }
                else if (count($merchantIdsToDispatch[self::MANDATORY_UPDATE_RULE]) < $limitForMandatoryUpdateRule)
                {
                    // merchants who didn't fall in other selection rules will be selected under this rule.
                    // This is to ensure that we fetch gateway balance for all merchants in say 10 minutes.
                    $merchantIdsToDispatch[self::MANDATORY_UPDATE_RULE][]= $basDetailsEntity->getMerchantId();
                }
            }

            // greater than condition will never be used. Kept it for safe side.
            if ((count($merchantIdsToDispatch[self::BALANCE_CHANGE_RULE]) >= $limitForMandatoryUpdateRule) and
                (count($merchantIdsToDispatch[self::MANDATORY_UPDATE_RULE]) >= $limitForBalanceChangeRule))
            {
                break;
            }
        }

        foreach ($merchantIdsToDispatch as $rule => $merchantIds)
        {
            foreach ($merchantIds as $merchantId)
            {
                $this->dispatchGatewayBalanceUpdateJob($channel, $merchantId, $rule);
            }
        }

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_DISPATCHED_V2, $merchantIdsToDispatch);

        return $merchantIdsToDispatch;
    }

    protected function getGatewayBalanceUpdateRateLimit(string $channel)
    {

        switch ($channel)
        {
            case Channel::RBL:
                $limit = (int) (new AdminService)->getConfigKey(
                    ['key' => ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT]);
                break;

            case Channel::ICICI:
                $limit = (int) (new AdminService)->getConfigKey(
                    ['key' => ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT]);
                break;

            case Channel::AXIS:
            case Channel::YESBANK:
                $limit = (int) (new AdminService)->getConfigKey(
                    ['key' => ConfigKey::CONNECTED_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT]);
                break;

            default:
                // just a safe check (it would never reach here), ideally it will fail at
                //validation at core when cron hits request with wrong channel
                $this->trace->error(
                    TraceCode::CHANNEL_NOT_SUPPORTED_FOR_BALANCE_FETCH,
                    [
                        'channel' => $channel
                    ]);
        }

        if (empty($limit) === true)
        {
            $limit = self::DEFAULT_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT;
        }

        return $limit;
    }

    protected function dispatchGatewayBalanceUpdateJob(string $channel, $merchantId, string $rule = 'default')
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_REQUEST,
            [
                Entity::CHANNEL     => $channel,
                Entity::MERCHANT_ID => $merchantId,
                'rule'              => $rule,
            ]);

        // different queue for each channel
        $job = $this->getGatewayBalanceUpdateJobForChannel($channel);

        if (empty($job) === false)
        {
            $job::dispatch($this->mode,
                           [
                               Entity::CHANNEL     => $channel,
                               Entity::MERCHANT_ID => $merchantId,
                           ]);
        }

    }

    protected function getGatewayBalanceUpdateJobForChannel(string $channel)
    {
        $job = 'RZP\Jobs' . '\\' . studly_case($channel) . 'BankingAccountGatewayBalanceUpdate';

        if (class_exists($job) === true)
        {
            return $job;
        }
        else
        {
            if (in_array($channel, self::$directChannelsForConnectBanking) === true)
            {
                return 'RZP\Jobs' . '\\' . 'ConnectedBankingAccountGatewayBalanceUpdate';
            }
            // just a safe check (it would never reach here), ideally it will fail at
            //validation at core when cron hits request with wrong channel
            $this->trace->error(
                TraceCode::CHANNEL_NOT_SUPPORTED_FOR_BALANCE_FETCH,
                [
                    'channel' => $channel
                ]);
        }
    }

    public function unsetPersonalIdentifiableInformation(array $input): array
    {
        if (empty($input[Entity::ACCOUNT_IFSC]) === false)
        {
            $input[Entity::ACCOUNT_IFSC] = str_repeat('*', strlen($input[Entity::ACCOUNT_IFSC]));
        }

        if (empty($input[Entity::ACCOUNT_NUMBER]) === false)
        {
            $input[Entity::ACCOUNT_NUMBER] = str_repeat('*', strlen($input[Entity::ACCOUNT_NUMBER]));
        }

        if (empty($input[Entity::BENEFICIARY_EMAIL]) === false)
        {
            $input[Entity::BENEFICIARY_EMAIL] = str_repeat('*', strlen($input[Entity::BENEFICIARY_EMAIL]));
        }

        if (empty($input[Entity::BENEFICIARY_MOBILE]) === false)
        {
            $input[Entity::BENEFICIARY_MOBILE] = str_repeat('*', strlen($input[Entity::BENEFICIARY_MOBILE]));
        }

        if (empty($input[Entity::BENEFICIARY_NAME]) === false)
        {
            $input[Entity::BENEFICIARY_NAME] = str_repeat('*', strlen($input[Entity::BENEFICIARY_NAME]));
        }

        if (empty($input[Entity::BENEFICIARY_ADDRESS1]) === false)
        {
            $input[Entity::BENEFICIARY_ADDRESS1] = str_repeat('*', strlen($input[Entity::BENEFICIARY_ADDRESS1]));
        }

        if (empty($input[Entity::BENEFICIARY_ADDRESS2]) === false)
        {
            $input[Entity::BENEFICIARY_ADDRESS2] = str_repeat('*', strlen($input[Entity::BENEFICIARY_ADDRESS2]));
        }

        if (empty($input[Entity::BENEFICIARY_ADDRESS3]) === false)
        {
            $input[Entity::BENEFICIARY_ADDRESS3] = str_repeat('*', strlen($input[Entity::BENEFICIARY_ADDRESS3]));
        }

        if (empty($input[Entity::BENEFICIARY_PIN]) === false)
        {
            $input[Entity::BENEFICIARY_PIN] = str_repeat('*', strlen($input[Entity::BENEFICIARY_PIN]));
        }

        return $input;
    }

    protected function sendSlackAlert($operation, $data)
    {
        (new SlackNotification)->send($operation, $data, null, 1, Entity::RX_CA_RBL_ALERTS);
    }

    // Haversine formula
    protected function distanceBetweenLocation($lat1, $lon1, $lat2, $lon2)
    {
        $dLat = ($lat2 - $lat1) * M_PI / 180.0;
        $dLon = ($lon2 - $lon1) * M_PI / 180.0;

        // convert to radians
        $lat1 = ($lat1) * M_PI / 180.0;
        $lat2 = ($lat2) * M_PI / 180.0;

        // apply formulae
        $a = pow(sin($dLat / 2), 2) + pow(sin($dLon / 2), 2) * cos($lat1) * cos($lat2);

        // Radius of earth
        $rad = 6371;

        $c = 2 * asin(sqrt($a));

        return $rad * $c;
    }

    public function checkIfServiceableByRBL($lat1, $lng1): bool
    {
        $location = BankLocation::$rblBankLocation;

        foreach ($location as $co_ordinate)
        {
            $lat2 = $co_ordinate[0];
            $lng2 = $co_ordinate[1];
            if ($this->distanceBetweenLocation($lat1, $lng1, $lat2, $lng2) < 29.9)
            {
                return true;
            }
        }
        return false;
    }

    public function getLocationFromPincode($pincode): array
    {
        return (new GoogleMapApi())->getLocationFromPincode($pincode);
    }

    public function autofillStateAndCityFromPincode(array $activation_detail, array $input): array
    {
        if (isset($input['pincode']) === false)
        {
            return $activation_detail;
        }

        $pinCode = $input['pincode'];

        $pincodeSearch = $this->app['pincodesearch'];

        try
        {
            $resp = $pincodeSearch->fetchCityAndStateFromPincode($pinCode);
        }
        catch (BadRequestException | BadRequestValidationFailureException | IntegrationException $e)
        {
            $this->trace->error(TraceCode::PINCODE_SEARCH_ERROR, [$pinCode, $e->getMessage()]);

            return $activation_detail;
        }

        $activation_detail[Activation\Detail\Entity::MERCHANT_CITY] = $resp['city'];

        $activation_detail[Activation\Detail\Entity::MERCHANT_STATE] = $resp['state'];

        try
        {
            $activation_detail[ActivationDetail\Entity::MERCHANT_REGION] = (new Activation\Detail\Region)->getRegionFromState($resp['state']);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::STATE_TO_REGION_MAP_FAILED, [$resp['state'], $e->getMessage()]);

            $activation_detail[ActivationDetail\Entity::MERCHANT_REGION] = null;
        }

        return $activation_detail;
    }

    private function fireHubspotEventForStatusChange(bool $bankingAccountStatusChanged, bool $bankingAccountSubStatusChanged, array $account, string $channel)
    {
        if ($bankingAccountStatusChanged or $bankingAccountSubStatusChanged)
        {
            $currentStatus = $account[Entity::STATUS];

            $currentSubStatus = $account[Entity::SUB_STATUS];

            $payload = ['ca_channel' => $channel];

            if ($bankingAccountStatusChanged)
            {
                $this->notifier->notify($account, Event::STATUS_CHANGE, Event::INFO, $payload);
            }
            else if ($bankingAccountSubStatusChanged)
            {
                if ($currentStatus === Status::PROCESSING and $currentSubStatus === Status::DISCREPANCY_IN_DOCS)
                {
                    $this->notifier->notify($account, Event::PROCESSING_DISCREPANCY_IN_DOCS, Event::INFO, $payload);
                }
            }
        }

    }

    private function notifyForMerchantNotAvailableToSPOC(array $bankingAccount)
    {
        if ($bankingAccount[Entity::SUB_STATUS] !== Status::MERCHANT_NOT_AVAILABLE)
        {
            return;
        }

        $spocEmail = $bankingAccount[Entity::SPOCS][0]['email'] ?? null;

        if (empty($spocEmail) === false)
        {
            $mailable = new MerchantNotAvailable([$bankingAccount], $spocEmail);

            Mail::queue($mailable);
        }
    }

    public function filterActivatedAccountsAndMaskAccountNumber(array $bankingAccounts)
    {
        $activatedBankingAccounts = $bankingAccounts;
        $activatedBankingAccounts['items'] = [];

        foreach ($bankingAccounts['items'] as $bankingAccount)
        {
            if ($bankingAccount[Entity::STATUS] === Status::ACTIVATED)
            {
                $bankingAccount[Entity::ACCOUNT_NUMBER] = mask_except_last4($bankingAccount[Entity::ACCOUNT_NUMBER]);
                $activatedBankingAccounts['items'][] =  $bankingAccount;
            }
        }

        $activatedBankingAccounts['count'] = count($activatedBankingAccounts['items']);

        return $activatedBankingAccounts;
    }

    /**
     * @param string $validatorOP
     * @param Entity $bankingAccount
     *
     * @return void
     */
    public function shouldNotifyOpsAboutProActivation(string $validatorOP, array $bankingAccount, bool $clarityContextEnabled = false): void
    {
        if (($validatorOP !== 'create_dashboard' && $validatorOP != 'create_co_created' && $validatorOP != 'create_ccc_capital_created'))
        {
            if ($clarityContextEnabled === false)
            {
                $this->notifyOpsAboutProActivation($bankingAccount);
            }

            $this->notifyMerchantAboutUpdatedStatus($bankingAccount);

            $this->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount);

            $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
            $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);
        }
    }

    /** Fetch merchant BA status (supports only RBL and ICICI channels for now)
     *  Add custom logic to fetch BA status for any other channel
     *
     * @param string|null $channel
     * @param Merchant\Entity $merchant
     * @return string
     */
    public function getMerchantBankingAccountStatus(?string $channel, Merchant\Entity $merchant, string $mode = Mode::LIVE): ?string
    {
        //lower casing channel value since some entities are storing bank names in upper cases
        $channel = strtolower($channel);
        switch ($channel)
        {
            case Channel::RBL:
                $bankingAccount = $this->repo->banking_account->connection($mode)->fetchBankingAccountByMerchantIdAccountTypeChannelAndStatus(
                    $merchant->getId(), Channel::RBL, AccountType::CURRENT);
                return $bankingAccount->getStatus();

            case Channel::ICICI:
                return (new BasService())->fetchMerchantBaApplicationStatusForIcici($merchant->getId());

            default:
                return null;
        }
    }

    /** Fetch merchant BA PAN status (supports only RBL and ICICI channels for now)
     *  Add custom logic to fetch PAN status for any other channel
     *
     * @param string $channel
     * @param Merchant\Entity $merchant
     * @return string
     */
    public function getMerchantBankingAccountPanStatus(string $channel, Merchant\Entity $merchant): ?string
    {
        //lower casing channel value since some entities are storing bank names in upper cases
        $channel = strtolower($channel);
        switch ($channel)
        {
            case Channel::RBL:
                $bankingAccount = $this->repo->banking_account->fetchBankingAccountByMerchantIdAccountTypeChannelAndStatus(
                    $merchant->getId(), Channel::RBL, AccountType::CURRENT);
                return $bankingAccount->bankingAccountActivationDetails[Entity::BUSINESS_PAN_VALIDATION];

            case Channel::ICICI:
                return (new BasService())->fetchMerchantBaPanStatusForIcici($merchant->getId());

            default:
                return null;
        }
    }

    public function sendNotificationAfterCAActivation(Entity $bankingAccount){

        if ((new Service())->isNeoStoneExperiment($bankingAccount->toArray()) === true)
        {
            $payload = ['ca_channel' => Entity::Neostone];

            $this->notifier->notify($bankingAccount->toArray(), Event::STATUS_CHANGE, Event::INFO, $payload);
        }
        else
        {
            $this->notifier->notify($bankingAccount->toArray(), Event::STATUS_CHANGE);
            $this->notifier->notify($bankingAccount->toArray(), Event::SUBSTATUS_CHANGE);
        }

        $merchant = $bankingAccount->merchant;

        try
        {
            $this->app['x-segment']->sendEventToSegment(SegmentEvent::X_BANKING_ACCOUNT_STATUS_CHANGE_V2, $merchant, $this->generateSegmentProperties($bankingAccount->toArray(),$merchant));

        } catch (\Exception $e)
        {
            $this->trace->error(TraceCode::X_CURRENT_ACCOUNT_SEGMENT_PUSH_FAILED,[
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Actions taken after archiving any banking account (Used for both RBL and ICICI accounts)
     * Currently updating state of Banking Account Statement Details table
     *
     * @param Balance\Entity|null $balance
     *
     * @return array[Optional[banking_account_statement_details]]
     */

    public function archiveBankingAccount(?Balance\Entity $balance): array
    {
        if (empty($balance) === false)
        {
            $bankingAccountStatementDetails = $this->repo->banking_account_statement_details->fetchAccountStatementByBalance($balance->getId());
            if (empty($bankingAccountStatementDetails) === false)
            {
                return (new BankingAccountStatementDetailsCore())->archiveStatementDetail($bankingAccountStatementDetails)->toArray();
            }
        }

        return [];
    }

    /**
     * @param array           $bankingAccount
     * @param Merchant\Entity $merchant
     *
     * @return void
     */
    private function sendSegmentEvent(array $bankingAccount, Merchant\Entity $merchant): void
    {
        try
        {
            $this->app['x-segment']->sendEventToSegment(SegmentEvent::X_BANKING_ACCOUNT_STATUS_CHANGE_V2, $merchant,
                $this->generateSegmentProperties($bankingAccount,$merchant));
        } catch (\Exception $e)
        {
            $this->trace->error(TraceCode::X_CURRENT_ACCOUNT_SEGMENT_PUSH_FAILED,[
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendClarityContextEnabledEventToSF(Merchant\Entity $merchant)
    {
        $salesForceClient = new SalesForceClient($this->app);
        $salesForceService = new SalesForceService($salesForceClient);

        $salesForceEventRequestDTO = new SalesForceEventRequestDTO();
        $salesForceEventRequestDTO->setEventType(new SalesForceEventRequestType('CURRENT_ACCOUNT_CLARITY_CONTEXT'));
        $salesForceEventRequestDTO->setEventProperties([Entity::CLARITY_CONTEXT => 'enabled']);

        $salesForceService->raiseEvent($merchant, $salesForceEventRequestDTO);
    }

    public function getAdminFromHeadersForMobApp()
    {
        $adminEmailHeader = $this->app['request']->header('X-Admin-Email');

        if ($this->isAdminRequestFromMOB())
        {
            $adminRepo = new \RZP\Models\Admin\Admin\Repository();

            return $adminRepo->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, $adminEmailHeader);
        }

        return null;
    }

    public function isAdminRequestFromMOB()
    {
        $adminEmailHeader = $this->app['request']->header('X-Admin-Email');

        if ($this->app['basicauth']->isMobApp() and
             empty($adminEmailHeader) === false)
        {
            return true;
        }

        return false;
    }

    // TODO: Move events related to banking_account status change to a central location
    public function sendFreshDeskTicketAndMoveApplicationToPicked(Entity $bankingAccount)
    {
        if ($bankingAccount->getStatus() == Status::CREATED)
        {
            $this->repo->transaction(function() use ($bankingAccount) {

                $this->updateBankingAccount($bankingAccount,
                    [
                        Entity::STATUS => Status::PICKED,
                        Entity::SUB_STATUS => Status::NONE
                    ],
                    $bankingAccount->merchant);

                $this->notifyOpsAboutProActivation($bankingAccount->toArray());
            });
        }
    }

    /**
     * Update Drop Off Date in activationDetailInput if changing status and/or sub-status
     */
    public function validateAndSetDropOffDate(Entity $bankingAccount, array &$activationDetailInput = null, $input)
    {
        $status = $bankingAccount->getStatus();

        if (isset($input[Entity::STATUS]) === true)
        {
            $status = $input[Entity::STATUS];
        }

        if ($status === Status::ARCHIVED)
        {

            if (isset($input[Entity::SUB_STATUS]) == true)
            {
                $subStatus = $input[Entity::SUB_STATUS];

                // Bank can't set any other sub-status in archived
                if ($subStatus !== Status::IN_PROCESS && $this->app['basicauth']->isBankLms() === true)
                {
                    throw new BadRequestValidationFailureException('Sub-status should be In Process for Drop off leads.');
                }

                $activationDetails = $bankingAccount->bankingAccountActivationDetails;
                // If already set, no need to update
                if (empty($activationDetails[Activation\Detail\Entity::DROP_OFF_DATE]) === false)
                {
                    return;
                }

                // if changing sub-status to anything other than in_process
                if ($subStatus !== Status::IN_PROCESS)
                {
                    $activationDetailInput[Activation\Detail\Entity::DROP_OFF_DATE] = Carbon::now()->timestamp;
                }
            }

        }
        else
        {
            $activationDetails = $bankingAccount->bankingAccountActivationDetails;
            // If set, need to unset
            if (empty($activationDetails[Activation\Detail\Entity::DROP_OFF_DATE]) == false) {

                $activationDetailInput[Activation\Detail\Entity::DROP_OFF_DATE] = null;
            }
        }
    }

    /**
     * Update Bank LMS Due Date in activationDetailInput->rbl_activation_details
     * We recalculate this on every update as it could change
     * due to changing any of the dates or status/sub-status
     */
    public function setBankDueDateIfApplicable(Entity $bankingAccount)
    {
        // re-fetching to get the updated data after update for activation details
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccount->getPublicId());

        $status = $bankingAccount->getStatus();

        $bankDueDate = null;

        if (in_array($status, [
            Status::VERIFICATION_CALL,
            Status::DOC_COLLECTION,
            Status::ACCOUNT_OPENING,
            Status::API_ONBOARDING,
            Status::ACCOUNT_ACTIVATION,
            Status::ARCHIVED,
        ]))
        {
            $followUpDate = $bankingAccount->getReferenceDateForStatus();

            $bankDueDate = Status::getBankDueDate($status, $followUpDate);
        }

        $activationDetails = $bankingAccount->bankingAccountActivationDetails;

        $rblActivationDetails = $activationDetails[Activation\Detail\Entity::RBL_ACTIVATION_DETAILS];

        $activationDetailInput = null;

        // If set, need to unset
        if (empty(Activation\Detail\Entity::extractFieldFromJSONField($rblActivationDetails, Activation\Detail\Entity::BANK_DUE_DATE)) == false) {

            $activationDetailInput = [
                Activation\Detail\Entity::RBL_ACTIVATION_DETAILS => [
                    Activation\Detail\Entity::BANK_DUE_DATE => $bankDueDate
                ]
            ];
        }

        return $activationDetailInput;
    }

    /**
     * According to new state logic,
     * If status is API Onboarding or Account Activation, and web-hook is triggered -> do nothing
     * Else change the status to account_opening and sub-status to ca_opened
     */
    private function handleStateFromAccountOpeningWebhook(Entity $bankingAccount, array & $attributes)
    {
        $this->trace->info(Tracecode::BANKING_ACCOUNT_WEBHOOK_NEW_STATE, [
            'bankingAccount' => $bankingAccount,
            'attributes' => $attributes,
        ]);

        /**
         * TODO:
         * M2 States Experiment
         * Remove this when all new leads are onboarded to new terminal states
         */
        if ($bankingAccount->usingNewStates() === false)
        {
            return;
        }

        if (in_array($bankingAccount->getStatus(), [
            Status::API_ONBOARDING,
            Status::ACCOUNT_ACTIVATION,
        ]))
        {
            $attributes[Entity::STATUS] = $bankingAccount->getStatus();
            $attributes[Entity::SUB_STATUS] = $bankingAccount->getSubStatus();
            return;
        }

        $attributes[Entity::STATUS] = Status::API_ONBOARDING;
        $attributes[Entity::SUB_STATUS] = Status::IN_REVIEW;
    }

    /**
     * @throws BadRequestException
     */
    private function shouldSendFreshDeskTicketForSalesLed(Entity $bankingAccount, ActivationDetail\Entity $activationDetail, array $activationDetailInput) : bool
    {
        // merchant_id & pincode are also required fields for banking_account entity but since a banking_account cannot exist without these,
        // we don't have to check for them.
        if (array_key_exists(ActivationDetail\Entity::ADDITIONAL_DETAILS, $activationDetailInput))
        {
            $activationDetailInput = (new ActivationDetail\Service(new Notifier()))->updateAdditionalDetailsPayload($activationDetail, $activationDetailInput);
        }

        // convert to array
        $existingActivationDetail = $activationDetail->toArray();

        // calculate additionalDetails
        $oldAdditionalDetails = json_decode(optional($activationDetail)->getAdditionalDetails() ?? '{}', true);
        $newAdditionalDetails = json_decode($activationDetailInput[ActivationDetail\Entity::ADDITIONAL_DETAILS] ?? '{}', true);

        // validate required fields
        $validator = new ActivationDetail\Validator();

        $activationDetailsPresent = $this->checkRequiredFieldsPresentForFreshDeskTicket(array_merge($existingActivationDetail, $activationDetailInput),
            $validator->getRequiredActivationDetailsKeysFreshDesk(),
            'freshDeskActivationDetails',
            $validator);

        $additionalDetailsPresent = $this->checkRequiredFieldsPresentForFreshDeskTicket(array_merge($oldAdditionalDetails, $newAdditionalDetails),
            $validator->getRequiredAdditionalDetailsKeysFreshDesk(),
            'freshDeskAdditionalDetails',
            $validator);

        // check conditions
        if (!$this->checkSpocPresentForBankingAccount($bankingAccount,$activationDetailInput)
            || !$activationDetailsPresent
            || !$additionalDetailsPresent)
        {
            return false;
        }

        return true;
    }

    public function checkRequiredFieldsPresentForFreshDeskTicket(array $checker, array $requiredFields, string $validatorOp, ActivationDetail\Validator $validator): bool
    {
        $checker = array_intersect_key($checker, array_fill_keys($requiredFields, ''));

        try
        {
            $validator->validateInput($validatorOp, $checker);
        }
        catch(\Exception $e)
        {
            $this->trace->info(TraceCode::FRESHDESK_MISSING_ATTRIBUTES, [
                'checker'               => $checker,
                'error'                 => $e->getMessage()
            ]);

            return false;
        }

        return true;
    }

    private function checkSpocPresentForBankingAccount(Entity $bankingAccount, array $activationDetailInput): bool
    {
        $existingSpoc = $bankingAccount->spocs()
            ->where(Entity::AUDITOR_TYPE, '=', 'spoc')
            ->first();

        if (empty($existingSpoc) && !array_key_exists(ActivationDetail\Entity::SALES_POC_ID, $activationDetailInput))
        {
            // SalesPoc is not set for application & it is not present in input payload. Return
            return false;
        }

        return true;
    }

    public function sendDocketIfApplicable($bankingAccount, $entity)
    {
        // re-fetch banking-account to handle case where it is updated during freshdeskticket creation
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccount->getPublicId());

        // For Docket Initiation, we need the latest activation details
        $bankingAccount->load('bankingAccountActivationDetails');

        $currentStatus = $bankingAccount->getStatus();

        $currentSubStatus = $bankingAccount->getSubStatus();

        if (!($currentStatus === Status::PICKED && $currentSubStatus == Status::INITIATE_DOCKET))
        {
            // Not registering as a reason
            return $bankingAccount;
        }

        [$shouldSendDocket, $reasonsToNotSend] = $this->checkIfDocketToBeSent($bankingAccount);

        if ($shouldSendDocket === false)
        {
            $bankingAccount = $this->updateBankingAccount(
                $bankingAccount,
                [
                    Entity::ACTIVATION_DETAIL => [
                        Activation\Detail\Entity::ADDITIONAL_DETAILS => [
                            Activation\Detail\Entity::SENT_DOCKET_AUTOMATICALLY => false,
                            Activation\Detail\Entity::REASONS_TO_NOT_SEND_DOCKET => $reasonsToNotSend,
                        ]
                    ]
                ],
                $entity, false, false, false);

            return $bankingAccount;
        }
        else
        {
            $sentDocket = $this->sendDocketEmail($bankingAccount);

            if ($sentDocket)
            {
                $bankingAccount = $this->updateBankingAccount(
                    $bankingAccount,
                    [
                        Entity::STATUS      => Status::PICKED,
                        Entity::SUB_STATUS  => Status::DOCKET_INITIATED,
                        Entity::ACTIVATION_DETAIL => [
                            Activation\Detail\Entity::ADDITIONAL_DETAILS => [
                                Activation\Detail\Entity::SENT_DOCKET_AUTOMATICALLY => true,
                                Activation\Detail\Entity::REASONS_TO_NOT_SEND_DOCKET => [], // empty array
                            ]
                        ]
                    ],
                    $entity, false, false, false);
            }
            else
            {
                $bankingAccount = $this->updateBankingAccount(
                    $bankingAccount,
                    [
                        Entity::ACTIVATION_DETAIL => [
                            Activation\Detail\Entity::ADDITIONAL_DETAILS => [
                                Activation\Detail\Entity::SENT_DOCKET_AUTOMATICALLY => false,
                                Activation\Detail\Entity::REASONS_TO_NOT_SEND_DOCKET => ['Server error'],
                            ]
                        ]
                    ],
                    $entity, false, false, false);
            }
        }

        return $bankingAccount;
    }

    private function checkIfDocketToBeSent(Entity $bankingAccount)
    {
        $reasonsToNotSend = [];

        $bankingAccountId = $bankingAccount->getId();

        $poe = optional($bankingAccount->bankingAccountActivationDetails)->isPoEVerified() ?? false;

        if ($poe == false)
        {
            array_push($reasonsToNotSend, Constants::POE_NOT_VERIFIED);
        }

        $entityNameCheck = optional($bankingAccount->bankingAccountActivationDetails)->businessNameMatchesMerchantName($bankingAccount->merchant->getName()) ?? false;

        if ($entityNameCheck == false)
        {
            array_push($reasonsToNotSend, Constants::ENTITY_NAME_MISMATCH);
        }

        $entityTypeCheck = optional($bankingAccount->bankingAccountActivationDetails)->businessCategoryMatchesMerchantBusinessType($bankingAccount->merchant->merchantDetail->getBusinessType()) ?? false;

        if ($entityTypeCheck == false)
        {
            array_push($reasonsToNotSend, Constants::ENTITY_TYPE_MISMATCH);
        }

        // For skipping DB calls for now
        if (empty($reasonsToNotSend))
        {

            $states = $bankingAccount->getActivationStatusChangeLog()->toArray();

            $expected = [
                [
                    Entity::STATUS => Status::CREATED,
                ],
                [
                    Entity::STATUS => Status::PICKED,
                    Entity::SUB_STATUS => Status::NONE,
                ],
                [
                    Entity::STATUS => Status::PICKED,
                    Entity::SUB_STATUS => Status::INITIATE_DOCKET,
                ]
            ];

            $match = check_array_selective_equals_recursive($expected, $states);

            if (!$match)
            {
                array_push($reasonsToNotSend, Constants::UNEXPECTED_STATE_CHANGE_LOG);
            }
        }

        // For skipping DB calls for now
        if (empty($reasonsToNotSend))
        {
            $existing = $this->repo->banking_account->fetchBankingAccountsWithMatchingMerchantName($bankingAccount->merchant, $bankingAccountId);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                'stage'                 => 'core >> check if docket to be sent',
                'duplicate_application' => $existing,
            ]);

            if ($existing != null)
            {
                array_push($reasonsToNotSend, Constants::DUPLICATE_MERCHANT_APPLICATION);
            }
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
            'stage'                 => 'core >> check if docket to be sent',
            'reasons_to_not_send'    => $reasonsToNotSend,
            'banking_account_id'    => $bankingAccountId,
            'merchant_id'           => $bankingAccount->getMerchantId(),
        ]);

        if (empty($reasonsToNotSend))
        {
            return [true, $reasonsToNotSend];
        }

        return [false, $reasonsToNotSend];
    }

    private function sendDocketEmail(Entity $bankingAccount)
    {
        try
        {
            $bankingAccountId = $bankingAccount->getId();

            $this->generateAndGetCredentials($bankingAccount);

            $merchantName = $bankingAccount->merchant[Merchant\Entity::NAME];

            $businessCategory = $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::BUSINESS_CATEGORY];

            /** @var BAS $bas */
            $bas = app('banking_account_service');

            // Get PDF URL
            $url = $bas->getDocketPdfUrl($bankingAccountId, $businessCategory, $merchantName);

            if ($url)
            {
                // Download PDF
                [$viewData, $recipients, $otherRecipients] = $this->getDocketMailData($bankingAccount, $url);

                // Send email
                $mailable = new DocketMail($viewData, $recipients, $otherRecipients);

                $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage'             => 'core >> download pdf',
                    'viewData'          => $viewData,
                    'recipients'        => $recipients,
                ]);

                Mail::queue($mailable);

                return true;
            }
            else
            {
                $this->trace->error(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_ERROR, [
                    'stage' => 'core >> Not Sending Docket',
                    'error' => 'No PDF URL'
                ]);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_ERROR, [
                'stage' => 'core >> download pdf',
                'error' => $ex->getMessage()
            ]);
        }

        return false;
    }

    private function generateAndGetCredentials(Entity $bankingAccount)
    {
        $bankingAccountId = $bankingAccount->getId();

        /** @var BAS $bas */
        $bas = app('banking_account_service');

        $credentials = $bas->getGeneratedRblCredentials($bankingAccountId);

        $merchantName = $bankingAccount->merchant[Merchant\Entity::NAME];

        // Check if credentials are already generated
        if (empty($credentials['upi_handle1']))
        {
            $mcc = $bankingAccount->merchant[Merchant\Entity::CATEGORY];

            /**
             * Get MCC code from business category and sub-category mapping
             *
             * Fallback for merchants who sign up directly on X
             * additional_details has business_details property for this
             *
             * This is different from Entity Type (banking_account_activation_details->business_category)
             */
            if (empty($mcc))
            {
                $businessDetails = ActivationDetail\Entity::extractFieldFromJSONField(
                    $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::ADDITIONAL_DETAILS],
                    ActivationDetail\Entity::BUSINESS_DETAILS
                );

                if (empty($businessDetails) === false)
                {
                    $category = $businessDetails[ActivationDetail\Entity::CATEGORY];
                    $subcategory = $businessDetails[ActivationDetail\Entity::SUB_CATEGORY];

                    $mcc = MccMappings::getMCCMapping($category, $subcategory);
                }
            }

            if (empty($mcc) === true)
            {
                $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage'     => 'core >> Not Sending Docket',
                    'error'     => 'MCC Code could not be resolved'
                ]);

                return false;
            }

            $rblCredentialsPayload = [
                'banking_account_id'    => $bankingAccount->getId(),
                'merchant_id'           => $bankingAccount->getMerchantId(),
                'merchant_name'         => $merchantName,
                'mcc_code'              => $mcc
            ];

            // Generate Credentials
            $bas->generatedRblCredentials($bankingAccountId, $rblCredentialsPayload);
        }
    }

    private function getDocketMailData(Entity $bankingAccount, string $url)
    {
        $bankingAccountActivationDetails = $bankingAccount->bankingAccountActivationDetails;

        $city = $bankingAccountActivationDetails->merchant_city;

        $pincode = $bankingAccount->getPincode();

        $businessCategory = $bankingAccountActivationDetails[ActivationDetail\Entity::BUSINESS_CATEGORY];

        $merchantName = $bankingAccount->merchant[Merchant\Entity::NAME];

        $refNo = $bankingAccount->getBankReferenceNumber();

        $entityType = ucwords(str_replace('_', ' ', $businessCategory));

        $viewData = [
            'merchantName'      => $merchantName,
            'refNo'             => $refNo,
            'entityType'        => $entityType,
            'subject'           => 'RazorpayX | Stamp Paper and Docs | '.$merchantName.' | '.$refNo.' | '.$entityType,
            'address'           => $bankingAccountActivationDetails->merchant_documents_address,
            'city'              => $city.', '.$pincode,
            'pocName'           => $bankingAccountActivationDetails->merchant_poc_name,
            'pocPhoneNumber'    => $bankingAccountActivationDetails->merchant_poc_phone_number,
            'attachment_url'    => $url,
        ];

        $recipient = [
            'name'  => 'Rangaswamy S',
            'email' => 'rangaswamy.s@lesconcierges.in',
        ];

        $otherRecipients = [
            [
                'name'  => 'Lohith M',
                'email' => 'lohith.m@lesconcierges.in',
            ],
            [
                'name'  => 'Syed',
                'email' => 'syed@lesconcierges.in'
            ],
            [
                'name'  => 'X Onboarding',
                'email' => 'x-caonboarding@razorpay.com',
            ],
        ];

        if (in_array($city, ['Bengaluru', 'Bangalore', 'bengaluru', 'bangalore']))
        {
            $viewData['pocName'] = 'Bennet/ Ferin';
            $viewData['pocPhoneNumber'] = '9113917356 / 9980430227';

            $viewData['address'] = 'Razorpay Software, SJR Cyber Laskar, Hosur Rd, Adugodi, Bengaluru, Karnataka 560030';
            $viewData['city'] = 'Bengaluru, 560030';

            $viewData['subject'] = $viewData['subject'].' | BLR';
        }

        return [
            $viewData, $recipient, $otherRecipients
        ];
    }

    private function generateSegmentProperties(array $bankingAccount,Merchant\Entity $merchant): array
    {
        /** @var \RZP\Models\User\Entity $owner */
        $owner = $merchant->owners(Product::BANKING)->first();
        if(empty($owner))
        {
            $owner = $merchant->owners()->first();
        }

        $userRole = 'owner';

        $merchantDetail = $merchant->merchantDetail;

        return [
            'merchant_id'           => $merchant->getId(),
            'user_id'               => $owner->getId(),
            'user_role'             => $userRole,
            'email'                 => optional($merchantDetail)->getContactEmail(),
            'phone'                 => optional($merchantDetail)->getContactMobile(),
            'bank_channel'          => array_get($bankingAccount, Entity::CHANNEL, ''),
            'business_name'         => array_get($bankingAccount, Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS . '.' . ActivationDetail\Entity::BUSINESS_NAME, ''),
            'business_type'         => array_get($bankingAccount, Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS . '.' . ActivationDetail\Entity::BUSINESS_CATEGORY, ''),
            'bank_beneficiary_name' => array_get($bankingAccount, Entity::BENEFICIARY_NAME, ''),
            'bank_account_ifsc'     => array_get($bankingAccount, Entity::ACCOUNT_IFSC, ''),
            'account_number'        => array_get($bankingAccount, Entity::ACCOUNT_NUMBER, ''),
            'status'                => array_get($bankingAccount, Entity::STATUS, ''),
            'sub_status'            => array_get($bankingAccount, Entity::SUB_STATUS, ''),
        ];

    }

    public function checkRblOnBasExperimentEnabled(string $merchantId) : bool
    {
        $isExperimentEnabled = (new Merchant\Core())->isSplitzExperimentEnable([
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.rbl_on_bas_exp_id')
        ], Constants::ACTIVE,
            TraceCode::BANKING_ACCOUNT_RBL_ON_BAS_EXPERIMENT_FAILED);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_RBL_ON_BAS_EXPERIMENT_STATUS, [
            'experiment_status' => $isExperimentEnabled,
            'merchant_id'       => $merchantId,
        ]);

        return $isExperimentEnabled;
    }
}
