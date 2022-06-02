<?php

namespace RZP\Models\BankingAccount;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Counter;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Admin\Admin;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Type;
use RZP\Models\Schedule\Task;
use RZP\Models\VirtualAccount;
use RZP\Http\Request\Requests;
use RZP\Models\Schedule\Period;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Activate;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\State;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway;
use RZP\Exception\IntegrationException;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\BankingAccount\Channel as BAChannel;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\BankingAccountService\Service as BasService;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Detail as BankingAccountDetail;
use RZP\Models\BankingAccountStatement\Channel as BasChannel;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;
use RZP\PushNotifications\CurrentAccount\StatusUpdate as StatusUpdatePN;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Attribute\Core as MerchantAttributeCore;
use \RZP\Models\Merchant\Attribute\Type as MerchantAttributeType;
use RZP\Models\BankingAccountStatement\Details\Core as BankingAccountStatementDetailsCore;

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

    protected static $notificationStatuses = [
        Status::PICKED,
        Status::INITIATED,
        Status::PROCESSING,
        Status::CANCELLED,
        Status::ACTIVATED,
        Status::UNSERVICEABLE,
        Status::REJECTED,
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

    public static $statusUpdatePnTitleMap = [
        Status::PICKED	        =>	self::PICKED_PN_TITLE,
        Status::INITIATED	    =>	self::INITIATED_PN_TITLE,
        Status::PROCESSING	    =>	self::PROCESSING_PN_TITLE,
        Status::CANCELLED	    =>	self::CANCELLED_PN_TITLE,
        Status::ACTIVATED	    =>	self::ACTIVATED_PN_TITLE,
        Status::UNSERVICEABLE	=>	self::UNSERVICEABLE_PN_TITLE,
        Status::REJECTED    	=>	self::REJECTED_PN_TITLE
    ];

    public static $statusUpdatePnBodyMap = [
        Status::PICKED	        =>	self::PICKED_PN_BODY,
        Status::INITIATED	    =>	self::INITIATED_PN_BODY,
        Status::PROCESSING	    =>	self::PROCESSING_PN_BODY,
        Status::CANCELLED	    =>	self::CANCELLED_PN_BODY,
        Status::ACTIVATED	    =>	self::ACTIVATED_PN_BODY,
        Status::UNSERVICEABLE	=>	self::UNSERVICEABLE_PN_BODY,
        Status::REJECTED    	=>	self::REJECTED_PN_BODY
    ];

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');

        $this->activationDetailService =  resolve(Activation\Detail\Service::class);

        $this->notifier = new Notifier;
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
     * @param Entity $bankingAccount
     */
    public function notifyOpsAboutProActivation(Entity $bankingAccount)
    {
        try
        {
            $activationDetail = $bankingAccount->bankingAccountActivationDetails;

            $reviewer = ['reviewer_name' => ""];

            if ($activationDetail != null)
            {
                $reviewer = ['reviewer_name' => $activationDetail->getAssigneeName()];
            }

            $bankingAccount = $bankingAccount->load('merchant');

            $mailer = new XProActivation(array_merge($bankingAccount->toArray(), $reviewer));

            Mail::queue($mailer);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_X_PRO_ACTIVATION_NOTIFICATION,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'message'            => 'Mail Sent'
                ]);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_X_PRO_ACTIVATION_NOTIFICATION_FAILED,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'error'              => $e->getMessage(),
                ]);
        }
    }

    public function notifyMerchantAboutUpdatedStatus(Entity $bankingAccount)
    {
        try
        {
            $mailer = StatusUpdateMailerFactory::getMailer($bankingAccount);

            Mail::queue($mailer);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'message'            => 'Mail Sent'
                ]);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_UPDATE_NOTIFICATION_FAILED,
                [
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $bankingAccount->merchant->getId(),
                    'status'             => $bankingAccount->getStatus(),
                    'error'              => $e->getMessage(),
                ]);
        }
    }

    public function notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification(Entity $bankingAccount)
    {
        $status = $bankingAccount->getStatus();

        $statusList = self::$notificationStatuses;

        if (in_array($status, $statusList, true) === false)
        {
            return;
        }

        $pushNotificationTitle = self::$statusUpdatePnTitleMap[$status];

        $pushNotificationBody =  self::$statusUpdatePnBodyMap[$status];

        $pushNotificationTag = "ca_onboarding_" .  $status;

        $users = $bankingAccount->merchant->ownersAndAdmins(Product::BANKING);

        foreach ($users as $user)
        {
            $userId = $user->getId();

            $notificationData = array(
                'ownerId'       => $bankingAccount->merchant->getId(),
                'ownerType'     => 'merchant',
                'title'         => $pushNotificationTitle,
                'body'          => $pushNotificationBody,
                'status'        => $status,
                'identityList'  => [$userId],
                'tags'          => array(
                    'merchantId'            => $bankingAccount->merchant->getId(),
                    'userId'                => $userId,
                    'notificationPurpose'   => $pushNotificationTag,
                ),
                'tagGroup'      => $pushNotificationTag,
            );

            $pushNotification = new StatusUpdatePN($notificationData);
            $pushNotification->send();

            $this->trace->info(TraceCode::PUSH_NOTIFICATION_DISPATCHED_FOR_CA_STATUS_UPDATE, [$notificationData]);
        }
    }

    public function extractAndValidateActivationDetailInput(array &$input, $entity = null)
    {
        if (isset($input['activation_detail']) === true)
        {
            $auth = $this->app['basicauth'];

            // if comment is passed and updater entity is merchant, can't add comment as
            // commenter is figured out from admin.
            if ((empty($entity) === false)
                and (isset($input['activation_detail'][ActivationDetail\Entity::COMMENT]) === true)
                and ($entity->getEntity() !== 'admin'))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_DETAILS_ONLY_ON_ADMIN_AUTH);
            }

            return array_pull($input, 'activation_detail');
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

        $this->shouldNotifyOpsAboutProActivation($validatorOp, $bankingAccount);

        $this->sendSegmentEvent($bankingAccount, $merchant);

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

        try
        {
            $processor->preProcessAccountInfoNotification($input);

            $attributes = $processor->processAccountInfoNotification($input);

            $bankingAccount = $this->fetchByBankReferenceAndChannel($channel, $attributes[Entity::BANK_REFERENCE_NUMBER]);

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
                $this->notifier->notify($bankingAccount, Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY, Event::ALERT, $eventProperties);
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
        $bankingAccount = $this->repo
                               ->banking_account
                               ->findByBankReferenceAndChannel($channel, $bankReference);

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
    public function updateBankingAccount(Entity $bankingAccount, array $input, Base\PublicEntity $entity = null, bool $isAutomatedUpdate = false, bool $fromDashboard = false)
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

        $activationDetailInput = $this->extractAndValidateActivationDetailInput($input, $entity);

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
                $isAutomatedUpdate)
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

            // storing state change
            if (($bankInternalStatusChanged === true) or
                ($bankingAccountStatusChanged === true) or
                ($bankingAccountSubStatusChanged === true))
            {
                $stateCore = new State\Core;

                $stateCore->captureNewBankingAccountState($bankingAccount, $entity);
            }

            $this->notifyIfStatusChanged($bankingAccount, $bankingAccountStatusChanged, $bankingAccountSubStatusChanged);

            // Updating BankingAccountActivation Details
            if (empty($activationDetailInput) === false)
            {
                // if ActivationDetail is passed with comment in input, entity will always be admin, not merchant.
                $this->activationDetailService->updateForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput, $isAutomatedUpdate, $entity);
            }
        });

        // We need to populate banking account details using
        // toArrayPublic, which populates only the pre fetched
        // relations. So explicitly fetching this relation here
        $bankingAccount->load('bankingAccountDetails');

        $bankingAccount->load('bankingAccountActivationDetails');

        return $bankingAccount;
    }

    public function checkAndSendFreshDeskEmailIfFormIsSubmitted(Entity $bankingAccount, array $activationDetailInput)
    {
        if (isset($activationDetailInput[ActivationDetail\Entity::DECLARATION_STEP]) === true)
        {
            $declaration_step = ($bankingAccount->bankingAccountActivationDetails)->declaration_step;

            if ($activationDetailInput[ActivationDetail\Entity::DECLARATION_STEP] == 1 and $declaration_step !== 1)
            {
                $this->notifyOpsAboutProActivation($bankingAccount);
            }
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

            // updating assignee to null
            $updateInput = [
                'activation_detail' => [
                    ActivationDetail\Entity::ASSIGNEE_TEAM => null
                ]
            ];

            $this->updateBankingAccount($bankingAccount, $updateInput, $admin, true);

            // For Adding payout feature without RZP KYC
            (new Activate())->addPayoutFeatureIfApplicable($bankingAccount->merchant, Mode::LIVE, true);

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

    protected function getSegmentEventPropertiesForBankingAccountStatusChange(Entity $bankingAccount, $bankingAccountStatus, $bankingAccountSubStatus): array
    {
        return [
            'status' => $bankingAccountStatus,
            'subStatus' => $bankingAccountSubStatus
        ];
    }

    /**
     * @param Entity $bankingAccount
     * @param bool   $bankingAccountStatusChanged
     * @param bool   $bankingAccountSubStatusChanged
     */
    public function notifyIfStatusChanged(Entity $bankingAccount, bool $bankingAccountStatusChanged, bool $bankingAccountSubStatusChanged): void
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

            $merchant = $bankingAccount->merchant;

            $this->sendSegmentEvent($bankingAccount, $merchant);
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
                    Entity::ID          => $bankingAccountId,
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

    private function fireHubspotEventForStatusChange(bool $bankingAccountStatusChanged, bool $bankingAccountSubStatusChanged, Entity $account, string $channel)
    {
        if ($bankingAccountStatusChanged or $bankingAccountSubStatusChanged)
        {
            $currentStatus = $account->getStatus();

            $currentSubStatus = $account->getSubStatus();

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

    private function notifyForMerchantNotAvailableToSPOC(Entity $bankingAccount)
    {
        if($bankingAccount->getSubStatus() !== Status::MERCHANT_NOT_AVAILABLE)
        {
            return;
        }

        $stateRepo = new State\Repository();

        $bankingAccountState = $stateRepo->getStateByBankingAccountIdAndSubState($bankingAccount->getId(), Status::MERCHANT_NOT_AVAILABLE);

        $spocEmail = $bankingAccount->spocs()->first()['email'];

        if (empty($spocEmail) === false)
        {
            $finalBankingAccountStates = [];

            if ($bankingAccountState->getSubStatus() === $bankingAccount->getSubStatus())
            {
                array_push($finalBankingAccountStates, $bankingAccountState);
            }

            $mailable = new MerchantNotAvailable($finalBankingAccountStates, $spocEmail);

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
    protected function shouldNotifyOpsAboutProActivation(string $validatorOP, Entity $bankingAccount): void
    {
        if ($validatorOP !== 'create_dashboard' && $validatorOP != 'create_co_created')
        {
            $this->notifyOpsAboutProActivation($bankingAccount);

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

        if ((new Service())->isNeoStoneExperiment($bankingAccount) === true)
        {
            $payload = ['ca_channel' => Entity::Neostone];

            $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE, Event::INFO, $payload);
        }
        else
        {
            $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
            $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);
        }

        $merchant = $bankingAccount->merchant;

        if (empty($merchant) === false)
        {
            $this->app['x-segment']->sendEventToSegment(SegmentEvent::CA_ACTIVATED, $merchant, ['status' => $bankingAccount->getStatus()]);
        }
        else
        {
            $this->trace->info(TraceCode::MERCHANT_FETCH_FAILED,
                [
                    'event_name' => SegmentEvent::CA_ACTIVATED,
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
     * @param Entity          $bankingAccount
     * @param Merchant\Entity $merchant
     *
     * @return void
     */
    private function sendSegmentEvent(Entity $bankingAccount, Merchant\Entity $merchant): void
    {
        $currentBankingAccountStatus = $bankingAccount->getStatus();

        $currentBankingAccountSubStatus = $bankingAccount->getSubStatus();

        $properties = $this->getSegmentEventPropertiesForBankingAccountStatusChange($bankingAccount, $currentBankingAccountStatus, $currentBankingAccountSubStatus);

        $this->app['x-segment']->sendEventToSegment(SegmentEvent::BANKING_ACCOUNT_STATUS_CHANGE, $merchant, $properties);
    }
}
