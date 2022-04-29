<?php

namespace RZP\Models\BankingAccount;

use Throwable;
use Carbon\Carbon;
use Mail;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccountService;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Mail\BankingAccount\UpdatesForAuditor;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\Gateway\Rbl\RequestResponseFormatting;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\DiscrepancyInDoc;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantPreparingDoc;

class Service extends Base\Service
{
    protected $pincodeSearch;

    protected $config;

    protected $core;

    protected $notifier;

    public function __construct($pincodeSearch = null, $core = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->pincodeSearch = $pincodeSearch ?? $this->app['pincodesearch'];

        $this->notifier = new Activation\Notification\Notifier();
    }

    public function fetch(string $id): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicIdAndMerchant($id, $this->merchant);

        $bankingAccount->load('bankingAccountActivationDetails');

        return $bankingAccount->toArrayPublic();
    }

    public function create(array $input): array
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE,
            [
                'input' => $this->core->scrubBankingAccountSensitiveDetails($input),
            ]);

        $this->validateOrgForBankingAccount($input[Entity::CHANNEL]);

        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS, $input);

        // Pulling the activation details out as they are stored as part of
        // a different entity.
        // These details are only to be sent from admin auth.
        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $activationDetailInput = $this->preProcessActivationDetailCreateInput($activationDetailInput);

        $account = $this->core->createBankingAccount($input, $this->merchant, $activationDetailInput, 'create_normal');

        return $account->toArrayPublic();

    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function createByMerchant(array $input): array
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE_FROM_DASHBOARD,
            [
                'input' => $input,
            ]);

        $this->validateOrgForBankingAccount($input[Entity::CHANNEL]);

        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS_DASHBOARD, $input);

        $resp = $this->checkPincodeAndBusinessType($input);

        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $activationDetailInput = $this->preProcessActivationDetailCreateInput($activationDetailInput);

        if ($resp['serviceability'] === false OR $resp['business_type_supported'] === false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UNSERVICEABLE_REQUEST,
                [
                    $input
                ]);

            $this->fireHubspotEventForUnserviceable($resp);

            return $resp;
        }
        else
        {
            $this->fireHubspotEventForApplicationStarted();
        }

        if (is_null($activationDetailInput) === false)
        {
            (new Activation\Detail\Validator)->setStrictFalse()->validateInput('preProcess', $activationDetailInput);

            $activationDetailInput = $this->core->autofillStateAndCityFromPincode($activationDetailInput, $input);

            if (isset($activationDetailInput[ActivationDetail\Entity::SALES_TEAM]) === true)
            {
                $activationDetailInput = $this->autofillSelfServeFields($activationDetailInput);
            }
        }
        else
        {
            throw new BadRequestValidationFailureException(
                'The activation Detail is required',
                'ActivationDetailInput');
        }

        $account = $this->core->createBankingAccount($input, $this->merchant, $activationDetailInput, 'create_dashboard');

        // Adding rbl Pincode serviceability and businessType supported to response
        return array_merge($account->toArrayPublic() , $resp);
    }

    /**
     * @param array $input
     *
     * @return Entity
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function createInternal(array $input): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE_FOR_RBL_LEADS,
            [
                'input' => $input,
            ]);

        $this->validateOrgForBankingAccount($input[Entity::CHANNEL]);

        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS_DASHBOARD, $input);

        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $activationDetailInput = $this->preProcessActivationDetailCreateInput($activationDetailInput);

        return $this->core->createBankingAccount($input, $this->merchant, $activationDetailInput, 'create_co_created');
    }

    /**
     * @throws \Exception
     */
    public function createMerchantAndBankingEntities(array $input): array
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE_FROM_RBL_LEAD_API,
            [
                'input' => $input,
            ]);

        $requestResponseFormatting = new RequestResponseFormatting();

        try
        {
            (new Validator)->validateInput(Validator::CREATE_LEAD_FROM_RBL, $input);
        }
        catch (Throwable $e)
        {
            return $requestResponseFormatting->processErrorAndReturnResponse($input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::HEADER], Gateway\Rbl\Status::FAIL, $e);
        }

        try
        {
            $this->repo->transaction(function() use ($input, $requestResponseFormatting){

                $this->createMerchantAndSetContext($input);

                $bankingAccountCreatePayload = $requestResponseFormatting->extractBankingAccountPayload($input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY]);

                $bankingAccount = $this->createInternal($bankingAccountCreatePayload);

                $attributes = $requestResponseFormatting->extractBankingEntityUpdatePayload($input);

                $this->core->updateBankingAccount($bankingAccount, $attributes, $bankingAccount->merchant, true);
            });
        }
        catch (Throwable $e)
        {
            return $requestResponseFormatting->processErrorAndReturnResponse($input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::HEADER], "", $e);
        }

        return $requestResponseFormatting->processErrorAndReturnResponse($input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::HEADER], Gateway\Rbl\Status::SUCCESS);
    }

    /**
     * This function to be used only for admin or internal routes since
     * we are not fetching banking_account by merchant_id.
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function update(string $id, array $input): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $previousStatus = $bankingAccount->getStatus();

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        $account = $this->core->updateBankingAccount($bankingAccount, $input, $admin);

        $currentStatus = $bankingAccount->getStatus();

        if ($this->isNeoStoneExperiment($account) === false)
        {
            if ($previousStatus !== $currentStatus)
            {
                $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount);
            }
        }

        return $account->toArrayPublic();
    }

    public function updateByMerchant(string $id, array $input): array
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $previousStatus = $bankingAccount->getStatus();

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $input,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

        $bankingAccount->load('bankingAccountActivationDetails');

        $resp = $this->checkPincodeAndBusinessType($input);

        if ($resp['serviceability'] === false OR $resp['business_type_supported'] === false)
        {
            return $bankingAccount->toArrayPublic() + $resp;
        }

        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $ca_channel = Entity::Neostone;

        if (is_null($activationDetailInput) === false)
        {
            $activationDetailInput = $this->core->autofillStateAndCityFromPincode($activationDetailInput, $input);

            $this->checkIfPersonalDetailFilledAndFireEvent($bankingAccount, $activationDetailInput, $ca_channel);

            $this->checkIfApplicationCompleteAndFireEvent($bankingAccount, $activationDetailInput, $ca_channel);

            $activationDetailInput = ['activation_detail' => $activationDetailInput];

            $input = $input + $activationDetailInput;
        }

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        $account = $this->core->updateBankingAccount($bankingAccount, $input, $admin, false, true);

        $currentStatus = $bankingAccount->getStatus();

        if ($previousStatus !== $currentStatus)
        {
            $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount);
        }

        return array_merge($account->toArrayPublic(), $resp);
    }

    public function activate(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_REQUEST,
            [
                'id'=> $id
            ]);

        //
        // This route is to be used via Admin auth only.
        // Don't use this on proxy auth
        //
        if ($this->auth->isAdminAuth() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_PERMITTED_ONLY_ON_ADMIN_AUTH);
        }

        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        // validating if user tries to add/change credentials
        // after his account gets activated successfully

        $this->checkIfAccountAlreadyActivated($bankingAccount);

        $admin = $this->app['basicauth']->getAdmin();

        $bankingAccount = $this->core->activate($bankingAccount, $input, $admin);

        if ($this->isNeoStoneExperiment($bankingAccount) === false)
        {
            $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount);
        }

        return $bankingAccount->toArrayPublic();
    }

    public function addOrRemoveServiceablePincodes(array $input, $channel)
    {
        $input[Entity::CHANNEL] = $channel;

        (new Validator)->validateInput(Validator::SERVICEABLE_PINCODE, $input);

        $coreMethod = $input[Entity::ACTION] . 'ServiceablePincodes';

        $this->core->$coreMethod($input[Entity::PINCODES], $channel);

        return ['success' => true];
    }

    public function fetchMultiple()
    {
        $bankingAccounts = $this->merchant->bankingAccounts;

        $bankingAccounts = (new BankingAccountService\Service())->fetchAccountDetailsFromBas($this->merchant->getMerchantId(), $bankingAccounts);

        $bankingAccounts = $bankingAccounts->load(Entity::BALANCE);

        foreach ($bankingAccounts as &$ba)
        {
            $balance = $ba->getBalance();

            if (empty($balance) === true)
            {
                continue;
            }

            if(($balance->getType() === Balance\Type::BANKING) &&
                ($balance->getAccountType() === Balance\AccountType::SHARED))
            {
                // Only call ledger when "ledger_journal_reads" is enabled on the merchant.
                if($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
                {
                    $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($this->merchant->getId(), $ba->getPublicId());
                    if (empty($ledgerResponse) === false)
                    {
                        $balanceAmount = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
                        $balance->setBalance($balanceAmount);
                    }

                    break;
                }
            }
        }

        return $bankingAccounts->toArrayPublic();
    }

    public function fetchActivatedAccounts()
    {
        $bankingAccounts = $this->fetchMultiple();

        return $this->core->filterActivatedAccountsAndMaskAccountNumber($bankingAccounts);
    }

    public function processAccountInfoWebhook(string $channel, array $input)
    {
        $response = $this->core->processAccountInfoWebhook($channel, $input);

        return $response;
    }

    public function bulkCreateBankingAccountsForYesbank(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_YESBANK_BULK_CREATE_REQUEST,
            [
                'input'     => $input,
                'channel'   => Channel::YESBANK,
            ]);

        $response = $this->core->bulkCreateBankingAccountsForYesbank($input);

        return $response;
    }

    public function processGatewayBalanceUpdate(string $channel)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_PROCESS_GATEWAY_BALANCE_UPDATE_REQUEST,
            [
               'channel' => $channel,
            ]);

        $response = $this->core->dispatchGatewayBalanceUpdateForMerchants($channel);

        return $response;
    }

    protected function filterOutAssigneeChanges(array $statusChangeLog)
    {
        $itemsToRetain = [];

        $previousStatusChange = null;

        foreach ($statusChangeLog['items'] as $currentStatusChange)
        {
            if ($previousStatusChange === null)
            {
                $previousStatusChange = $currentStatusChange;
                $itemsToRetain[] = $currentStatusChange;
                continue;
            }
            $columnsToIgnore = [State\Entity::ID, State\Entity::CREATED_AT, State\Entity::UPDATED_AT];

            $diffWithPreviousStatusChange = array_diff_assoc(
                array_diff_key($previousStatusChange, array_flip($columnsToIgnore)),
                array_diff_key($currentStatusChange, array_flip($columnsToIgnore))
            );

            // if there are changes other that *just* assignee_team, then include it in the list.
            if (array_keys($diffWithPreviousStatusChange) !== [State\Entity::ASSIGNEE_TEAM])
            {
                $itemsToRetain[] = $currentStatusChange;
            }

            $previousStatusChange = $currentStatusChange;
        }

        $statusChangeLog['items'] = $itemsToRetain;

        $statusChangeLog['count'] = count($statusChangeLog['items']);

        return $statusChangeLog;
    }

    public function getActivationStatusChangeLog(string $bankingAccountId)
    {
        /** @var Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $activationStatusChangeLog = $this->core->getActivationStatusChangeLog($bankingAccount);

        $activationStatusChangeLog = $activationStatusChangeLog->toArrayPublic();

        return $this->filterOutAssigneeChanges($activationStatusChangeLog);
    }

    protected function checkIfAccountAlreadyActivated(Entity $bankingAccount)
    {
        if ($bankingAccount->getStatus() === Status::ACTIVATED)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_ACTIVATED,
                null,
                ['id' => $bankingAccount->getId()]
            );
        }
    }

    public function bulkAssignReviewer(array $input)
    {
        (new Validator)->validateInput('bulk_assign_reviewer', $input);

        $bankingAccountIds  = $input[Entity::BANKING_ACCOUNT_IDS];

        $reviewerId = $input[Entity::REVIEWER_ID];

        return (new Core)->bulkAssignReviewer($reviewerId, $bankingAccountIds);
    }

    public function prepareInputForUpdate(array $input, string $channel)
    {
        $requiredKeysForUpdateInput = [
            Entity::STATUS,
            Entity::SUB_STATUS,
            Entity::BANK_INTERNAL_STATUS,
        ];

        $requiredKeysForActivationDetailInput = [
            ActivationDetail\Entity::ASSIGNEE_TEAM,
            ActivationDetail\Entity::RM_NAME,
            ActivationDetail\Entity::RM_PHONE_NUMBER,
            ActivationDetail\Entity::ACCOUNT_OPEN_DATE,
            ActivationDetail\Entity::ACCOUNT_LOGIN_DATE,
            ActivationDetail\Entity::ADDITIONAL_DETAILS,
        ];

        $requiredKeysforCommentInput = [
            Comment\Entity::COMMENT,
            Comment\Entity::SOURCE_TEAM_TYPE,
            Comment\Entity::SOURCE_TEAM,
            Comment\Entity::ADDED_AT
        ];

        $requiredKeysForBackFillingData = [
            ActivationDetail\Entity::SALES_POC_EMAIL,
            ActivationDetail\Entity::SALES_TEAM,
        ];

        $commentInput = array_intersect_key($input, array_fill_keys($requiredKeysforCommentInput, ''));

        $updateInput = array_intersect_key($input, array_fill_keys($requiredKeysForUpdateInput, ''));

        $activationDetailInput = array_intersect_key($input, array_fill_keys($requiredKeysForActivationDetailInput, ''));

        $backFillDataInput = array_intersect_key($input, array_fill_keys($requiredKeysForBackFillingData, ''));

        $updateInput['activation_detail'] = $activationDetailInput;

        if(isset($backFillDataInput[ActivationDetail\Entity::SALES_POC_EMAIL]) === true)
        {
            $updateInput['activation_detail']['sales_poc_email'] = $backFillDataInput[ActivationDetail\Entity::SALES_POC_EMAIL];

            $updateInput['activation_detail']['sales_team'] = $backFillDataInput[ActivationDetail\Entity::SALES_TEAM];

        }

        if (empty($commentInput[Comment\Entity::COMMENT]) === false)
        {
            // hard coding to internal as we don't expect external comments to
            // be made via batch
            $commentInput[Comment\Entity::TYPE] = 'internal';

            $updateInput['activation_detail']['comment'] = $commentInput;
        }

        // removing whitespaces
        array_walk_recursive($updateInput, 'trim');

        // empty string indicates nothing to update. Therefore, excluding
        array_unset_recursive($updateInput, '');

        // Convert free text Status string to internally accepted status keys.
        if (isset($updateInput[Entity::STATUS]) === true)
        {
            $updateInput[Entity::STATUS] = trim($updateInput[Entity::STATUS]);

            $updateInput[Entity::STATUS] = Status::transformFromExternalToInternal($updateInput[Entity::STATUS]);
        }

        if (isset($updateInput[Entity::SUB_STATUS]) === true)
        {
            $updateInput[Entity::SUB_STATUS] = trim($updateInput[Entity::SUB_STATUS]);

            $updateInput[Entity::SUB_STATUS] = Status::transformSubStatusFromExternalToInternal($updateInput[Entity::SUB_STATUS]);
        }

        if (isset($updateInput[Entity::BANK_INTERNAL_STATUS]) === true)
        {
            $updateInput[Entity::BANK_INTERNAL_STATUS] = trim($updateInput[Entity::BANK_INTERNAL_STATUS]);

            $gatewayProcessor = $this->core->getProcessor($channel);

            $updateInput[Entity::BANK_INTERNAL_STATUS] = $gatewayProcessor->transformBankStatusFromExternalToInternal($updateInput[Entity::BANK_INTERNAL_STATUS]);
        }

        // if `null` string is sent for assignee team, then change it to null value.
        if(isset($updateInput['activation_detail'][ActivationDetail\Entity::ASSIGNEE_TEAM])
            && ($updateInput['activation_detail'][ActivationDetail\Entity::ASSIGNEE_TEAM] === 'null'))
        {
            $updateInput['activation_detail'][ActivationDetail\Entity::ASSIGNEE_TEAM] = null;
        }

        // Convert date strings to epoch
        $dateFields = [
            ActivationDetail\Entity::ACCOUNT_OPEN_DATE,
            ActivationDetail\Entity::ACCOUNT_LOGIN_DATE,
        ];

        foreach($dateFields as $dateField)
        {
            if (isset($updateInput['activation_detail'][$dateField]) === true)
            {
                $updateInput['activation_detail'][$dateField] =
                    strtoepoch($updateInput['activation_detail'][$dateField], 'd-M-Y', true);
            }
        }

        return $updateInput;
    }

    public function updateDetailsFromBatchService(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_UPDATE_DETAILS_FROM_BATCH,
            [
                'input' => $input,
                'batch_id' => $this->app['request']->header(RequestHeader::X_Batch_Id, null),
                'creator_id' => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
                'creator_type' => $this->app['request']->header(RequestHeader::X_Creator_Type, null)
            ]);

        try
        {
            $bankingAccount = $this->core->fetchByBankReferenceAndChannel(
                $input[Entity::CHANNEL],
                $input[Entity::BANK_REFERENCE_NUMBER]);

            $admin = $this->repo->admin->findOrFailPublic($input[Entity::ADMIN_ID]);

            // Storing admin interpreted via batch in app
            // so that downstream services like BankingAccountComment
            // can retrieve it directly (as opposed to passing admin through
            // various classes.
            // TODO: Ideally, should be handled in the middleware.
            $this->app->bind('batchAdmin', function() use($admin) {
               return $admin;
            });

        }
        catch (Throwable $e)
        {
            // TODO: throw different validation errors for both the find queries.
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null,
                [
                    Entity::BANK_REFERENCE_NUMBER => $input[Entity::BANK_REFERENCE_NUMBER],
                    Entity::CHANNEL => $input[Entity::CHANNEL],
                    Entity::ADMIN_ID => $input[Entity::ADMIN_ID]
                ]);
        }

        // Catch all errors because batch service fails silently if you return 5xx.
        // TODO: solve cleanly
        try
        {
            $updateInput = $this->prepareInputForUpdate($input, $input[Entity::CHANNEL]);

            $this->update($bankingAccount->getPublicId(), $updateInput);
        }
        catch (Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [], $e->getMessage());
        }

        return [
            'status' => 'success'
        ];
    }

    public function downloadActivationMis(array $input)
    {
        $misType = array_pull($input, 'mis_type');

        $misProcessor = Activation\MIS\Factory::getProcessor($misType, $input);

        return $misProcessor->generate();
    }

    /*
     * This is a cron route which is to be called every day at 9 am.
     * This will send updates(maily comments made against BankingAccount)
     * in the last day to Spocs
     */
    public function sendDailyUpdatesToAuditors(string $auditorType)
    {
        // getting timestamps
        $today = Carbon::today(Timezone::IST)->hour(9)->getTimestamp();
        $yesterday = Carbon::yesterday(Timezone::IST)->hour(9)->getTimestamp();

        $this->trace->info(TraceCode::BANKING_ACCOUNT_AUDITOR_SEND_UPDATES_REQUEST,
            [
                'auditor_type' => $auditorType
            ]);

        try
        {
            $requiredUpdates = $this->getRequiredUpdatesForAuditors($auditorType, $yesterday, $today);

            foreach ($requiredUpdates as $auditorEmail => $requiredAuditorUpdates)
            {
                $auditorName = array_pull($requiredAuditorUpdates, 'name');

                $this->trace->info(TraceCode::BANKING_ACCOUNT_AUDITOR_UPDATES,
                    [
                        'auditor_email'     => $auditorEmail,
                        'auditor_name'      => $auditorName,
                        'updates'           => $requiredAuditorUpdates
                    ]);

                $mailable = new UpdatesForAuditor($auditorEmail, $auditorName, $requiredAuditorUpdates);

                Mail::queue($mailable);
            }
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_AUDITOR_SEND_UPDATES_FAILED,
                [
                    'auditor_type' => $auditorType
                ]);
        }
        return [];
    }

    public function getRequiredUpdatesForAuditors(string $auditorType, int $fromTs, int $toTs)
    {
        $requiredUpdates = [];

        (new Validator)->validateAuditorTypeForDailyUpdates($auditorType);

        // for spoc
        $spocGroupedComments = $this->repo->banking_account_comment->fetchCommentsMadeBetweenForSpoc($fromTs, $toTs);

        foreach($spocGroupedComments as $spocEmail => $spocEmailComments)
        {
            if (empty($spocEmail) === false)
            {
                $commentsInfo = [];

                foreach($spocEmailComments as $comment)
                {
                    $commentsInfo[] = $this->getCommentInfo($comment);
                }

                $requiredUpdates[$spocEmail] =
                    [
                        // all will have same name since it's in the same group. Using first as reference.
                        'name'     => $spocEmailComments->first()->bankingAccount->spocs()->first()['name'],
                        'comments' => $commentsInfo
                    ];
            }
        }

        return $requiredUpdates;
    }

    protected function getCommentInfo(Comment\Entity $comment)
    {
        $commentInfo = $comment->toArrayPublic();
        $commentInfo['comment'] = strip_tags($commentInfo['comment']);
        $commentInfo['bank_reference_number'] = $comment->bankingAccount->getBankReferenceNumber();
        $commentInfo['merchant_id'] = $comment->bankingAccount->getMerchantId();
        $commentInfo['status'] = $comment->bankingAccount->getStatusForExternalDisplay();
        $commentInfo['sub_status'] = $comment->bankingAccount->getSubStatusForExternalDisplay();

        $commentInfo['business_name'] = $comment->bankingAccount->merchant->merchantDetail->getBusinessName();
        $commentInfo['admin_dashboard_link'] = $comment->bankingAccount->getDashboardEntityLink();
        $commentInfo['created_at'] = Carbon::createFromTimestamp($commentInfo['created_at'], Timezone::IST)->format('d-M-y H:i');
        return $commentInfo;
    }

    public function getBankingAccountSalesPOCs()
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        // Ideally this should be some specific permission that is assigned
        // to every sales team member. But this is not present right now.
        // going with a hack to use `view_activation_form` permission instead.
        $permission = $this->repo
            ->permission
            ->findByOrgIdAndPermission($orgId, Permission\Name::VIEW_ACTIVATION_FORM);

        if (empty($permission) === true)
        {
            throw new Exception\RuntimeException('Missing Permission');
        }

        $admins = [];

        foreach ($permission->roles as $role)
        {
            foreach ($role->admins as $roleAdmin)
            {
                $admins[] = $roleAdmin->toArrayPublic();
            }
        }

        return multidim_array_unique($admins, Admin\Entity::ID);
    }

    public function CheckServiceableByRBL($pinCode, bool $includeIcici = false): array
    {
        $errorMessage = "PINCODE is not valid";

        $isAdmin = $this->app['basicauth']->isAdminAuth();

        $serviceablePincode = new ServiceablePincodes();

        if ($includeIcici === true and $isAdmin === false)
        {
            $isWhiteListed = $serviceablePincode->checkIfPincodeIsWhitelisted($pinCode);

            if ($isWhiteListed === true)
            {
                return ['serviceability' => true,
                        'errorMessage'   => null];
            }
        }

        if ($serviceablePincode->checkIfPincodeIsUnserviceableByRBl($pinCode) === true)
        {
            return ['serviceability' => false,
                'errorMessage'   => null];
        }

        try
        {
            $this->pincodeSearch->fetchCityAndStateFromPincode($pinCode);
        }
        catch (BadRequestException | BadRequestValidationFailureException | IntegrationException $e)
        {
            if ($e->getMessage() == 'Third Party Error' or $e->getMessage() == 'Something Went Wrong')
            {
                $this->trace->error(TraceCode::PINCODE_SEARCH_ERROR, [$pinCode, $e->getMessage()]);

                throw $e;
            }
            else
            {
                return ['serviceability' => false,
                        'errorMessage'   => $errorMessage];
            }
        }

        try
        {
            [$lat1, $lng1, $er] = $this->core->getLocationFromPincode($pinCode);
        }
        catch (BadRequestException | IntegrationException | Exception\RuntimeException $e)
        {
            $this->trace->error(TraceCode::GOOGLE_MAP_REQUEST_FAILED, [$pinCode, $e->getMessage()]);

            throw $e;
        }

        if ($er != null)
        {
            return ['serviceability' => false,
                    'errorMessage'   => $er];
        }

        return ['serviceability' => $this->core->checkIfServiceableByRBL($lat1, $lng1),
                'errorMessage'   => null];
    }

    public function resetWebhookData(string $id)
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_WEBHOOK_DATA_RESET,
            [
                'id' => $bankingAccount->getId(),
                'channel' => $channel,
            ]);

        if($bankingAccount->getStatus() !== Status::PROCESSED)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_WEBHOOK_RESET_NOT_ALLOWED_FOR_CURRENT_STATUS);
        }

        $stateChangeLogBeforeProcessedState = $this->getStatusChangeLogBeforeProcessedState($bankingAccount);

        $admin = $this->app['basicauth']->getAdmin();

        $account = $this->core->resetAccountInfoWebhookData($bankingAccount, $stateChangeLogBeforeProcessedState, $admin);

        return $account->toArrayPublic();
    }

    /**
     * @param $bankingAccount
     * @return mixed
     */
    public function getStatusChangeLogBeforeProcessedState(Entity $bankingAccount)
    {
        $stateChangeLogArray = $this->core->getActivationStatusChangeLog($bankingAccount);

        $totalStateChangeLogsCount = count($stateChangeLogArray);

        $stateChangeLogBeforeProcessedState = $stateChangeLogArray[$totalStateChangeLogsCount - 2];

        $currentStatus =  $stateChangeLogArray[$totalStateChangeLogsCount - 1]['status'];

        for ($i = $totalStateChangeLogsCount - 2; $i >= 0; $i--) {
            if ($stateChangeLogArray[$i]['status'] != $currentStatus) {
                $stateChangeLogBeforeProcessedState = $stateChangeLogArray[$i];
                break;
            }
        }
        return $stateChangeLogBeforeProcessedState;
    }

    protected function checkBusinessType(string $businessType): bool
    {
        return in_array($businessType, ActivationDetail\Validator::$allowedBusinessCategories);
    }

    /**
     * If input is null true is returned
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws Exception\RuntimeException
     * @throws IntegrationException
     */
    protected function checkPincodeAndBusinessType(array $input): array
    {
        $serviceability = null;

        if (isset($input['pincode']) === true)
        {
            $pincode = $input[Entity::PINCODE];

            $serviceability = $this->CheckServiceableByRBL($pincode, false);
        }

        $businessTypeSupported = true;

        if (isset($input['activation_detail']) === true)
        {
            if (isset($input['activation_detail'][ActivationDetail\Entity::BUSINESS_CATEGORY]) === true)
            {
                $businessTypeSupported = $this->checkBusinessType($input['activation_detail'][ActivationDetail\Entity::BUSINESS_CATEGORY]);
            }
        }

        if ($serviceability === null)
        {
            return [
                'business_type_supported' => $businessTypeSupported,
                'serviceability' => true,
                'errorMessage' => null
            ] ;
        }
        return [
            'business_type_supported' => $businessTypeSupported,
            'serviceability' => $serviceability['serviceability'],
            'errorMessage' => $serviceability['errorMessage']
        ] ;
    }

    /**
     * Get banking account from account number
     *
     * @param string $accountNumber
     * @param string $merchantId
     * @return array
     */
    public function fetchBankingAccountForAccountNumber(string $accountNumber, string $merchantId)
    {
        $this->trace->info(
            TraceCode::FETCH_BANKING_ACCOUNT_FOR_PAYOUT_SERVICE,
            [
                Entity::MERCHANT_ID => $merchantId,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::FETCH_BANKING_ACCOUNT_PAYOUT_SERVICE,
            [
                Entity::ACCOUNT_NUMBER => $accountNumber,
                Entity::MERCHANT_ID    => $merchantId
            ]);

        $bankingAccount = $this->repo->banking_account->getBankingAccountWithBalanceViaAccountNumberAndMerchantId($accountNumber, $merchantId);

        $this->trace->info(
            TraceCode::FETCHED_BANKING_ACCOUNT_FOR_PAYOUT_SERVICE,
            [
                Entity::MERCHANT_ID        => $merchantId,
                Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId()
            ]);

        return [
            Entity::ID                   => $bankingAccount->getId(),
            Entity::STATUS               => $bankingAccount->getStatus(),
            Entity::CHANNEL              => $bankingAccount->getChannel(),
            Entity::BALANCE_ID           => $bankingAccount->getBalanceId(),
            Entity::MERCHANT_ID          => $bankingAccount->getMerchantId(),
            Entity::ACCOUNT_NUMBER       => $bankingAccount->getAccountNumber(),
            Entity::ACCOUNT_TYPE         => $bankingAccount->balance->getAccountType(),
            Entity::BALANCE_TYPE         => $bankingAccount->balance->getType(),
            Entity::FTS_FUND_ACCOUNT_ID  => $bankingAccount->getFtsFundAccountId()
        ];
    }

    /**
     * Get banking account Beneficiary from account number and ifsc
     *
     * @param string $accountNumber
     * @param string $ifsc
     * @return array
     */
    public function fetchBankingAccountBeneficiary(string $accountNumber, string $ifsc)
    {
        $errorMessage = "Account Number/IFSC combination not present";

        (new Validator)->setStrictFalse()->validateInput(Validator::FETCH_BANKING_ACCOUNT_IFSC_SERVICE,
            [
                Entity::ACCOUNT_NUMBER          => $accountNumber,
                Entity::ACCOUNT_IFSC            => $ifsc
            ]);

        $bankingAccount = $this->repo->banking_account->getBankingAccountViaAccountNumberAndIfsc($accountNumber, $ifsc);

        if (is_null($bankingAccount) === true) {

            return [
                Entity::BENEFICIARY_NAME        => null,
                Entity::STATUS                  => null,
                'errorMessage'                  => $errorMessage
            ];
        }

        $this->trace->info(
            TraceCode::FETCHED_BANKING_ACCOUNT_BENEFICIARY,
            [
                Entity::ACCOUNT_IFSC            => $ifsc,
                Entity::BANKING_ACCOUNT_ID      => $bankingAccount->getId()
            ]);

        return [
            Entity::BENEFICIARY_NAME            => $bankingAccount->getBeneficiaryName(),
            Entity::STATUS                      => $bankingAccount->getStatus(),
            'errorMessage'                      => null
        ];
    }

    protected function preProcessActivationDetailCreateInput(array $input = null): ?array
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

    private function autofillSelfServeFields(array $activation_detail): array
    {
        if ($activation_detail[ActivationDetail\Entity::SALES_TEAM] === ActivationDetail\Validator::SELF_SERVE)
        {
            $activation_detail[ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE] = 20000;

            $activation_detail[ActivationDetail\Entity::ACCOUNT_TYPE] = ActivationDetail\Validator::BUSINESS_PLUS;

            $activation_detail[ActivationDetail\Entity::INITIAL_CHEQUE_VALUE] = 20000;

            $activation_detail[ActivationDetail\Entity::EXPECTED_MONTHLY_GMV] = 20000;
        }

        return $activation_detail;
    }

    protected function checkIfApplicationCompleteAndFireEvent(Entity $bankingAccount, array $activation_detail, string $channel)
    {
        if (isset($activation_detail[ActivationDetail\Entity::DECLARATION_STEP]) === true)
        {
            if ($activation_detail[ActivationDetail\Entity::DECLARATION_STEP] === 1)
            {
                $payload = ['ca_channel' => $channel];

                $this->notifier->notify($bankingAccount, Event::APPLICATION_RECEIVED, Event::INFO, $payload);
            }
        }
    }

    private function fireHubspotEventForUnserviceable(array $resp)
    {
        $merchantEmail = ($this->merchant)->getEmail();

        if ($resp['serviceability'] === false and $resp['business_type_supported'] === false)
        {
            $payload = ['ca_pincode_business_type_not_supported' => 'TRUE'];
        }
        else if ($resp['business_type_supported'] === false)
        {
            $payload = ['ca_business_type_not_supported' => 'TRUE'];
        }
        else
        {
            $payload = ['ca_pincode_not_serviceable' => 'TRUE'];
        }

        $this->app->hubspot->trackHubspotEvent($merchantEmail, $payload);
    }

    public function isNeoStoneExperiment(Entity $bankingAccount): bool
    {
        $bankingAccountActivation = $bankingAccount->bankingAccountActivationDetails;

        $this->trace->info(
            TraceCode::CHECK_NEOSTONE,
            [
                $bankingAccount->merchant->getMerchantId(),
                'banking_account_activation_detail' => is_null($bankingAccountActivation)
            ]);

        if (is_null($bankingAccountActivation) === true)
        {
            return false;
        }

        $this->trace->info(
            TraceCode::NEOSTONE_MERCHANT_TRUE,
            [
                $bankingAccount->merchant->getMerchantId(),
                'contact_verified'  => $bankingAccountActivation->getContactVerified()
            ]);

        // For neostone we are verifying merchant contact with otp
        return ($bankingAccountActivation->getContactVerified() === 1);
    }

    private function checkIfPersonalDetailFilledAndFireEvent(Entity $bankingAccount, array $activationDetailInput, string $channel)
    {
        if (isset($activationDetailInput[ActivationDetail\Entity::MERCHANT_POC_NAME]) === true)
        {
            $payload = ['ca_channel' => $channel];

            $this->notifier->notify($bankingAccount, Event::PERSONAL_DETAILS_FILLED, Event::INFO, $payload);
        }
    }

    private function fireHubspotEventForApplicationStarted()
    {
        $merchantEmail = ($this->merchant)->getEmail();

        $payload = ['ca_started_application' => 'TRUE'];

        $this->app->hubspot->trackHubspotEvent($merchantEmail, $payload);
    }

    /**
     * @param $channel
     * @throws BadRequestException
     */
    private function validateOrgForBankingAccount(string $channel)
    {
        if ($this->merchant->getOrgId() !== '100000razorpay') {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED_CA_ONBOARDING_CANNOT_INITIATED_FOR_NON_RZP_ORG_MERCHANTS,
                Entity::MERCHANT_ID,
                [
                    'merchant_id' => $this->merchant->getPublicId(),
                    'channel' => $channel
                ],
                'Current account on-boarding cannot be initiated for the Non Razorpay org merchants.');
        }
    }

    public function notifyToSPOC(): array
    {
        try
        {
            $this->notifyForMerchantPreparingDoc();

            $this->notifyForDiscrepancyInDoc();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_NOTIFICATION_TO_SPOC_FAILED);
        }

        return [];
    }

    private function notifyForMerchantPreparingDoc()
    {
        $stateRepo = new State\Repository();

        $spocGroupedBankingAccountStates = $stateRepo->getBankingAccountsStateBySubStateAndCreatedBetween(Status::MERCHANT_PREPARING_DOCS, strtotime('- 6 day'), strtotime('- 5 day'));

        foreach ($spocGroupedBankingAccountStates as $spocEmail => $bankingAccountStates)
        {
            if (empty($spocEmail) === false and empty($bankingAccountStates) == false)
            {
                $finalBankingAccountStates = [];

                foreach ($bankingAccountStates as $bankingAccountState)
                {
                    $bankingAccount = $bankingAccountState->bankingAccount;

                    if ($bankingAccountState->getSubStatus() === $bankingAccount->getSubStatus())
                    {
                        array_push($finalBankingAccountStates, $bankingAccountState);
                    }
                }

                $mailable = new MerchantPreparingDoc($finalBankingAccountStates, $spocEmail);

                Mail::queue($mailable);
            }
        }
    }

    private function notifyForDiscrepancyInDoc()
    {
        $stateRepo = new State\Repository();

        $spocGroupedBankingAccountStates = $stateRepo->getBankingAccountsStateBySubStateAndCreatedBetween(Status::DISCREPANCY_IN_DOCS, strtotime('- 6 day'), strtotime('- 5 day'));

        foreach ($spocGroupedBankingAccountStates as $spocEmail => $bankingAccountStates)
        {
            if (empty($spocEmail) === false and empty($bankingAccountStates) == false)
            {
                $finalBankingAccountStates = [];

                foreach ($bankingAccountStates as $bankingAccountState)
                {
                    $bankingAccount = $bankingAccountState->bankingAccount;

                    if ($bankingAccountState->getSubStatus() === $bankingAccount->getSubStatus())
                    {
                        array_push($finalBankingAccountStates, $bankingAccountState);
                    }
                }

                $mailable = new DiscrepancyInDoc($finalBankingAccountStates, $spocEmail);

                Mail::queue($mailable);
            }
        }
    }

    /**
     * @param $id
     *
     * @return void
     */
    private function setMerchantContext($id): void
    {
        $merchantEntity = $this->repo->merchant->find($id);

        $this->auth->setMerchant($merchantEntity);

        $this->merchant = $merchantEntity;
    }

    /**
     * @param array $input
     *
     * @return void
     * @throws BadRequestException
     * @throws \Exception
     * @throws Throwable
     */
    private function createMerchantAndSetContext(array $input): void
    {
        $this->auth->setRequestOriginProduct(ProductType::BANKING);

        $requestResponseFormatting = new RequestResponseFormatting();

        $merchantCreatePayload = $requestResponseFormatting->extractMerchantCreatePayload($input);

        $merchant = (new \RZP\Models\User\Service)->registerInternal($merchantCreatePayload);

        $this->setMerchantContext($merchant['id']);

        $preSignupDetails = $requestResponseFormatting->getPreSignupPayload($input);

        (new \RZP\Models\Merchant\Detail\Service)->editPreSignupDetails($preSignupDetails);

        $payload = [
            'merchant_id' => $merchant['id'],
            'x_onboarding_category'   => 'co-created'
        ];

        $this->app->salesforce->sendXOnboardingToSalesforce($payload);
    }
}
