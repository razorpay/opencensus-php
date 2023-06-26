<?php

namespace RZP\Models\BankingAccount;

use Throwable;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Services\CapitalCardsClient;
use RZP\Models\BankingAccountService;
use RZP\Exception\BadRequestException;
use Illuminate\Support\Facades\Mail;
use RZP\Exception\IntegrationException;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Models\SubVirtualAccount as SubVA;
use RZP\Mail\BankingAccount\UpdatesForAuditor;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Jobs\BankingAccount\BankingAccountRblMisReport;
use RZP\Mail\BankingAccount\DocketMail\DocketMail;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\Gateway\Rbl\RequestResponseFormatting;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\DiscrepancyInDoc;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantPreparingDoc;
use RZP\Models\BankingAccount\Activation\MIS\Leads;

class Service extends Base\Service
{
    protected $pincodeSearch;

    protected $config;

    /** @var Core $core*/
    protected $core;

    protected $notifier;

    /** @var BankingAccountService\Service $bankingAccountService */
    protected $bankingAccountService;

    public function __construct($pincodeSearch = null, $core = null, $bankLmsService = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->pincodeSearch = $pincodeSearch ?? $this->app['pincodesearch'];

        $this->notifier = new Activation\Notification\Notifier();

        $this->bankingAccountService = new BankingAccountService\Service();
    }

    public function createBankingAccountForCapitalCorpCard($input, Balance\Entity $balance): Entity
    {
        $this->trace->info(
            TraceCode::CREATE_CORP_CARD_BANKING_ACCOUNT,
            [
                'input' => $this->core->scrubBankingAccountSensitiveDetails($input),
            ]);

        /* Commented for now, since corp card is given non rzp merchants also.
         * $this->validateOrgForBankingAccount($input[Entity::CHANNEL]);
         */
        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS, $input);

