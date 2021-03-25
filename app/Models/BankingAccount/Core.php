<?php

namespace RZP\Models\BankingAccount;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Counter;
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
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\BankingAccount\Channel as BAChannel;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Detail as BankingAccountDetail;
use RZP\Models\BankingAccountStatement\Details as BASDetails;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;

class Core extends Base\Core
{
    const GATEWAY   = 'gateway';
    const PROCESSOR = 'processor';

    const DEFAULT_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT = 1000;

    // Values for default Fee Recovery Schedule
    const DEFAULT_SCHEDULE_PERIOD   = Period::DAILY;
    const DEFAULT_SCHEDULE_INTERVAL = 7;


    /** @var ActivationDetail\Service $activationDetailService */
    protected $activationDetailService;

    /**
     * @var Notifier
     */
    protected $notifier;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('banking_account');

        $this->activationDetailService =  resolve(Activation\Detail\Service::class);

        $this->notifier = new Notifier;
    }

    public function createOrFetchSharedBankingAccountFromVA(VirtualAccount\Entity $virtualAccount): Entity
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
            return $existingBankingAcc;
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

        return $bankingAccount;
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
            $mailer = new XProActivation($bankingAccount->toArray());

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

    protected function extractAndValidateActivationDetailInput(array &$input, $entity = null)
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

    protected function preProcessActivationDetailCreateInput(array $input = null)
    {
        if (empty($input) === true)
        {
            return $input;
        }

        if (isset($input[ActivationDetail\Entity::ASSIGNEE_TEAM]) === false)
        {
            // defaulting to Ops as they are the default assignee
            $input[ActivationDetail\Entity::ASSIGNEE_TEAM] = 'ops';
        }

        return $input;
    }

    public function createBankingAccount(array $input, Merchant\Entity $merchant): Entity
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS, $input);

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

        // Pulling the activation details out as they are stored as part of
        // a different entity.
        // These details are only to be sent from admin auth.
        $activationDetailInput = $this->extractAndValidateActivationDetailInput($input);

        $activationDetailInput = $this->preProcessActivationDetailCreateInput($activationDetailInput);

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

        $this->repo->transaction(function() use ($bankingAccount, $merchant, $bankContent, $activationDetailInput)
        {
            $this->repo->saveOrFail($bankingAccount);

            if ($activationDetailInput !== null)
            {
                $this->activationDetailService->createForBankingAccount($bankingAccount->getPublicId(), $activationDetailInput);
            }

            $stateCore = new State\Core;

            $stateCore->captureNewBankingAccountState($bankingAccount, $merchant);

        });

        $this->notifyOpsAboutProActivation($bankingAccount);

        $this->notifyMerchantAboutUpdatedStatus($bankingAccount);

        $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
        $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);


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
     *                  'comment' : 'sampel comment',
     *                  'type' : 'external',
     *              }
     *      }
     * }
     * @param Entity $bankingAccount
     * @param array $input
     * @param Base\PublicEntity|null $entity
     * @param bool $isAutomatedUpdate
     * @return Entity
     * @throws BadRequestException
     * @throws LogicException
     */
    public function updateBankingAccount(Entity $bankingAccount, array $input, Base\PublicEntity $entity = null, bool $isAutomatedUpdate = false)
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

        $bankingAccount->edit($input);

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

            if ($bankingAccountStatusChanged === true)
            {
                $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
            }
            if ($bankingAccountSubStatusChanged === true)
            {
                $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);
            }

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

        return $bankingAccount;
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
        $bankingAccount = $this->repo->transaction(function () use ($bankingAccount, $input, $admin)
        {
            $channel = $bankingAccount->getChannel();

            $processor = $this->getProcessor($channel);

            $bankingAccount = $processor->activate($bankingAccount, $input);

            $merchant = $bankingAccount->merchant;

            $mode = $this->app['rzp.mode'];

            $balanceInfo = $this->getBalanceAttributesToSave($bankingAccount);

            $balance = (new Merchant\Balance\Core)->createBalanceForCurrentAccount($merchant, $balanceInfo, $mode);

            // Creating a contact of type 'rzp_fees' and a fund account related to it. To be used for fees recovery.
            $this->createRZPFeesContactAndFundAccount($merchant);

            $bankingAccount->balance()->associate($balance);

            $this->repo->saveOrFail($bankingAccount);

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

            return $bankingAccount;
        });

        // For Adding payout feature without RZP KYC
        (new Activate())->addPayoutFeatureForCurrentAccount($bankingAccount->merchant);

        $this->sendBankingCaActivationSmsIfApplicable($bankingAccount);

        $this->notifier->notify($bankingAccount, Event::STATUS_CHANGE);
        $this->notifier->notify($bankingAccount, Event::SUBSTATUS_CHANGE);

        return $bankingAccount;
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

    public function createRZPFeesContactAndFundAccount(Merchant\Entity $merchant)
    {
        $rzpFeesContacts = $this->repo->contact->fetch([
                                                           Contact\Entity::TYPE => Contact\Type::RZP_FEES
                                                       ],
                                                       $merchant->getId());

        if ($rzpFeesContacts->count() > 0)
        {
            $errorMessage = 'Merchant has an existing rzp_fees type contact';

            $errorData = [
                'merchant_id' => $merchant->getId()
            ];

            $this->sendSlackAlert($errorMessage, $errorData);

            throw new LogicException('Merchant has an existing rzp_fees type contact',
                                     ErrorCode::BAD_REQUEST_LOGIC_ERROR_MULTIPLE_RZP_FEES_CONTACT,
                                     $errorData);
        }

        $contact = (new Contact\Core)->createRZPFeesContact($merchant);

        (new FundAccount\Core)->createRZPFeesFundAccount($merchant, $contact);
    }

    protected function createScheduleTaskForFeeRecovery(Merchant\Balance\Entity $balance,
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

            $this->sendSlackAlert($errorMessage, null);

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

    public function fetchAndUpdateGatewayBalanceWrapper(array $input)
    {
        $validator = new Validator();

        $validator->validateInput(Validator::FETCH_GATEWAY_BALANCE, $input);

        $channel    = $input[Entity::CHANNEL];
        $merchantId = $input[Entity::MERCHANT_ID];

        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->getBankingAccountByMerchantIdAndChannel($merchantId, $channel);

        $bankingAccount = $this->fetchAndUpdateGatewayBalance($bankingAccount);

        return $bankingAccount;
    }

    /**
     * for CA, balance needs to be fetched from balance api provided by respective banks/gateways at regular frequency
     * which is agreed upon in SLA. This function will be used to fetch balance from gateway before making normal/queued
     * payouts depending upon balance_last_fetched_at.
     *
     * @param Entity $bankingAccount
     *
     * @return mixed
     */
    public function fetchAndUpdateGatewayBalance(Entity $bankingAccount)
    {
        $channel = $bankingAccount->getChannel();

        $gatewayProcessor = $this->getProcessor($channel);

        // every gateway processor must implement fetchGatewayBalance function. This function sends Mozart request
        // to fetch balance from gateway and return balance.
        try
        {
            $balance = $gatewayProcessor->fetchGatewayBalance($bankingAccount);

            $bankingAccount->setGatewayBalance($balance);

            $bankingAccount->setBalanceLastFetchedAt(Carbon::now()->getTimestamp());

            $this->repo->saveOrFail($bankingAccount);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_SUCCEEDED,
                [
                    Entity::CHANNEL                 => $channel,
                    Entity::MERCHANT_ID             => $bankingAccount->getMerchantId(),
                    Entity::ACCOUNT_NUMBER          => $bankingAccount->getAccountNumber(),
                    Entity::GATEWAY_BALANCE         => $bankingAccount->getGatewayBalance(),
                    Entity::BALANCE_LAST_FETCHED_AT => $bankingAccount->getBalanceLastFetchedAt(),
                ]);

            // Once gateway balance is fetched, this has to be updated in BAS Details table as well. Statement fetch will be initiated based on that table.
            $basDetailInput = array(
                BASDetails\Entity::ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
                BASDetails\Entity::CHANNEL          => $bankingAccount->getChannel(),
                BASDetails\Entity::MERCHANT_ID      => $bankingAccount->getMerchantId(),
                BASDetails\Entity::BALANCE_ID       => $bankingAccount->getBalanceId(),
                BASDetails\Entity::GATEWAY_BALANCE  => $bankingAccount->getGatewayBalance()
                );

            (new BASDetails\Core)->createOrUpdate($basDetailInput);
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_FETCH_AND_UPDATE_GATEWAY_BALANCE_REQUEST_FAILED,
                [
                    Entity::CHANNEL                 => $channel,
                    Entity::MERCHANT_ID             => $bankingAccount->getMerchantId(),
                    Entity::ACCOUNT_NUMBER          => $bankingAccount->getAccountNumber(),
                    Entity::GATEWAY_BALANCE         => $bankingAccount->getGatewayBalance(),
                    Entity::BALANCE_LAST_FETCHED_AT => $bankingAccount->getBalanceLastFetchedAt(),
                ]);
        }

        return $bankingAccount;
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
                'input'   => $input,
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

    public function getProcessor(string $channel): Gateway\Processor
    {
        $processor = __NAMESPACE__ . '\\' . 'Gateway';

        $processor .= '\\' . studly_case($channel) . '\\' . 'Processor';

        if (class_exists($processor) === true)
        {
            return new $processor;
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

        // different limit for each channel
        $limit = $this->getGatewayBalanceUpdateRateLimit($channel);

        // get list of merchants based upon channel and balance last fetched at
        $merchantIds = $this->repo->banking_account
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

    protected function dispatchGatewayBalanceUpdateJob(string $channel, $merchantId)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_REQUEST,
            [
                Entity::CHANNEL     => $channel,
                Entity::MERCHANT_ID => $merchantId,
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
}
