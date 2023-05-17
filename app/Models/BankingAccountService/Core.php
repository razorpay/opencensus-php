<?php

namespace RZP\Models\BankingAccountService;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mail;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Mail\BankingAccount\CurrentAccount;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;
use RZP\Models\BankingAccountStatement;
use RZP\Models\Base;
use RZP\Models\Counter;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Attribute\Entity as MerchantAttributeEntity;
use RZP\Models\Merchant\Attribute\Group;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\XChannelDefinition;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createCaBankingDependencies(string $merchantId, array $input): array
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CREATE_BANKING_REQUEST, $input);

        list($balance, $createdNow, $basDetailEntity) = $this->repo->transaction(function () use ($merchantId, $input)
        {
            list($balance, $createdNow, $basDetailEntity) = $this->createBalanceAndBankingAccountStatementDetails($merchantId, $input);

            $merchant = $balance->merchant;

            (new BankingAccount\Core)->createScheduleTaskForFeeRecovery($balance, $merchant);

            (new Counter\Core)->fetchOrCreate($balance);

            (new BankingAccount\Core)->createRZPFeesContactAndFundAccount($merchant, $balance->getChannel());

            return [$balance, $createdNow, $basDetailEntity];
        });

        // check experiment and onboard DA to ledger in reverse shadow or shadow mode
        $merchant = $balance->merchant;

        if (($createdNow === true) and ($this->onBoardDAMerchantOnLedgerInShadow($balance->merchant, $this->app['rzp.mode']) === true)) {
            (new BankingAccount\Core())->assginLedgerFeatureForMerchant($merchant, Feature\Constants::DA_LEDGER_JOURNAL_WRITES);
            (new Merchant\Balance\Ledger\Core)->createXLedgerAccountForDirect($merchant, $basDetailEntity, $this->app['rzp.mode'], $balance->getBalance(),0,false);
        }

        return [
            'balance_id' => $balance->getId(),
        ];
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

    public function removeBusinessId(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        /* @var Detail\Entity $merchantDetail */
        $merchantDetail = $merchant->merchantDetail;

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_BUSINESS_ID_UNLINK_REQUEST, [
            'merchant_id' => $merchantId, 'business_id' => $merchantDetail->getBasBusinessId()]);

        $merchantDetail->setBasBusinessId(null);

        $this->repo->merchant_detail->saveOrFail($merchantDetail);

        return $merchantDetail->toArrayPublic();
    }

    public function assignBusinessId(string $merchantId, array $input): array
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_BUSINESS_ID_REQUEST, $input);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        /* @var Detail\Entity $merchantDetail*/
        $merchantDetail = $merchant->merchantDetail;

        $merchantDetail->setBasBusinessId($input[Constants::BUSINESS_ID]);

        $this->repo->merchant_detail->saveOrFail($merchantDetail);

        return $merchantDetail->toArrayPublic();
    }

    public function createBalanceAndBankingAccountStatementDetails($merchantId, $input)
    {
        try
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $attributes = [
                Merchant\Balance\Entity::ACCOUNT_TYPE        => Merchant\Balance\AccountType::DIRECT,
                Merchant\Balance\Entity::CHANNEL             => $input[Constants::CHANNEL],
                Merchant\Balance\Entity::ACCOUNT_NUMBER      => $input[Constants::ACCOUNT_NUMBER],
            ];

            list($balance, $createdNow) = $this->createBalance($merchant, $attributes);

            $basDetailEntity = $this->createBankingAccountStatementDetails($merchantId, $input, $balance->getId());

            $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_BALANCE_CREATE, $balance->toArrayPublic());

            (new Merchant\Activate())->addPayoutFeatureIfApplicable($merchant, Mode::LIVE, true);

            if ($createdNow === true)
            {
                (new Merchant\Activate())->addEnableIpWhitelistFeatureOnX($merchant, Mode::LIVE);
            }

            $this->trace->info(TraceCode::PAYOUT_FEATURE_ADDED, [
                Merchant\Constants::MERCHANT_ID => $merchantId
            ]);

            (new Merchant\Core())->addHasKeyAccessToMerchantIfApplicable($merchant);

            return [$balance, $createdNow, $basDetailEntity];
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_CREATING_BANKING_ACCOUNT_DEPENDENT_ENTITIES,
                [
                    'merchant_id'        => $merchantId,
                    'account_number'     => $input[Constants::ACCOUNT_NUMBER],
                    'channel'            => $input[Constants::CHANNEL],
                    'error'              => $e->getMessage()
                ]);

            throw $e;

        }
    }

    public function createBalance(Merchant\Entity $merchant, $attributes)
    {
        $balance = $this->repo->balance->getBalanceByMerchantIdAccountNumberChannelAndAccountType(
            $merchant->getId(),
            $attributes[Constants::ACCOUNT_NUMBER],
            $attributes[Constants::CHANNEL],
            AccountType::DIRECT);

        if(empty($balance) === true)
        {
            $mode = $this->app['rzp.mode'];

            $balance = (new Merchant\Balance\Core)->createBalanceForCurrentAccount($merchant, $attributes, $mode);
            return [$balance, true];
        }

        return [$balance, false];
    }

    public function createBankingAccountStatementDetails($merchantId, $input, $balanceId)
    {
        $input[Constants::BALANCE_ID] = $balanceId;

        $input[Constants::MERCHANT_ID] = $merchantId;

        return (new BankingAccountStatement\Details\Core())->createOrUpdate($input);
    }

    /**
     *  Creating bankingAccountEntity in memory only and attaches to result set.
     *
     * @param $merchantId
     * @param $basBankingAccount
     * @param $bankingAccounts
     *
     * @return
     */
    public function attachBasBankingAccount($merchantId, $basBankingAccount, $bankingAccounts)
    {
        if (empty($basBankingAccount) === true)
        {
            return $bankingAccounts;
        }

        $ba = $this->generateInMemoryBankingAccount($merchantId, $basBankingAccount);

        $bankingAccounts->add($ba);

        return $bankingAccounts;
    }

    public function attachBankingAccountWithBalance($merchantId, $basBankingAccount)
    {
        $ba = $this->generateInMemoryBankingAccount($merchantId, $basBankingAccount);

        $bankingAccountArray = $ba->toArrayPublic();

        $bankingAccountArray['banking_balance'] = optional($ba->balance)->toArrayPublic();

        return $bankingAccountArray;
    }

    public function generateInMemoryBankingAccount($merchantId, $basBankingAccount)
    {
        $ba = new BankingAccountEntity();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $channel = strtolower($basBankingAccount['partner_bank']);

        $input = [
            BankingAccountEntity::CHANNEL      => $channel,
            BankingAccountEntity::ACCOUNT_TYPE => 'current',
            BankingAccountEntity::ACCOUNT_IFSC => $basBankingAccount['ifsc'],
        ];

        if (empty($basBankingAccount['account_number']) === false)
        {
            $input[BankingAccountEntity::ACCOUNT_NUMBER] = $basBankingAccount['account_number'];
        }

        if (empty($basBankingAccount['beneficiary_name']) === false)
        {
            $input[BankingAccountEntity::BENEFICIARY_NAME] = $basBankingAccount['beneficiary_name'];
        }

        $ba->build($input);

        $status = $basBankingAccount[Constants::STATUS];

        if ($status === 'ACTIVE')
        {
            $status = 'activated';
        }

        $ba->setId($basBankingAccount['id']);

        $ba->setBasCaStatus($status);

        // Fetching balance by account number in-case there are multiple CA's
        $balance = $this->repo->balance->getBalanceByMerchantIdAccountNumberChannelAndAccountType($merchantId,
                                                                                                  $basBankingAccount['account_number'],
                                                                                                  $channel,
                                                                                                  'direct');

        $ba->merchant()->associate($merchant);

        $ba->balance()->associate($balance);

        return $ba;
    }

    /**
     * In case of CAs implemented in BAS (ICICI, Axis, Yesbank) balance exists but not banking_account entity.
     * We make a call to banking account service to fetch the banking account id.
     *
     * @param string $balanceId
     *
     */
    public function fetchBankingAccountId(string $balanceId)
    {
        $bankingAccountId = null;

        /* @var BalanceEntity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);

        //banking account does not exist for BAS CAs only.
        if ((empty($balance->bankingAccount) === true) and
            (in_array($balance->getChannel(), Channel::getDirectTypeChannels())) and
            ($balance->getAccountType() === Merchant\Balance\AccountType::DIRECT))
        {
            //call to bas to fetch the banking_account_id.
            $bankingAccountId = app('banking_account_service')->fetchBankingAccountId($balanceId);
        }
        else
        {
            $bankingAccountId = optional($balance->bankingAccount)->getPublicId();
        }

        return $bankingAccountId;
    }

    public function removeRequestParamsFromInput($requestParams, $input)
    {
        if(empty($requestParams) === true)
        {
            return $input;
        }

        return array_except($input, array_keys($requestParams));
    }

    public function attachRequestParamsToPath($queryString, $path)
    {
        if(empty($queryString) === true)
        {
            return $path;
        }

        return $path . '?' . $queryString;
    }

    public function isvalidBusinessId($path)
    {
        $businessId = null;

        $result = preg_split("/[\/]/", $path);

        if(empty($result) === false)
        {
            $businessId = $result[0];
        }
        else
        {
            $businessId = $path;
        }

        if($businessId !== $this->fetchBusinessId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BAS_INVALID_BUSINESS_ID);
        }

        return $businessId;
    }

    public function fetchBusinessId()
    {
        /* @var1 Detail\Entity $merchantDetail*/
        $merchantDetail = $this->merchant->merchantDetail;

        $businessId = $merchantDetail->getBasBusinessId();

        if(empty($businessId) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BAS_BUSINESS_ID_NOT_CREATED);
        }

        return $businessId;
    }

    public function isBusinessExists()
    {
        $businessId = $this->merchant->merchantDetail->getBasBusinessId();

        if(empty($businessId) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BAS_BUSINESS_ALREADY_CREATED);
        }
    }

    public function isDeleteBusinessRequest($path, $method)
    {
        if ($method === Request::METHOD_DELETE)
        {
            //By now business would have created.
            $businessId = $this->fetchBusinessId();

            if($path === Constants::BUSINESS_PATH . '/' . $businessId)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BAS_BUSINESS_DELETION_OPERATION_NOT_PERMITTED);
            }
        }
    }

    /*
     * FE checks for ca_activation_status field and lands on live mode if it's activated.
     */
    public function fetchBasCaStatus($merchantId)
    {
        $status = null;
        //Avoiding failure of /user api if banking account service is down.
        try
        {
            $bankingAccount = $this->app['banking_account_service']->fetchAccountDetails($merchantId);

            if (empty($bankingAccount) === false)
            {
                $status = $bankingAccount[Constants::STATUS];

                if ($status === 'ACTIVE')
                {
                    $status = 'activated';
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_FETCH_ACCOUNT_DETAILS
            );
        }

        return $status;
    }

    public function sendCaLeadToSalesForce($input, $caOnboardingFlow)
    {
        $this->trace->info(TraceCode::BAS_SALESFORCE_REQUEST);

        /* @var Merchant\Entity $merchant*/
        $merchant = $this->repo->merchant->findByPublicId($input[Constants::MERCHANT_ID]);

        /* @var Detail\Entity $merchantDetails*/
        $merchantDetails = $this->repo->merchant_detail->findByPublicId($input[Constants::MERCHANT_ID]);

        if (empty($input[Constants::CA_PREFERRED_EMAIL]) === true)
        {
            $input[Constants::CA_PREFERRED_EMAIL] = $merchantDetails->getContactEmail();
        }

        if (empty($input[Constants::CA_PREFERRED_PHONE]) === true)
        {
            $input[Constants::CA_PREFERRED_PHONE] = $merchantDetails->getContactMobile();
        }

        $merchantAttribute = $this->repo->merchant_attribute->getKeyValues($input[MerchantConstants::MERCHANT_ID], ProductType::BANKING, Group::X_MERCHANT_PREFERENCES, [Merchant\Attribute\Type::X_SIGNUP_PLATFORM])->first();

        $input[Constants::SOURCE_DETAIL] = $merchantAttribute[MerchantAttributeEntity::VALUE] ?? Constants::X_DASHBOARD;

        if ($caOnboardingFlow != null)
        {
            $input[Constants::CA_ONBOARDING_FLOW] = $caOnboardingFlow;
        }

        // Add channel details
        $xChannelDefinitionService = new XChannelDefinition\Service();
        $xChannelDefinitionService->addChannelDetailsInSFPayloadIfNotPresent($merchant, $input);

        $this->app->salesforce->sendCaLeadDetails($input);

        return ['success' => true];
    }

    public function sendCaLeadStatusToSalesForce($input)
    {
        $this->trace->info(TraceCode::BAS_SALESFORCE_ICICI_LEAD_STATUS_UPDATE_REQUEST);

        $this->app->salesforce->sendLeadStatusUpdate($input, Constants::ICICI);

        return ['success' => true];
    }

    public function sendCaLeadToFreshDesk($input)
    {
        $this->trace->info(TraceCode::BAS_FRESHDESK_REQUEST);

        /* @var Detail\Entity $merchantDetails*/
        $merchantDetails = $this->repo->merchant_detail->findByPublicId($input[Constants::MERCHANT_ID]);

        if(empty($input[Constants::CA_PREFERRED_EMAIL]) === true)
        {
            $input[Constants::CA_PREFERRED_EMAIL] = $merchantDetails->getContactEmail();
        }

        if(empty($input[Constants::CA_PREFERRED_PHONE]) === true)
        {
            $input[Constants::CA_PREFERRED_PHONE] = $merchantDetails->getContactMobile();
        }

        if (array_key_exists(Constants::ACCOUNT_MANAGER_NAME, $input) === false) {
            $input[Constants::ACCOUNT_MANAGER_NAME] = "";
        }

        if (array_key_exists(Constants::ACCOUNT_MANAGER_EMAIL, $input) === false) {
            $input[Constants::ACCOUNT_MANAGER_EMAIL] = "";
        }

        if (array_key_exists(Constants::ACCOUNT_MANAGER_PHONE, $input) === false) {
            $input[Constants::ACCOUNT_MANAGER_PHONE] = "";
        }

        $this->notifyOpsAboutLead($input);

        return ['success' => true];
    }


    /**
     * This email is sent to ops to notify them about the interest merchant has shown in
     * ICICI Current Account
     *
     */
    public function notifyOpsAboutLead($input)
    {
        /* @var Merchant\Entity $merchant*/
        $merchant = $this->repo->merchant->find($input[Constants::MERCHANT_ID]);

        try
        {
            $mailer = new CurrentAccount($input);

            Mail::queue($mailer);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_CA_ACTIVATION_NOTIFICATION,
                [
                    'merchant_id'              => $input[Constants::MERCHANT_ID],
                    'message'                  => 'Mail Sent'
                ]);

            $this->app['diag']->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_FRESHDESK_TICKET_CREATE_ICICI, $merchant, null, $input);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_CA_ACTIVATION_NOTIFICATION_FAILED,
                [
                    'error'              => $e->getMessage(),
                ]);
        }
    }


    public function sendRblApplicationInProgressLeadsToSalesForce(): array
    {
        $config = app('config')->get('applications.banking_account_service');

        if(empty($config) === false and empty($config['rbl_leads_sf_time_filter']) === false)
        {
            $timestamp = (int) $config['rbl_leads_sf_time_filter'];
        }
        else
        {
            //setting the value to 18th Sep 2021
            $timestamp = 1631903400;
        }

        $salesTeam = BankingAccount\Activation\Detail\Validator::SELF_SERVE;

        $last5hrs = Carbon::now()->subHours(5)->getTimestamp();

        $bankingAccountActivationDetail = (new BankingAccount\Activation\Detail\Repository())->fetchRblApplicationsBySalesTeamCreatedAtNotSubmitted($salesTeam, $timestamp, $last5hrs);

        foreach ($bankingAccountActivationDetail as $detail)
        {
            $bankingAccountId = $detail->getBankingAccountId();

            /* @var BankingAccountEntity $bankingAccount*/
            $bankingAccount = $this->repo->banking_account->findOrFail($bankingAccountId);

            $merchantId = $bankingAccount->getMerchantId();

            $merchantAttributes = $this->repo->merchant_attribute->getKeyValuesForAllProduct($merchantId, Group::X_MERCHANT_CURRENT_ACCOUNTS);

            $merchantChannel = null;

            $merchantCaCampaignId = null;

            foreach ($merchantAttributes as $merchantAttribute)
            {
                if ($merchantAttribute->type == Merchant\Attribute\Type::CA_ONBOARDING_FLOW) {
                    $merchantChannel = $merchantAttribute->value;
                }

                if ($merchantAttribute->type == Merchant\Attribute\Type::CA_CAMPAIGN_ID) {
                    $merchantCaCampaignId = $merchantAttribute->value;
                }
            }

            //check is required since declaration_step property added recently. Relying on it will lead to sending of applications irrespective of the state.
            if($bankingAccount->getStatus() === BankingAccount\Status::CREATED)
            {
                if ($merchantChannel !== Constants::CA_CHANNEL_NITRO)
                {
                    $input = [
                        Constants::CA_PARTNER_BANK    => Constants::RBL,
                        Constants::CA_PREFERRED_EMAIL => $detail->getMerchantPocEmail(),
                        Constants::CA_PREFERRED_PHONE => $detail->getMerchantPocPhoneNumber(),
                        Constants::SOURCE             => Constants::X_CA_UNIFIED,
                        Constants::MERCHANT_ID        => $bankingAccount->getMerchantId(),
                        Constants::PRODUCT_NAME       => Constants::CURRENT_ACCOUNT,
                    ];

                    //details contain senstive details so id is logged
                    $this->trace->info(TraceCode::BAS_SALESFORCE_RBL_DETAIL, [
                        'banking_account_activation_detail_id' => $detail->getId(),
                    ]);

                    $this->sendCaLeadToSalesForce($input, $merchantChannel);

                    //front end converts SME to X-SME at the admin dashboard.
                    $detail->setSalesTeam(BankingAccount\Activation\Detail\Validator::SME);

                    $this->repo->banking_account_detail->saveOrFail($detail);
                }
                elseif ($merchantCaCampaignId !== null)
                {
                    $input = [
                        Constants::SOURCE                => Constants::X_CA_UNIFIED_NITRO,
                        Constants::MERCHANT_ID           => $merchantId,
                        Constants::CAMPAIGN_ID           => $merchantCaCampaignId,
                        Constants::PRODUCT_NAME          => Constants::CURRENT_ACCOUNT,
                        Constants::CA_PARTNER_BANK       => Constants::RBL,
                        Constants::CA_PREFERRED_EMAIL    => $detail->getMerchantPocEmail(),
                        Constants::CA_PREFERRED_PHONE    => $detail->getMerchantPocPhoneNumber(),
                        Constants::X_ONBOARDING_CATEGORY => 'normal'
                    ];

                    $this->trace->info(TraceCode::RBL_NITRO_SALESFORCE_PUSH, [
                        'banking_account_id' => $bankingAccountId,
                    ]);

                    $this->sendCaLeadToSalesForce($input, $merchantChannel);

                    //front end converts SME to X-SME at the admin dashboard.
                    $detail->setSalesTeam(BankingAccount\Activation\Detail\Validator::SME);

                    $this->repo->banking_account_detail->saveOrFail($detail);
                }
            }
        }

        return ['success' => true];
    }
}