        return $this->core->createCapitalCorpCardBankingAccount($input, $balance->merchant, $balance);

    }

    public function fetch(string $id): array
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($id);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_FETCH_RBL_APPLICATION_FROM_BAS,
                [
                    'id'      => $id,
                ]);

            $id = $this->repo->banking_account->verifyIdAndStripSign($id);

            return $this->bankingAccountService->getRblApplicationFromBasForInternalFetch($id);
        }

        if ($bankingAccount->getMerchantId() != $this->merchant->getId())
        {
            throw new BadRequestValidationFailureException(
                'Banking Account does not belong to merchant',
                'merchant_id'
            );
        }

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

        $validatorOp = 'create_normal';
        if ($this->auth->getInternalApp() === 'salesforce')
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_CREATE_FROM_SALESFORCE,
                [
                    'input' => $this->core->scrubBankingAccountSensitiveDetails($input),
                ]);

            $validatorOp = 'create';
        }

        $account = $this->core->createBankingAccount($input, $this->merchant, $activationDetailInput, $validatorOp);

        return $account->toArrayPublic();

    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     * @throws Throwable
     */
    public function createByMerchant(array $input): array
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_CREATE_FROM_DASHBOARD,
            [
                'input' => $input,
            ]);

        if ($this->app['basicauth']->isMobApp() and $this->core->checkRblOnBasExperimentEnabled($this->merchant->getId()))
        {
            return $this->bankingAccountService->createRblOnboardingApplicationOnBas($this->merchant, $input);
        }

        $this->validateOrgForBankingAccount($input[Entity::CHANNEL]);

        (new Validator)->setStrictFalse()->validateInput(Validator::PRE_PROCESS_DASHBOARD, $input);

        $serviceabilityResponse = $this->checkPincodeBusinessTypeAndFillMerchantAddress($input);

        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $activationDetailInput = $this->preProcessActivationDetailCreateInput($activationDetailInput);

        if ($serviceabilityResponse['serviceability'] === false OR $serviceabilityResponse['business_type_supported'] === false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_UNSERVICEABLE_REQUEST,
                [
                    $input
                ]);

            $this->fireHubspotEventForUnserviceable($serviceabilityResponse);

            return $serviceabilityResponse;
        }
        else
        {
            $this->fireHubspotEventForApplicationStarted();
        }

        if (is_null($activationDetailInput) === false)
        {
            (new Activation\Detail\Validator)->setStrictFalse()->validateInput('preProcess', $activationDetailInput);

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
        return array_merge($account->toArrayPublic() , $serviceabilityResponse);
    }

    public function isFosLead(Entity $bankingAccount)
    {

        $pinCode = $bankingAccount->getPincode();

        if (empty($pinCode))
        {
            return false;
        }

        try
        {
            $resp = $this->pincodeSearch->fetchCityAndStateFromPincode($pinCode);
        }
        catch (BadRequestException|BadRequestValidationFailureException|IntegrationException $e)
        {
            $this->trace->error(TraceCode::PINCODE_SEARCH_ERROR, [$pinCode, $e->getMessage()]);

            return false;
        }

        if ((isset($resp['city']) === true) && ((new Validator())->checkFosLeadCities($resp['city'])))
        {
            return true;
        }

        return false;
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
     * Check if the banking account id exists in db
     *
     * To be used to route request to BAS in case the application does not exist on API
     *
     * @param string $id
     */
    public function checkAndGetBankingAccountId(string $id)
    {
        try
        {
            /** @var Entity $bankingAccount */
            $bankingAccount = $this->repo->banking_account->findByPublicId($id);

            return [true, $bankingAccount];
        }
        catch (\RZP\Exception\BadRequestException $ex)
        {
            if ($ex->getCode() === ErrorCode::BAD_REQUEST_INVALID_ID)
            {
                return [false, null];
            }
        }
        catch (\Throwable $ex)
        {
            throw $ex;
        }
    }

    public function updateApplicationOnBasByReferenceNumber(string $referenceNumber, array $input)
    {
        return $this->bankingAccountService->updateRBLApplicationByApplicationIdOrReferenceNumber($referenceNumber, $input);
    }

    public function updateApplicationOnBas(string $id, array $input)
    {
        if (str_starts_with($id, 'bacc'))
        {
            $id = $this->repo->banking_account->verifyIdAndStripSign($id);
        }

        return $this->bankingAccountService->updateRBLApplicationByApplicationIdOrReferenceNumber($id, $input);
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
    public function update(string $id, array $input, bool $fromPartnerDashboard = false): array
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($id);

        // Remove sensitive fields for tracing purposes
        $inputTrace = $input;

        foreach(BankingAccountService\Service::SENSITIVE_UPDATE_FIELDS as $sensitiveAccountDetailKey)
        {
            unset($inputTrace[$sensitiveAccountDetailKey]);
        }

        if ($existsInApi === false)
        {

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE,
                [
                    'stage'   => 'Validate request for internal edit',
                    'id'      => $id,
                    'input'   => $inputTrace,
                ]);

            (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

            return $this->updateApplicationOnBas($id, $input);
        }

        $previousStatus = $bankingAccount->getStatus();

        $this->updateInputAssigneeTeamBasedOnSubStatusChange($bankingAccount, $input);

        $channel = $bankingAccount->getChannel();

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_EDIT,
            [
                'id'      => $bankingAccount->getId(),
                'channel' => $channel,
                'input'   => $inputTrace,
            ]);

        (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        $admin = $admin !== null ? $admin : $this->core->getAdminFromHeadersForMobApp();

        // TBD: Updates from RBL LMS
        if (empty($admin) === true) {
            $admin = $this->app['basicauth']->getUser();
        }

        $account = $this->core->updateBankingAccount($bankingAccount, $input, $admin, false, false, $fromPartnerDashboard);

        $currentStatus = $bankingAccount->getStatus();

        if ($this->isNeoStoneExperiment($account->toArray()) === false)
        {
            if ($previousStatus !== $currentStatus)
            {
                $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount->toArray());
                $this->core->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount->toArray());
            }
        }

        if ($this->checkIfAccountIsArchived($previousStatus, $input) or
            $this->checkIfAccountIsTerminated($previousStatus, $input))
        {
            $this->archiveBankingAccount($bankingAccount->getId(), array(Entity::CHANNEL => Channel::RBL, Base\PublicEntity::MERCHANT_ID => $account->getMerchantId()));
        }

        // Attach Ca Application to Partner Merchant
        if ($this->checkIfSentToBank($previousStatus, $currentStatus))
        {
            try
            {
                (new BankLms\Service())->attachCaApplicationMerchantToBankPartner([Entity::BANKING_ACCOUNT_ID => $id]);
            }
            catch (BadRequestException|BadRequestValidationFailureException|Exception\LogicException $e)
            {
                $this->trace->error(TraceCode::BANKING_ACCOUNT_BANK_LMS_FAILED_TO_ATTACH_APPLICATION,
                    [
                        'id'    => $bankingAccount->getId(),
                        'error' => $e,
                    ]);
            }
        }

        return $account->toArrayPublic();
    }


    public function moveToSTBIfApplicable(Entity $bankingAccount)
    {
        $currentStatus = $bankingAccount->getStatus();

        $currentSubStatus = $bankingAccount->getSubStatus();

        if (!($currentStatus === Status::PICKED && $currentSubStatus == Status::DOCKET_INITIATED))
        {
            return;
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_DOCKET_DELIVERED_MOVE_TO_STB, [
            'banking_account_id' => $bankingAccount->getId(),
            'merchant_id' => $bankingAccount->getMerchantId(),
        ]);

        $bankingAccount = $this->update(
            $bankingAccount->getPublicId(),
            [
                Entity::STATUS      => Status::INITIATED,
                Entity::SUB_STATUS  => Status::NONE,
            ]);
    }


    /**
     * Changes input array based on sub-status change
     * > Sub-status is changing either from or to `Pending on Sales | <REASON>`
     * > Add assignee_team and comment
     *
     * Check if the sub_status is changing either from or to `Pending on Sales | <REASON>`
     * * If it is changing to something like "Pending on Sales | *", then make sure the assignee team will be `sales`
     * * Otherwise if it is already sales, then the assignee team is changed to `ops`.
     *
     * @param \RZP\Models\BankingAccount\Entity $bankingAccount
     * @param array $input
     */
    private function updateInputAssigneeTeamBasedOnSubStatusChange(Entity $bankingAccount, &$input)
    {
        if (array_key_exists(Entity::SUB_STATUS, $input) == true)
        {
            $currentSubStatus = $bankingAccount->getSubStatus();
            $newSubStatus = $input[Entity::SUB_STATUS];

            if ($newSubStatus != $currentSubStatus &&
                (str_starts_with($newSubStatus, Status::PENDING_ON_SALES_SUB_STRING) ||
                str_starts_with($currentSubStatus, Status::PENDING_ON_SALES_SUB_STRING)))
            {
                $currentSubStatusSanitized = $currentSubStatus;
                if ($currentSubStatus !== null)
                {
                    $currentSubStatusSanitized = Status::sanitizeStatus($currentSubStatus);
                }

                $newSubStatusSanitized = $newSubStatus;
                if ($newSubStatus !== null)
                {
                    $newSubStatusSanitized = Status::sanitizeStatus($currentSubStatus);
                }

                $comment = [
                    Comment\Entity::ADDED_AT => Carbon::now()->timestamp,
                    Comment\Entity::TYPE => 'internal',
                    Comment\Entity::SOURCE_TEAM_TYPE => 'internal',
                    Comment\Entity::SOURCE_TEAM => 'sales', // updated below
                    Comment\Entity::COMMENT => "Sub-status updated: <br /><b>".$currentSubStatusSanitized."</b> → <b>".$newSubStatusSanitized."</b>.<br />",
                ];

                if (str_starts_with($newSubStatus, Status::PENDING_ON_SALES_SUB_STRING))
                {
                    $input[Entity::ACTIVATION_DETAIL][Entity::ASSIGNEE_TEAM] = 'sales';

                    $comment[Comment\Entity::COMMENT] = $comment[Comment\Entity::COMMENT]."Assignee changed to Sales.";
                    $comment[Comment\Entity::SOURCE_TEAM] = 'ops';

                    $input[Entity::ACTIVATION_DETAIL][Comment\Entity::COMMENT] = $comment;

                }
                else if ($bankingAccount->bankingAccountActivationDetails->assignee_team == 'sales')
                {
                    $input[Entity::ACTIVATION_DETAIL][Entity::ASSIGNEE_TEAM] = 'ops';

                    $comment[Comment\Entity::COMMENT] = $comment[Comment\Entity::COMMENT]."Assignee changed to Ops.";
                    $comment[Comment\Entity::SOURCE_TEAM] = 'sales';

                    $input[Entity::ACTIVATION_DETAIL][Comment\Entity::COMMENT] = $comment;
                }
            }
        }
    }

    public function updateByMerchant(string $id, array $input): array
    {

        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($id);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE,
                [
                    'stage'   => 'Validate request for internal edit',
                    'id'      => $id,
                    'input'   => $input,
                ]);

            (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_FETCH_RBL_APPLICATION_FROM_BAS,
                [
                    'id'      => $id,
                ]);

            $id = $this->repo->banking_account->verifyIdAndStripSign($id);

            $bankingAccount = $this->bankingAccountService->getRblApplicationFromBasForInternalFetch($id);

            $serviceabilityResponse = $this->checkPincodeBusinessTypeAndFillMerchantAddress($input);

            if ($serviceabilityResponse['serviceability'] === false OR $serviceabilityResponse['business_type_supported'] === false)
            {
                return array_merge($bankingAccount, $serviceabilityResponse);
            }

            // Firing Events and Status Update Notifications will be handled from BAS
            $bankingAccount = $this->updateApplicationOnBas($id, $input);

            return array_merge($bankingAccount, $serviceabilityResponse);
        }

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

        $serviceabilityResponse = $this->checkPincodeBusinessTypeAndFillMerchantAddress($input);

        if ($serviceabilityResponse['serviceability'] === false OR $serviceabilityResponse['business_type_supported'] === false)
        {
            return $bankingAccount->toArrayPublic() + $serviceabilityResponse;
        }

        $activationDetailInput = $this->core->extractAndValidateActivationDetailInput($input);

        $ca_channel = Entity::Neostone;

        if (is_null($activationDetailInput) === false)
        {
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
            $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount->toArray());
            $this->core->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount->toArray());
        }

        return array_merge($account->toArrayPublic(), $serviceabilityResponse);
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

        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($id);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_FETCH_RBL_APPLICATION_FROM_BAS,
                [
                    'id'      => $id,
                ]);

            $id = $this->repo->banking_account->verifyIdAndStripSign($id);

            $bankingAccount =  $this->bankingAccountService->activateAccountForRbl($id);

            // Send push notification to merchant
            $this->core->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount);

            return $bankingAccount;
        }

        // validating if user tries to add/change credentials
        // after his account gets activated successfully

        $this->checkIfAccountAlreadyActivated($bankingAccount);

        $admin = $this->app['basicauth']->getAdmin();

        $bankingAccount = $this->core->activate($bankingAccount, $input, $admin);

        if ($this->isNeoStoneExperiment($bankingAccount->toArray()) === false)
        {
            $this->core->notifyMerchantAboutUpdatedStatus($bankingAccount->toArray());
            $this->core->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount->toArray());
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

    public function fetchMultiple(array $input = []): array
    {
        $bankingAccounts = $this->merchant->bankingAccounts;

        $bankingAccounts = (new BankingAccountService\Service())->fetchAccountDetailsFromBas($this->merchant->getMerchantId(), $bankingAccounts);

        $bankingAccounts = $bankingAccounts->load(Entity::BALANCE);

        $this->setFeeRecoveryFlagForBankingAccounts($bankingAccounts, $input);

        /** @var Entity $ba */
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
                [$shouldAppend, $subVirtualAccount] = $this->shouldAppendMasterBankingAccountDetailsInResponse($balance);

                if ($shouldAppend === true)
                {
                    $masterBankingAccount = $this->getDirectBankingAccountOfMasterMerchant($subVirtualAccount);

                    $ba->setMasterBankingAccount($masterBankingAccount);
                }

                // Only call ledger when "ledger_reverse_shadow" is enabled on the merchant.
                if($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
                {
                    $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($this->merchant->getId(), $ba->getPublicId());
                    if ((empty($ledgerResponse) === false) &&
                        (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE]) === false) &&
                        (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE]) === false))
                    {
                        $balanceAmount = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
                        $balance->setBalance($balanceAmount);
                    }

                    break;
                }
            }
        }

        $shouldFetchCardDetails = ((is_array($input[Entity::ACCOUNT_TYPE]) === true) and
                                   (in_array(Balance\AccountType::CORP_CARD, $input[Entity::ACCOUNT_TYPE], true) === true));

        foreach ($bankingAccounts as $index => &$bankingAccount)
        {
            $balance = $bankingAccount->getBalance();

            if (((is_array($input[Entity::ACCOUNT_TYPE]) === true) and
                 (in_array($bankingAccount[Entity::ACCOUNT_TYPE], $input[Entity::ACCOUNT_TYPE], true)) === false))
            {
                unset($bankingAccounts[$index]);
                continue;
            }

            // If account_type is corp_card, fetch card details from capital-cards service
            if ($bankingAccount->getAccountType() === Balance\AccountType::CORP_CARD)
            {
                if ($shouldFetchCardDetails === true)
                {
                    $response = $this->app[CapitalCardsClient::CAPITAL_CARDS_CLIENT]->getCorpCardAccountDetails(
                        ['balance_id' => $balance->getId()]);

                    // If no records are found in the capital-cards service , remove from response
                    if (empty($response) === true)
                    {
                        unset($bankingAccounts[$index]);
                    }
                    else
                    {
                        $balance[Balance\Entity::CORP_CARD_DETAILS] = $response;
                    }

                }
                else
                {
                    unset($bankingAccounts[$index]);
                }
            }
        }

        $response = $bankingAccounts->toArrayPublic();

        $response[Base\PublicCollection::ITEMS] = array_values($response[Base\PublicCollection::ITEMS]);

        return $response;
    }

    public function shouldAppendMasterBankingAccountDetailsInResponse(Balance\Entity $balance)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ASSUME_SUB_ACCOUNT) === false)
        {
            return [false, null];
        }

        /** @var SubVA\Entity $subVirtualAccount */
        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountFromSubAccountNumber($balance->getAccountNumber(), true);

        if ((empty($subVirtualAccount) === true) or
            ($subVirtualAccount->getSubAccountType() !== SubVA\Type::SUB_DIRECT_ACCOUNT))
        {
            return [false, $subVirtualAccount];
        }

        return [true, $subVirtualAccount];

    }

    public function getDirectBankingAccountOfMasterMerchant(\RZP\Models\SubVirtualAccount\Entity $subVirtualAccount)
    {
        $masterBalance  = $subVirtualAccount->balance;
        $masterMerchant = $subVirtualAccount->masterMerchant;

        $masterDirectBankingAccount = $this->repo->banking_account->findByMerchantAndAccountNumberPublic($masterMerchant, $subVirtualAccount->getMasterAccountNumber());

        if ($masterDirectBankingAccount === null)
        {
            try
            {
                $basBankingAccount = $this->app['banking_account_service']->fetchBankingAccountByAccountNumberAndChannel($masterMerchant->getId(), $masterBalance->getAccountNumber(), $masterBalance->getChannel());

                $masterDirectBankingAccount = (new BankingAccountService\Core())->generateInMemoryBankingAccount($masterMerchant->getId(), $basBankingAccount);
            }
            catch (\Throwable $exception)
            {
                (new Metrics())->pushErrorMetrics(Metrics::MASTER_DIRECT_BANKING_ACCOUNT_FETCH_FAILURES_TOTAL,
                                                  [
                                                      Entity::MERCHANT_ID => $masterMerchant->getId(),
                                                      Entity::CHANNEL     => $masterBalance->getChannel(),
                                                  ]);

                throw $exception;
            }
        }

        $isUpiAllowedForMasterBankingAccount = $this->checkIfUpiTransferIsAllowedOnMasterMerchant($masterDirectBankingAccount->getChannel(), $masterMerchant->getId());

        return [
            Entity::ID                => $masterDirectBankingAccount->getId(),
            Constants::NAME           => $masterMerchant->getDisplayNameElseName(),
            Entity::CHANNEL           => $masterDirectBankingAccount->getChannel(),
            Entity::STATUS            => $masterDirectBankingAccount->getStatus(),
            Entity::ACCOUNT_TYPE      => $masterDirectBankingAccount->getAccountType(),
            Entity::ACCOUNT_NUMBER    => mask_except_last4($masterDirectBankingAccount->getAccountNumber()),
            Constants::IS_UPI_ALLOWED => $isUpiAllowedForMasterBankingAccount,
        ];
    }

    public function checkIfUpiTransferIsAllowedOnMasterMerchant($bankingAccountChannel, $merchantId)
    {
        $payoutValidator = new \RZP\Models\Payout\Validator;

        $isUpiEnabled = false;

        try
        {
            if (($bankingAccountChannel === Channel::RBL) and
                ($payoutValidator->isUpiModeEnabledOnRblDirectAccountForMerchantId($merchantId) === true))
            {
                $isUpiEnabled =  true;
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                TraceCode::UPI_MODE_ENABLED_ON_RBL_DIRECT_ACCOUNT_CHECK_EXCEPTION,
                null,
                [
                    Entity::CHANNEL => $bankingAccountChannel,
                    Entity::MERCHANT_ID => $merchantId
                ]
            );
        }

        return $isUpiEnabled;
    }

    public function setFeeRecoveryFlagForBankingAccounts($bankingAccounts, $input)
    {
        foreach ($bankingAccounts as $ba)
        {
            if (($this->merchant->isFeatureEnabled(Feature\Constants::SKIP_EXPOSE_FEE_RECOVERY) === true) or
                ((isset($input['fee_recovery']) === true) and
                 ((boolval($input['fee_recovery']) === false))))
            {
                $ba->feeRecoverySetFlag = false;
            }
        }
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
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->getApplicationStatusLogsForRblLms($bankingAccountId);
        }

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

        $reviewerId        = $input[Entity::REVIEWER_ID];
        $bankingAccountIds = $input[Entity::BANKING_ACCOUNT_IDS] ?? [];

        // Remove the prefix from the banking account ids
        $bankingAccountIdsWithoutPrefix = array_map(function($bankingAccountId) {
            return str_replace(Entity::getIdPrefix(), '', $bankingAccountId);
        }, $bankingAccountIds);

        $bankingAccountIdsInApi = $this->repo->banking_account->getValidBankingAccountIds(Channel::RBL, AccountType::CURRENT, $bankingAccountIdsWithoutPrefix);
        $bankingAccountIdsInBas = array_values(array_diff($bankingAccountIdsWithoutPrefix, $bankingAccountIdsInApi));

        $this->trace->info(TraceCode::BANKING_ACCOUNT_BULK_ASSIGNER_REVIEWER, [
            'reviewer_id'             => $reviewerId,
            'api_banking_account_ids' => $bankingAccountIdsInApi,
            'bas_banking_account_ids' => $bankingAccountIdsInBas,
        ]);

        $apiResult = [];
        $basResult = [];

        // Call this only for those banking accounts which are present in API DB
        if (!empty($bankingAccountIdsInApi))
        {
            // Add bacc_ prefix to banking account ids
            $bankingAccountIdsInApi = array_map(function($bankingAccountId) {
                return Entity::getIdPrefix() . $bankingAccountId;
            }, $bankingAccountIdsInApi);

            $apiResult = (new Core)->bulkAssignReviewer($reviewerId, $bankingAccountIdsInApi);
        }

        // Call BAS only for those banking accounts which are not present in API DB
        if (!empty($bankingAccountIdsInBas))
        {
            $basInput = $input;

            $basInput[Entity::BANKING_ACCOUNT_IDS] = $bankingAccountIdsInBas;

            $basResult = $this->bankingAccountService->bulkAssignAccountManagerForRbl($basInput);
        }

        return $this->mergeBulkAssignResult($apiResult, $basResult);
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
            $admin = $this->repo->admin->findOrFailPublic($input[Entity::ADMIN_ID]);

            $bankingAccount = $this->core->getBankingAccountByBankReferenceAndChannel(
                $input[Entity::CHANNEL],
                $input[Entity::BANK_REFERENCE_NUMBER]);

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
            // TODO: throw different validation errors for admin find query.
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

            $newBankingAccountStatus = $updateInput[Entity::STATUS] ?? null;

            if ($bankingAccount == null)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE,
                    [
                        'stage'             => 'Batch Service Update Input',
                        'input'             => $updateInput,
                        'reference_number'  => $input[Entity::BANK_REFERENCE_NUMBER],
                        'channel'           => $input[Entity::CHANNEL],
                    ]);

                (new Validator)->setStrictFalse()->validateInput(Validator::INTERNAL_EDIT, $input);

                $this->updateApplicationOnBasByReferenceNumber($input[Entity::BANK_REFERENCE_NUMBER], $updateInput);
            }
            else
            {

                // Archiving an already activated banking_account should not be allowed by Batch
                if ($newBankingAccountStatus == Status::ARCHIVED and
                    $bankingAccount->getStatus() == Status::ACTIVATED)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_ACTIVATED,
                        null,
                        ['id' => $bankingAccount->getId()]
                    );
                }

                $this->update($bankingAccount->getPublicId(), $updateInput);
            }

        }
        catch (Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [], $e->getMessage());
        }

        return [
            'status' => 'success'
        ];
    }

    public function requestActivationMisReport(array $input)
    {
        array_pull($input, 'mis_type');

        $admin = $this->auth->getAdmin()->toArray();

        BankingAccountRblMisReport::dispatch(Mode::LIVE, $input, $admin);

        return [
            'status' => 'success',
            'message' => 'Report will be sent over email in a few mins.'
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

    public function getBankingAccountOpsMxPocs()
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $mxPocEmails = OpsMxPocEmails::$mxPocEmails;

        $admins = (new Admin\Repository())->fetchByOrgIDAndEmailIDs($orgId, $mxPocEmails)->toArray();

        return $admins;
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

    public function CheckServiceableByRBLUsingBAS(string $pincode, bool $includeIcici = false) : array
    {
        $basResponse = [];

        $isAdmin = $this->app['basicauth']->isAdminAuth();

        $response = [
            'serviceability' => false,
            'errorMessage' => null,
        ];

        try
        {
            // 1. make BAS call
            $basResponse = $this->bankingAccountService->checkServiceability($pincode)['data'] ?? [];

            // 2. validate response
            (new Validator())->validateInput('basServiceabilityResponse', $basResponse[Constants::PINCODE_DETAILS]);
        }
        catch(Exception\BadRequestValidationFailureException $e)
        {
            /*
             * Not catching Exception\ServerErrorException thrown by BAS 500 errors. Allow them to
             * percolate upwards.
             */
            $response['errorMessage'] = $e->getMessage() . ';' . $basResponse[Constants::PINCODE_DETAILS][Constants::ERROR];

            return $response;
        }

        unset($basResponse[Constants::PINCODE_DETAILS][Constants::ERROR]);

        $response = array_merge($response, $basResponse[Constants::PINCODE_DETAILS]);

        // 3. extract serviceable banks
        $serviceableBanks = $this->extractServiceableBanksFromBasServiceabilityResponse($basResponse);

        // 4. set serviceability if valid
        if (in_array(Constants::RBL, $serviceableBanks) or
            (in_array(Constants::ICICI, $serviceableBanks) and $includeIcici and $isAdmin))
        {
            $response['serviceability'] = true;
        }

        return $response;
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

        if(in_array($bankingAccount->getStatus(), [
                Status::API_ONBOARDING,
                Status::ACCOUNT_ACTIVATION,
                Status::ARCHIVED,
            ]) === false
        )
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
    protected function checkPincodeBusinessTypeAndFillMerchantAddress(array &$input): array
    {
        $serviceability = null;

        if (isset($input['pincode']) === true)
        {
            $pincode = $input[Entity::PINCODE];

            $serviceability = $this->CheckServiceableByRBLUsingBAS($pincode);
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

        $serviceability['business_type_supported'] = $businessTypeSupported;

        if($serviceability['serviceability'])
        {
            $input['activation_detail'][ActivationDetail\Entity::MERCHANT_CITY] = $serviceability[Constants::CITY];
            $input['activation_detail'][ActivationDetail\Entity::MERCHANT_STATE] = $serviceability[Constants::STATE];
            $input['activation_detail'][ActivationDetail\Entity::MERCHANT_REGION] = $serviceability[Constants::REGION];
        }

        return $serviceability;
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

        try
        {
            $bankingAccount = $this->repo->banking_account->getBankingAccountWithBalanceViaAccountNumberAndMerchantId($accountNumber, $merchantId);
        }
        catch (\Exception $ex)
        {
            if ($ex instanceof DbQueryException)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, null, 'No db records found.');
            }

            throw $ex;
        }

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
     * Get banking account using balance id
     *
     * @param string $balanceId
     * @return array
     */
    public function getBankingAccountForBalanceId(string $balanceId)
    {
        try
        {
            $this->trace->info(
                TraceCode::FETCH_BANKING_ACCOUNT_FOR_PAYOUT_SERVICE,
                [
                    Entity::BALANCE_ID => $balanceId,
                ]);

            (new Validator)->validateInput(
                Validator::FETCH_BANKING_ACCOUNT_USING_BALANCE_ID,
                [
                    Entity::BALANCE_ID => $balanceId
                ]);

            try
            {
                $bankingAccount = $this->repo->banking_account->getFromBalanceIdOrFail($balanceId);
            }
            catch (\Exception $ex)
            {
                if ($ex instanceof DbQueryException)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, null, 'No db records found.');
                }

                throw $ex;
            }

            $response = [
                Entity::ID                  => $bankingAccount->getId(),
                Entity::STATUS              => $bankingAccount->getStatus(),
                Entity::CHANNEL             => $bankingAccount->getChannel(),
                Entity::BALANCE_ID          => $bankingAccount->getBalanceId(),
                Entity::MERCHANT_ID         => $bankingAccount->getMerchantId(),
                Entity::ACCOUNT_NUMBER      => $bankingAccount->getAccountNumber(),
                Entity::ACCOUNT_TYPE        => $bankingAccount->balance->getAccountType(),
                Entity::BALANCE_TYPE        => $bankingAccount->balance->getType(),
                Entity::FTS_FUND_ACCOUNT_ID => $bankingAccount->getFtsFundAccountId()
            ];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FETCH_BANKING_ACCOUNT_FOR_PAYOUT_SERVICE_FAILED,
                [
                    'balance_id' => $balanceId,
                ]
            );

            throw $exception;
        }

        $this->trace->info(TraceCode::FETCH_BANKING_ACCOUNT_FOR_PAYOUT_SERVICE_RESPONSE,
                           [
                               'response' => $response
                           ]);

        return $response;
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

    public function getCustomerAppointmentDateOptions(string $city, array $input): array
    {
        if (empty($city))
        {
            throw new BadRequestValidationFailureException(
                'The city param is required',
                'city');
        }

        return CustomerAppointmentDate::getCustomerAppointmentDateOptions($city);
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

                $this->notifier->notify($bankingAccount->toArray(), Event::APPLICATION_RECEIVED, Event::INFO, $payload);
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

    public function isNeoStoneExperiment(array $bankingAccount): bool
    {
        $bankingAccountActivation = $bankingAccount[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];

        $this->trace->info(
            TraceCode::CHECK_NEOSTONE,
            [
                $bankingAccount[Entity::MERCHANT_ID],
                'banking_account_activation_detail' => is_null($bankingAccountActivation)
            ]);

        if (empty($bankingAccountActivation) === true)
        {
            return false;
        }

        $this->trace->info(
            TraceCode::NEOSTONE_MERCHANT_TRUE,
            [
                $bankingAccount[Entity::MERCHANT_ID],
                'contact_verified'  => $bankingAccountActivation[Activation\Detail\Entity::CONTACT_VERIFIED],
            ]);

        // For neostone we are verifying merchant contact with otp
        return ($bankingAccountActivation[Activation\Detail\Entity::CONTACT_VERIFIED] == 1);
    }

    private function checkIfPersonalDetailFilledAndFireEvent(Entity $bankingAccount, array $activationDetailInput, string $channel)
    {
        if (isset($activationDetailInput[ActivationDetail\Entity::MERCHANT_POC_NAME]) === true)
        {
            $payload = ['ca_channel' => $channel];

            $this->notifier->notify($bankingAccount->toArray(), Event::PERSONAL_DETAILS_FILLED, Event::INFO, $payload);
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
                $finalBankingAccounts = [];

                foreach ($bankingAccountStates as $bankingAccountState)
                {
                    $bankingAccount = $bankingAccountState->bankingAccount;

                    if ($bankingAccountState->getSubStatus() === $bankingAccount->getSubStatus())
                    {
                        $finalBankingAccounts[] = $bankingAccount;
                    }
                }

                $mailable = new MerchantPreparingDoc($finalBankingAccounts, $spocEmail);

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
                $finalBankingAccounts = [];

                foreach ($bankingAccountStates as $bankingAccountState)
                {
                    $bankingAccount = $bankingAccountState->bankingAccount;

                    if ($bankingAccountState->getSubStatus() === $bankingAccount->getSubStatus())
                    {
                        $finalBankingAccounts[] = $bankingAccount->toArray();
                    }
                }

                $mailable = new DiscrepancyInDoc($finalBankingAccounts, $spocEmail);

                Mail::queue($mailable);
            }
        }
    }

    /**
     *
     * @param string $bankingAccountId
     * @param array $input
     * @return array
     */

    public function archiveBankingAccount(string $bankingAccountId, array $input): array
    {
        (new Validator)->setStrictFalse()->validateInput(Validator::ARCHIVE_ACCOUNT, $input);

        $bankingAccount = $this->repo->banking_account->findOrFail($bankingAccountId);

        $balance = null;

        if ($bankingAccount->getBalanceId() !== null)
        {
            $balance = $this->repo->balance->find($bankingAccount->getBalanceId());
        }

        return $this->core->archiveBankingAccount($balance);
    }

    private function checkIfAccountIsArchived(string $previousStatus, array $input): bool
    {
        return (isset($input[Entity::STATUS])) and
               ($input[Entity::STATUS] == Status::ARCHIVED) and
               ($previousStatus != $input[Entity::STATUS]);
    }

    private function checkIfAccountIsTerminated(string $previousStatus, array $input) : bool
    {
        return (isset($input[Entity::STATUS])) and
            ($input[Entity::STATUS] == Status::TERMINATED) and
            ($previousStatus != $input[Entity::STATUS]);
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

    private function checkIfSentToBank($previousStatus, $currentStatus): bool
    {
        return ($previousStatus != $currentStatus and $currentStatus == Status::INITIATED);
    }

    private function extractServiceableBanksFromBasServiceabilityResponse(array $basResponse) : array
    {
        $serviceableBanks = [];

        $serviceability = $basResponse[Constants::SERVICEABILITY];

        for ($index = 0; $index < count($serviceability); $index++)
        {
            if($serviceability[$index][Constants::IS_SERVICEABLE])
            {
                $serviceableBanks[] = $serviceability[$index][Constants::PARTNER_BANK];
            }
        }

        return $serviceableBanks;
    }

    public function fetchMultipleRblApplicationsFromApiAndBas(array $input) : array
    {
        $originalSkip  = $input[Constants::SKIP] ?? 0;
        $originalCount = $input[Constants::COUNT] ?? 20;
        $rowsToFetch   = $originalSkip + $originalCount;

        // Fetch skip + count rows from both sources
        // We can apply skip and count after merging data from both sources
        $input[Constants::SKIP]  = 0;
        $input[Constants::COUNT] = $rowsToFetch;

        $bankingAccountsFromDb = (new AdminService())->fetchMultipleEntities('banking_account', $input);
        $bankingAccountsFromDb = $bankingAccountsFromDb['items'];

        $bankingAccountsFromBas = $this->bankingAccountService->fetchApplicationsForRblLms($input);

        $bankingAccounts = $this->mergeBankingAccountArrays($bankingAccountsFromDb, $bankingAccountsFromBas,
                                                            $originalSkip, $originalCount,
                                                            Entity::CREATED_AT, 'desc');


        // Update merchant details
        foreach ($bankingAccounts as $index => $bankingAccount)
        {
            if (isset($bankingAccount[Entity::MERCHANT]) === false)
            {
                /* @var $merchant Merchant\Entity */
                $merchant = $this->repo->merchant->findByPublicId($bankingAccount[Entity::MERCHANT_ID]);
                $merchant->load('merchantDetail');

                $bankingAccount[Entity::MERCHANT] = $merchant->toArrayAdmin();
                $bankingAccounts[$index] = $bankingAccount;
            }
        }

        return $bankingAccounts;
    }

    public function fetchRblApplicationFromApiAndBas(string $bankingAccountId): array
    {
        try
        {
            return (new AdminService())->fetchEntityById('banking_account', $bankingAccountId);
        }
        catch (\Exception $e)
        {
            if ($e->getCode() === ErrorCode::BAD_REQUEST_INVALID_ID)
            {
                $bankingAccount = $this->bankingAccountService->fetchCompositeApplicationForRbl($bankingAccountId);

                // Add merchant details
                /* @var $merchant Merchant\Entity */
                $merchant = $this->repo->merchant->findByPublicId($bankingAccount[Entity::MERCHANT_ID]);
                $merchant->load('promotions.promotion');
                $bankingAccount[Entity::MERCHANT] = $merchant->toArrayAdmin();

                return $bankingAccount;
            }
            throw $e;
        }
    }

    protected function mergeBankingAccountArrays(array  $bankingAccountsFromDb,
                                                 array  $bankingAccountsFromBas,
                                                 int    $skip,
                                                 int    $count,
                                                 string $sortBy,
                                                 string $sortDirection): array
    {
        $combinedBankingAccounts = array_merge($bankingAccountsFromDb, $bankingAccountsFromBas);

        $createdAt = array_column($combinedBankingAccounts, $sortBy);

        array_multisort($createdAt, $sortDirection === 'asc' ? SORT_ASC : SORT_DESC, $combinedBankingAccounts);

        return array_slice($combinedBankingAccounts, $skip, $count);
    }

    private function mergeBulkAssignResult(array $apiResult, array $basResult)
    {
        $apiSuccessCount = $apiResult['success'] ?? 0;
        $basSuccessCount = $basResult['success'] ?? 0;

        $apiFailureCount = $apiResult['failed'] ?? 0;
        $basFailureCount = $basResult['failed'] ?? 0;

        $apiFailedItems = $apiResult['failedItems'] ?? [];
        $basFailedItems = $basResult['failed_items'] ?? [];

        return array_merge($apiResult, [
            'success'     => $apiSuccessCount + $basSuccessCount,
            'failed'      => $apiFailureCount + $basFailureCount,
            'failedItems' => array_merge($apiFailedItems, $basFailedItems),
        ]);
    }

}
