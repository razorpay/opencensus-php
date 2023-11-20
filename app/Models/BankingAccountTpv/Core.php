<?php

namespace RZP\Models\BankingAccountTpv;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\BankingAccount\Gateway;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\BankingAccount\Gateway\Yesbank;
use RZP\Services\BankingAccountService as BasService;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;

class Core extends Base\Core
{
    protected $mutex;

    protected $validator;

    const BANKING_ACCOUNT_TPV_CREATE = 'banking_account_tpv_create_';

    const RZP_MERCHANT_ID            = '100000Razorpay';

    const LIMIT_TO_SOURCE_ACCOUNTS   = 40;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->validator = new Validator();
    }

    public function create(array $input, string $message = TraceCode::ADMIN_CREATE_TPV)
    {
        $tpv = $this->buildTpv($input, $message);

        $this->repo->saveOrFail($tpv);

        return $tpv->toArrayPublic();
    }

    public function buildTpv(array $input, string $message = TraceCode::ADMIN_CREATE_TPV)
    {
        $this->trace->info($message, $input);

        $this->duplicateTpvCheckAndPullFavId($input);

        $tpv = (new Entity)->build($input);

        $this->validator->validateMerchantBalanceId($tpv->getMerchantId(), $tpv->getBalanceId());

        if ($input[Entity::STATUS] === Status::APPROVED)
        {
            $this->sourceAccountAdditionForRxWallet($tpv);

            $tpv->setIsActive(true);
        }

        $tpv->trimPayerAccountNumber();

        return $tpv;
    }

    public function edit(string $id, array $input)
    {
        $tpvId = Entity::verifyIdAndSilentlyStripSign($id);

        /** @var Entity $tpv */
        $tpv = $this->repo->banking_account_tpv->findOrFail($tpvId);

        $this->trace->info(TraceCode::ADMIN_EDIT_TPV, $input);

        $tpv->edit($input, 'admin_edit');

        $this->validator->validateMerchantBalanceId($tpv->getMerchantId(), $tpv->getBalanceId());

        // We don't update the payer bank account number in this api but technically we can do that via this route so
        // updating the trimmed payer account number here as well.
        $tpv->trimPayerAccountNumber();

        if (isset($input[Entity::STATUS]) === true)
        {
            if ($input[Entity::STATUS] === Status::APPROVED)
            {
                $this->sourceAccountAdditionForRxWallet($tpv);

                $tpv->setIsActive(true);
            }
            else
            {
                $tpv->setIsActive(false);
            }
        }

        $this->repo->saveOrFail($tpv);

        return $tpv->toArrayPublic();
    }

    public function fetchMerchantTpvs()
    {
        $tpvs = $this->repo->banking_account_tpv->fetchMerchantTpvs($this->merchant->getId());

        $this->trace->info(TraceCode::MERCHANT_FETCH_TPV, $tpvs->toArrayPublic());

        return $tpvs->toArrayPublic();
    }

    public function getMerchantTpvsWithFavDetails($input, $merchantId)
    {
        $this->repo->merchant->findOrFail($merchantId);

        $tpvs = $this->repo->banking_account_tpv->fetch($input, $merchantId);

        $result = $tpvs->toArrayPublic();

        $this->trace->info(TraceCode::MERCHANT_TPV_FAV_INFO, $result);

        return $result;
    }

    public function duplicateTpvCheckAndPullFavId(array &$input)
    {
        if (isset($input[Entity::FUND_ACCOUNT_VALIDATION_ID]) === true)
        {
            $input[Entity::FUND_ACCOUNT_VALIDATION_ID] = FundAccountValidation::verifyIdAndSilentlyStripSign($input[Entity::FUND_ACCOUNT_VALIDATION_ID]);

            $this->repo->fund_account_validation->findOrFail($input[Entity::FUND_ACCOUNT_VALIDATION_ID]);
        }

        $tpvRecord = $this->repo->banking_account_tpv->fetchTpvOnMerchantBalanceAccountNumberIfsc($input);

        if (empty($tpvRecord) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_TPV);
        }

    }

    public function createAutoApprovedTpvForActivatedMerchants(Merchant $merchant, string $mode)
    {
        try
        {
            $this->autoApproveTpvRequest($merchant, $mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::AUTO_APPROVED_TPV_CREATION_ERROR,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    public function autoApproveTpvRequest(Merchant $merchant, string $mode = Mode::LIVE)
    {
        $bankAccount = (new BankAccount\Repository())->getBankAccountOnConnection($merchant, $mode);

        if ($bankAccount !== null)
        {
            $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
                $merchant->getId(),
                Type::BANKING,
                AccountType::SHARED,
                $mode);

            $tpvInput = [
                Entity::MERCHANT_ID             => $merchant->getMerchantId(),
                Entity::BALANCE_ID              => $balance->getId(),
                Entity::STATUS                  => Status::APPROVED,
                Entity::PAYER_NAME              => $bankAccount->getBeneficiaryName(),
                Entity::PAYER_ACCOUNT_NUMBER    => $bankAccount->getAccountNumber(),
                Entity::PAYER_IFSC              => $bankAccount->getIfscCode(),
            ];

            $tpv = $this->repo->banking_account_tpv->fetchTpvOnMerchantBalanceAccountNumberIfsc($tpvInput);

            if (empty($tpv) === true)
            {
                $tpv = $this->create($tpvInput);

                $this->trace->info(TraceCode::AUTO_APPROVED_TPV_FOR_ACTIVATED_MERCHANT,
                    [
                        'tpv'           => $tpv,
                        'merchant_id'   => $merchant->getMerchantId(),
                    ]);
            }

            return $tpv;
        }
    }

    public function manualAutoApproveTpv(array $merchantIds)
    {
        $successCount = 0;
        $failedCount  = 0;
        $totalCount   = 0;

        $tpvs                     = [];
        $tpvSuccessFulMerchantIds = [];
        $tpvFailedMerchantIds     = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $totalCount++;

                $this->trace->info(
                    TraceCode::AUTO_APPROVE_TPV_MERCHANT_REQUEST,
                    [
                        'merchant_id' => $merchantId,
                    ]);

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $mutexKey = self::BANKING_ACCOUNT_TPV_CREATE . $merchant->getId();

                $tpv = $this->mutex->acquireAndRelease(
                    $mutexKey,
                    function() use ($merchant)
                    {
                        return $this->autoApproveTpvRequest($merchant);
                    },
                    60,
                    ErrorCode::BAD_REQUEST_TPV_CREATE_OPERATION_IN_PROGRESS);

                array_push($tpvs, $tpv['id']);

                array_push($tpvSuccessFulMerchantIds, $merchantId);

                $successCount++;

            }
            catch(\Throwable $e)
            {
                $failedCount++;

                array_push($tpvFailedMerchantIds, $merchantId);

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::AUTO_APPROVE_TPV_MERCHANT_FAILURE,
                    [
                        'merchant_id' => $merchantId,
                    ]);
            }
        }

        $this->trace->info(TraceCode::AUTO_APPROVED_TPV_MERCHANTS_BULK, [
            'data_fix_successful_' . Entity::MERCHANT_IDS => $tpvSuccessFulMerchantIds,
            'data_fix_failed_' . Entity::MERCHANT_IDS     => $tpvFailedMerchantIds,
            'tpv_ids'                                     => $tpvs,
        ]);

        return [
            'total_count'   => $totalCount,
            'failed_count'  => $failedCount,
            'success_count' => $successCount,
        ];
    }

    public function createTpvFromXDashboard(array $input)
    {
        //populate fields required for creating a tpv and status will be pending since ops has to validate.
        $input[Entity::MERCHANT_ID] = $this->merchant->getId();
        $input[Entity::STATUS]      = Status::PENDING;
        $input[Entity::CREATED_BY]  = $this->merchant->getName();

        $countOfSourceAccounts = count($this->repo->banking_account_tpv->fetchMerchantTpvs($this->merchant->getId()));

        // check if source accounts limit for given merchant is already crossed
        if($countOfSourceAccounts === self::LIMIT_TO_SOURCE_ACCOUNTS)
        {
            return [];
        }

        // check if merchant is live disabled or not
        if($this->merchant->isLive() === false)

        {
            return [];
        }

        //re using create tpv function
        $tpv =  $this->buildTpv($input, TraceCode::CREATE_TPV_X_DASHBOARD);

        //create fav request
        $fav = $this->initiatePennyTesting($input);

        if($fav !== null)
        {
            $tpv->setFundAccountValidationId($fav->getId());
        }

        $this->repo->saveOrFail($tpv);

        return $tpv->toArrayPublic();
    }

    public function initiatePennyTesting(array $input)
    {
        try
        {
            $favInput = $this->generateFavInput($input);

            //using rzp merchant instead of actual merchant to avoid fund account validation charges
            $rzpMerchant = $this->repo->merchant->findOrFail(self::RZP_MERCHANT_ID);

            return (new FundAccount\Validation\Core())->create($favInput, $rzpMerchant);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::TPV_FUND_ACCOUNT_VALIDATION_FAILURE,
                [
                    'merchant_id' => $this->merchant->getId(),
                ]);
        }
    }

    public function generateFavInput(array $input)
    {
        return [
            FundAccountValidation::FUND_ACCOUNT => [
                FundAccountEntity::ACCOUNT_TYPE => 'bank_account',
                FundAccountEntity::DETAILS      => [
                    BankAccountEntity::ACCOUNT_NUMBER => $input[Entity::PAYER_ACCOUNT_NUMBER],
                    BankAccountEntity::NAME           => $input[Entity::PAYER_NAME],
                    BankAccountEntity::IFSC           => $input[Entity::PAYER_IFSC],
                ],
            ],
            FundAccountValidation::AMOUNT       => '100',
            FundAccountValidation::CURRENCY     => 'INR',
            FundAccountValidation::NOTES        => []
        ];
    }

    public function getMozartRequestDataForSourceAccountAddition($merchantCredentials, $sourceAccountDetails)
    {
        return [
            Constants::REQUEST_ID            => $this->app['request']->getId(),
            Constants::CLIENT_IDENTIFIER     => $merchantCredentials[Gateway\Fields::CREDENTIALS][Yesbank\Fields::CLIENT_ID],
            Constants::SOURCE_ACCOUNT_NUMBER => $sourceAccountDetails[Entity::PAYER_ACCOUNT_NUMBER],
            Constants::SOURCE_ACCOUNT_IFSC   => $sourceAccountDetails[Entity::PAYER_IFSC],
        ];
    }

    public function validateSourceAccountAdditionResponse($bankResponse)
    {
        $isSourceAccountAdditionSuccessful = (isset($bankResponse[Constants::DATA][Constants::STATUS]) and
                                              ($bankResponse[Constants::DATA][Constants::STATUS] === Constants::SUCCESS));

        if ($isSourceAccountAdditionSuccessful === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Source Account Addition failure. Invalid Response.', null, $bankResponse
            );
        }
    }

    /**
     * @return void
     *
     * @var $bankingAccountTpv Entity
     * Before marking the banking account Tpv as active, we first add the source accounts to bank.
     */
    public function sourceAccountAdditionForRxWallet(Entity $bankingAccountTpv)
    {
        $balance = $bankingAccountTpv->balance;

        if ($balance->getAccountType() !== AccountType::RX_WALLET)
        {
            return;
        }

        $sourceAccountDetails = [
            Entity::PAYER_ACCOUNT_NUMBER => $bankingAccountTpv->getPayerAccountNumber(),
            Entity::PAYER_IFSC           => $bankingAccountTpv->getPayerIfsc(),
        ];

        $merchantId    = $bankingAccountTpv->getMerchantId();
        $accountNumber = $sourceAccountDetails[Entity::PAYER_ACCOUNT_NUMBER];

        try
        {
            /** @var BasService $bankingAccountService */
            $bankingAccountService = $this->app['banking_account_service'];

            // Get Client ID from BAS
            $merchantCredentials = $bankingAccountService->fetchBankingCredentials(
                $merchantId, $balance->getChannel(), $accountNumber);

            $requestData = $this->getMozartRequestDataForSourceAccountAddition($merchantCredentials, $sourceAccountDetails);

            // Make Mozart Call to initiate Source Account Addition
            $bankResponse = $this->app->mozart->sendMozartRequest(Constants::SOURCE_ACCOUNT_ADDITION_MOZART_NAMESPACE,
                                                                  $balance->getChannel(),
                                                                  Constants::SOURCE_ACCOUNT_ADDITION_MOZART_ACTION,
                                                                  $requestData);

            $this->validateSourceAccountAdditionResponse($bankResponse);
        }
        catch (\Throwable $exception)
        {
            $exceptionTraceData = [
                Entity::MERCHANT_ID               => $merchantId,
                Entity::BALANCE_ID                => $balance->getId(),
                Constants::SOURCE_ACCOUNT_DETAILS => $sourceAccountDetails,
                BalanceEntity::CHANNEL            => $balance->getChannel(),
            ];

            if ($exception instanceof Exception\GatewayErrorException)
            {
                $errorCodeAndDesc = $exception->getGatewayErrorCodeAndDesc();

                $exceptionTraceData += [
                    'gateway_exception_details' => $errorCodeAndDesc
                ];

                $errorCode = $errorCodeAndDesc[0] ?? '';
                $nonActionableErrorCodes = Constants::NON_ACTIONABLE_ERROR_CODES[$balance->getChannel()] ?? [];

                if (in_array($errorCode, $nonActionableErrorCodes, true) === true)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::INFO,
                        TraceCode::SOURCE_ACCOUNT_ADDITION_NON_ACTIONABLE_FAILURE,
                        $exceptionTraceData);

                    return;
                }
            }

            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::SOURCE_ACCOUNT_ADDITION_FAILURE,
                $exceptionTraceData);

            $this->trace->count(Metric::SOURCE_ACCOUNT_ADDITION_FAILURES_COUNT);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SOURCE_ACCOUNT_ADDITION_FAILURE, null, $exceptionTraceData
            );
        }
    }
}
