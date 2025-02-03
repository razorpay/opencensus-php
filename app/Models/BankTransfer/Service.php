<?php

namespace RZP\Models\BankTransfer;

use App;
use Cache;
use Carbon\Carbon;
use Database\DefaultConnection;
use phpseclib\Crypt\AES;
use RZP\Base\JitValidator;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Environment;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Encryption\AESEncryption;
use RZP\Exception\BadRequestException;
use RZP\Jobs\ProcessCollectxTransfer;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\Tracer;
use RZP\Models\Merchant\RazorxTreatment;
use Symfony\Component\HttpFoundation\File\File;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Address;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Payment;
use RZP\Models\QrPayment;
use RZP\Models\BankAccount;
use RZP\Base\RuntimeManager;
use RZP\Models\UpiTransfer;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\BankTransferHistory;
use RZP\Models\BankTransferRequest;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Metric;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\VirtualAccount\Provider;
use RZP\Reconciliator\RequestProcessor;
use RZP\Jobs\BankTransferCreateProcess;
use RZP\Models\Payment\Processor\Notify;
use function GuzzleHttp\default_ca_bundle;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\InternationalIntegration;
use RZP\Models\Pricing\Service as PricingService;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\BankTransfer\Mode as BankTransferModes;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Metric as BankTransferMetrics;
use RZP\Models\Workflow\Service\Builder as WorkflowBuilder;
use RZP\Models\Merchant\Detail\Core as MerchantDetailsCore;
use RZP\Models\Ledger\ReverseShadow\Payments as CLSPayments;
use RZP\Models\VirtualAccount\Entity as VirtualAccountEntity;
use RZP\Models\VirtualAccount\Processor as VirtualAccountProcessor;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;
use RZP\Models\BankTransfer\Processor as BankTransferProcessor;
use RZP\Models\Merchant\Detail\Constants as MerchantDetailsConstants;

class Service extends Base\Service
{
    protected $validator;
    protected $provider;
    protected $ip;
    protected $mutex;
    protected $core;
    protected $smartCollectService;

    // Seconds in 15 minutes
    const FIFTEEN_MINUTES = 900;

    //notification type
    const CASH_MANAGER_TRANSACTION_NOTIFICATION = 'cash_manager_transaction_notification';
    const PAYMENT_RELEASED_NOTIFICATION = 'payment_released_notification';
    const TRANSFER_COMPLETED_NOTIFICATION = 'transfer_completed_notification';
    const REGULAR = 'regular';
    const PRIORITY = 'priority';

    const COLLECTX_VALIDATION_YESBANK_CALLBACK_IDENTIFIER = 'validate';
    const COLLECTX_NOTIFICATION_YESBANK_CALLBACK_IDENTIFIER = 'notify';

    const VALIDATION_CALLBACK = "validation";
    const NOTIFICATION_CALLBACK = "notification";

    const COLLECTX_YB_PAYLOAD_TRANSFER_TYPE = "transfer_type";

    const TRANSFER_TYPE_UPI = "UPI";
    const TRANSFER_TYPE_NEFT = "NEFT";
    const TRANSFER_TYPE_RTGS = "RTGS";
    const TRANSFER_TYPE_IMPS = "IMPS";
    const TRANSFER_TYPE_FT = "FT";
    const TRANSFER_TYPE_IFT = "IFT";
    const TRANSFER_TYPE_TRANSFER = "TRANSFER";
    const COLLECTX_DEFAULT_FEE_CREDITS_THRESHOLD = 50000;
    const STATUS = "status";

    const REASON = "reason";

    /**
     * Service constructor. Sets provider from app auth, and
     * sets request IP for use in validation of providers.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->provider = $this->getProvider();

        $this->ip = $this->app['request']->ip();

        $this->mutex = $this->app['api.mutex'];

        $this->smartCollectService = $this->app['smartCollect'];

        $this->settlementService = new Settlement\Service();
    }

    public function processPendingBankTransfer(array $input)
    {
        (new Validator)->validateInput('pending_bank_transfer', $input);

        $bankTransferRequestId = $input[BankTransferRequest\Entity::BANK_TRANSFER_REQUEST_ID];

        try {
            /** @var BankTransferRequest\Entity $bankTransferRequest */
            $bankTransferRequest = $this->repo->bank_transfer_request->findOrFailPublic($bankTransferRequestId);

            // $input as first parameter is not required. $bankTransferRequest is sufficient to process the request
            // This is because: Following call has been deprecated $this->process($input, $provider, $checkForIfsc);
            // in validateAndProcessRequest
            return $this->validateAndProcessRequest([], $bankTransferRequest, Provider::ICICI, true, true);
        } catch (\Exception $ex) {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESS_REQUEST_NOT_FOUND,
                [
                    'message' => 'Bank Transfer Request not found',
                    BankTransferRequest\Entity::BANK_TRANSFER_REQUEST_ID => $bankTransferRequestId,
                ]
            );

            throw $ex;
        }
    }

    public function processBankTransferInScService(array  $input,
                                                   string $provider = null,
                                                          $requestPayload = null)
    {
        $response = $this->smartCollectService->processBankTransfer(['data' => $input,
            'gateway' => $provider,
            'request_payload' => $requestPayload]);
        if ((isset($response['status_code']) === true) and
            ($response['status_code'] === 200)) {
            return $response['body'];
        }

        return $this->saveRequestAndProcess($input, $provider, false, $requestPayload);
    }

    public function saveRequestAndProcessInternal(array $input, string $routeName = null)
    {
        $data = $input['data'];
        $provider = $input['gateway'];
        $requestPayload = $input['request_payload'];

        return $this->saveRequestAndProcess($data, $provider, false, $requestPayload, $routeName);
    }

    public function saveRequestAndProcess(
        array  $input,
        string $provider = null,
        bool   $checkForIfsc = false,
               $requestPayload = null,
        string $routeName = null
    )
    {
        //For validation callbacks, request type is set as validation in input
        //Validations are performed in processValidationRequest() and success
        //or failure response is returned from here.
        //If it is a notification request (request type = "notification") these validations
        //are skipped.
        //$response is returned empty if it is not a validation request

        if ($provider !== null && $this->isCollectXCallback($input, $provider) === true)
        {
            $this->trace->info(TraceCode::COLLECTX_PAYMENT_TRANSFER_REQUEST, [
                "input"     => $input,
                "provider"  => $provider
            ]);

            // Flow to worker flow if experiment is enabled for the merchant, else usual flow
            $checkForWorkerFlow = $this->isExperimentEnabledForCollectXWorkerFlow($input, $provider, $requestPayload);

            if ($checkForWorkerFlow['enabled'] === true)
            {
                return $this->handleCollectXCallbackWorkerFlow($input, $provider, $requestPayload, $checkForWorkerFlow['merchant_id']);
            }

            return $this->handleCollectXCallback($input, $provider, $requestPayload);

        }

        if ($provider === Provider:: RBL)
        {
            $this->trace->error(
                TraceCode::RBL_PROVIDER_UNEXPEXTED_PAYMENT_ERROR, [
                    'Request' => $input
                ]);

            throw new Exception\BadRequestValidationFailureException(TraceCode::RBL_PROVIDER_UNEXPEXTED_PAYMENT_ERROR);
        }

        if ($input['gateway'] === Gateway::YESBANK || strpos($input['input']['payee_ifsc'], "YESB") === 0 )
        {
            $this->trace->error(
                TraceCode::YESBANK_GATEWAY_UNEXPECTED_PAYMENT_ERROR, [
                    'Request' => $input
                ]);

            throw new Exception\BadRequestValidationFailureException(TraceCode::YESBANK_GATEWAY_UNEXPECTED_PAYMENT_ERROR);
        }

        // Do Early Return if Virtual Account is not present
        // Indusind Will handle Refund themselves
        if ($provider === Provider::INDUSIND)
        {
            $valid  = $this->checkIfVirtualAccountIsPresent($input);

            $this->trace->error(TraceCode::VIRTUAL_ACCOUNT_UNAVAILABLE, [
                'input' => $input
            ]);

            if ($valid === false) {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE,
                    $input
                );
            }

        }


        $response = $this->processValidationRequest($input, $provider);

        if (empty($response) === false) {

            if ($response['valid'] === false) {
                throw new Exception\BadRequestValidationFailureException();
            }

            return $response;
        }

        //metric for difference in time b/w( bank transfer transaction time , bank transfer webhook callback)
        if((array_key_exists('Req_dt_time' , $input)) && (empty($input['Req_dt_time']) === false))
        {
            try
            {
                $diffInMillSec = get_diff_in_millisecond(strtoepoch($input['Req_dt_time'],'d-m-Y H:i:s'));

                $this->trace->histogram(BankTransferMetrics::BANKTRANSFER_WEBHOOK_DELAY, $diffInMillSec);
            }

            catch(\Throwable $ex)
            {

                $this->trace->error(TraceCode::BANK_TRANSFER_WEBHOOK_DELAY_METRIC_PUSH_FAILURE,[
                    $ex->getMessage()
                ]);
            }
        }


        $response = $this->validateDuplicateRequest($input, $routeName);

        if (empty($response) === false) {
            return $response;
        }

        //metric for counting number of incoming notification callbacks from bank
        $this->trace->count(BankTransferMetrics::BANKTRANSFER_CALLBACK_COUNT);

        $bankTransferRequest = null;

        try {
            if ($checkForIfsc === true) {
                $this->checkAndReplaceForIfsc($input, $provider ?? $this->provider);
            }

            $this->removeInvalidRegexFromPayerAccount($input);

            $this->extractPayerNameAndAccountFromPayerName($input);

            $this->modifyInvalidInputForPJSB($input);

            $bankAccount = $this->getQrBankAccount($input);

            if ($bankAccount !== null) {
                return (new QrPayment\Service())->processBankTransfer($input, $provider ?? $this->provider,
                    $requestPayload, $bankAccount);
            }

            $bankTransferRequest = (new BankTransferRequest\Core())->create(
                $input,
                $provider ?? $this->provider,
                $requestPayload ?? $input, [], $routeName
            );

        } catch (\Exception $ex) {

            $this->trace->traceException($ex,
                Trace::ERROR,
                TraceCode::BANK_TRANSFER_SAVE_REQUEST_FAILED,
                [
                    'transaction_id' => $input[Entity::REQ_UTR]
                ]);

        }

        return $this->validateAndProcessRequest($input, $bankTransferRequest, $provider, $checkForIfsc);
    }

    protected function checkIfVirtualAccountIsPresent(array $input): bool
    {
        $accountNumber = $input["payee_account"];
        $ifsc = $input["payee_ifsc"];

        $virtualAccount = $this->getVirtualAccountUsingAccountNumberAndIfsc($accountNumber, $ifsc);

        if ($virtualAccount === null)
        {
            return false;
        }

        return true;
    }

    protected function checkForCollectXValidateRequestForUPI(array $input): bool
    {
        if ((array_key_exists('validate', $input)) and $input['validate']['transfer_type'] === "UPI")
        {
            return true;
        }
        return false;
    }

    protected function checkForCollectXNotifyRequestForUPI(array $input): bool
    {
        if ((array_key_exists('notify', $input)) and $input['notify']['transfer_type'] === "UPI")
        {
            return true;
        }
        return false;
    }

    // As of now, we have added checks of only bank transfer modes since UPI is not yet supported for both yesbank and axis
    protected function isCollectXCallback(array $input, string $provider): bool
    {
        try {
            switch ($provider) {
                case Provider::YESBANK:
                    return $this->checkForYesBankCollectxCallback($input);

                case Provider::AXIS:
                    return $this->checkForAxisBankCollectxCallback($input);

                case Provider::RBL:
                    return $this->checkforRblBankCollectxCallback($input);

                default:
                    $this->trace->info(
                        TraceCode::INVALID_PROVIDER_COLLECTX_BANK_TRANSFER, [
                            'provider' => $provider
                        ]
                    );

                    return false;
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex,
                Trace::ERROR,
                TraceCode::COLLECTX_PAYMENT_TRANSFER_CHECK_FAILED, [
                    'input' => $input
                ]);

            return false;
        }
    }

    public function checkForYesBankCollectxCallback(array $input): bool
    {
        return array_key_exists(self::COLLECTX_VALIDATION_YESBANK_CALLBACK_IDENTIFIER, $input) ||
            array_key_exists(self::COLLECTX_NOTIFICATION_YESBANK_CALLBACK_IDENTIFIER, $input);
    }

    protected function checkForRblBankCollectxCallback(array $input): bool
    {
        $accountNumber = $input['payee_account'];

        $ifsc = $input['payee_ifsc'];

        // Check1: Check if bank account and VA exists for the given account number and IFSC
        /* @var VirtualAccountEntity $virtualAccount*/
        $virtualAccount = $this->getVirtualAccountUsingAccountNumberAndIfsc($accountNumber, $ifsc);

        if ($virtualAccount === null) {
            return false;
        }

        // Check2: Check if experiment is enabled for the given merchant
        $isCollectxRblExpEnabled = $this->isCollectxRblExperimentEnabled($virtualAccount->getMerchantId());

        if ($isCollectxRblExpEnabled !== true) {
            return false;
        }

        // Check3: Check if collectx feature is enabled for attached merchant
        if (($virtualAccount->merchant !== null) &&
            ($virtualAccount->merchant->isFeatureEnabled(Feature\Constants::COLLECTX_ENABLED) === true)) {
            return true;
        }

        return false;
    }

    protected function checkForAxisBankCollectxCallback(array $input): bool
    {
        // Check 1: Check if bank account and VA exists for the given account number and IFSC
        $accountNumber = $input["payee_account"];

        $ifsc = $input["payee_ifsc"];

        // ifsc is overriden to common ifsc to bypass the common corp code check which is happening outside.
        $ifsc = Provider::getIFSC(true)[Provider::AXIS];

        /* @var VirtualAccountEntity $virtualAccount*/
        $virtualAccount = $this->getVirtualAccountUsingAccountNumberAndIfsc($accountNumber, $ifsc);

        if ($virtualAccount === null) {
            return false;
        }


        // Check2: Check if experiment is enabled for the given merchant
        $isCollectxAxisExpEnabled = $this->isCollectxAxisExperimentEnabled($virtualAccount->getMerchantId());

        if ($isCollectxAxisExpEnabled !== true) {
            return false;
        }

        // Check 2: Check if collectx feature is enabled for attached merchant
        $isCollectxFeatureEnabled = $virtualAccount->merchant->isFeatureEnabled(Feature\Constants::COLLECTX_ENABLED);

        if ($isCollectxFeatureEnabled === false) {
            return false;
        }

        // Check 3: Check if the account number is prefixed with the collectx series
        $merchantID = $virtualAccount->getMerchantId();

        $isCollectxAccountNumber = $this->isCollectxAccountNumber($accountNumber, $merchantID, $input);

        if ($isCollectxAccountNumber === true) {
            return true;
        }

        return false;
    }

    protected function isCollectxAccountNumber($accountNumber, $merchantID, $input): bool
    {
        try
        {
            $config = (new Admin\Service)->getConfigKey(
                ['key' => Admin\ConfigKey::COLLECTX_SERIES_PREFIX]);
        }
        catch(\Exception $ex)
        {
            $this->trace->info(
                TraceCode::COLLECTX_REDIS_GET_FAILURE, [
                    'input' => $input
                ]
            );

            return false;
        }

        if (empty($config) === true)
        {
            return false;
        }

        if (array_key_exists($merchantID, $config) === true)
        {
            $prefix = $config[$merchantID];

            if (stripos($accountNumber, $prefix) == 0) {
                return true;
            }
        }

        return false;
    }

    protected function isCollectxRblExperimentEnabled($merchantID): bool
    {
        $properties = [
            "id" => $merchantID,
            "experiment_name" => RazorxTreatment::COLLECTX_RBL_PAYMENT_TRANSFER_RAMP_UP,
            'request_data'  => json_encode(['id' => $merchantID])
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable') === true;
    }

    protected function isCollectxAxisExperimentEnabled($merchantID): bool
    {
        $properties = [
            "id" => $merchantID,
            "experiment_name" => RazorxTreatment::COLLECTX_AXIS_PAYMENT_TRANSFER_RAMP_UP,
            'request_data'  => json_encode(['id' => $merchantID])
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable') === true;
    }

    protected function getVirtualAccountUsingAccountNumberAndIfsc(string $accountNumber, string $ifsc)
    {
        $bankAccount = $this->repo
            ->bank_account
            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $ifsc, true);

        if ($bankAccount === null or $bankAccount->source === null) {
            return null;
        }

        return $bankAccount->source;
    }

    protected function incrementCollectxCallbackMetric(array $formattedInput, string $provider): void
    {
        $transferMethod = $formattedInput[Entity::MODE];

        $this->trace->count(BankTransferMetrics::COLLECTX_BANK_CALLBACK_COUNT, [
            'provider'          => $provider,
            'transfer_method'   => $transferMethod === self::TRANSFER_TYPE_UPI ? Constants\Entity::UPI_TRANSFER : Constants\Entity::BANK_TRANSFER,
            'request_type'      => $formattedInput[Entity::REQUEST_TYPE],
        ]);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function performCollectxValidations(array $input, string $provider, string $merchantId): void
    {
        $mode = $input[Entity::MODE];

        // Each method inside would raise exceptions if anything fails
        // Exceptions to be handled at the caller
        switch($mode){
            case self::TRANSFER_TYPE_UPI:
                $this->validateDuplicateUpiRequest($input, $provider);
                $this->checkForUnexpectedUpiTransferPayments($input, $provider);
                $this->checkForAvailableBalanceAndFeeCredits($merchantId, $input, $provider, Constants\Entity::UPI_TRANSFER);
                break;

            case self::TRANSFER_TYPE_IMPS:
            case self::TRANSFER_TYPE_NEFT:
            case self::TRANSFER_TYPE_RTGS:
            case self::TRANSFER_TYPE_FT:
            case self::TRANSFER_TYPE_IFT:
            case self::TRANSFER_TYPE_TRANSFER:
                $this->validateDuplicateRequest($input, null, true);
                $this->checkForUnexpectedBankTransferPayments($input, $provider);
                $this->checkForAvailableBalanceAndFeeCredits($merchantId, $input, $provider, Constants\Entity::BANK_TRANSFER);
                break;

            default:
                $ex = new Exception\BadRequestValidationFailureException(
                    ErrorCode::INVALID_MODE_COLLECTX_TRANSFER,
                    $mode
                );

                $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $input, $provider, $mode);

                throw $ex;
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function checkForAvailableBalanceAndFeeCredits(string $merchantId, $input, $provider, $method): bool
    {
        $availableBalance = $this->getAvailableBalanceForMerchantWithFeeCredits($merchantId);

        if ($availableBalance < self::COLLECTX_DEFAULT_FEE_CREDITS_THRESHOLD)
        {
            $ex = new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD,
                $merchantId
            );

            $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $input, $provider, $method);

            throw $ex;
        }

        return true;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function getAvailableBalanceForMerchantWithFeeCredits(string $merchantID):int
    {
        /** @var MerchantEntity $merchant */
        $merchant = $this->repo->merchant->getMerchant($merchantID);

        $isMerchantOnCLS = $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if($isMerchantOnCLS)
        {
            return $this->getAvailableBalanceForCLSMerchant($merchant);
        }

        return $this->getAvailableBalanceForAPIMerchant($merchant);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function getAvailableBalanceForCLSMerchant(MerchantEntity $merchant): int
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountsList = (new CLSPayments\Core())->getMerchantAccounts($ledgerService, $merchant->getId());

        $merchantAccountBalances = (new CLSPayments\Core())->getMerchantAccountBalancesMap($merchantAccountsList);

        $merchantBalance = $merchantAccountBalances[LedgerConstants::MERCHANT_BALANCE];

        $merchantFeeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];

        $this->trace->info(TraceCode::COLLECTX_CREDITS_BALANCE_DEBUG, [
            "merchant_id" => $merchant->getId(),
            "merchant_on_cls" => true,
            "balance" => $merchantBalance,
            "fee_credits" => $merchantFeeCredits,
        ]);

        return $merchantBalance + $merchantFeeCredits;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function getAvailableBalanceForAPIMerchant(MerchantEntity $merchant): int
    {
        /** @var \RZP\Models\Customer\Balance\Entity $balance */
        $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(),
            Merchant\Balance\Type::PRIMARY);

        if ($balance === null)
        {
            $ex = new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_PRIMARY_BALANCE_UNAVAILABLE_FOR_FEE_CREDITS,
                $merchant->getId()
            );

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::COLLECTX_PRIMARY_BALANCE_UNAVAILABLE_FOR_FEE_CREDITS, [
                    "merchant_id" => $merchant->getId()
                ]
            );
        }

        /** @var MerchantEntity $merchant */
        $merchant = $this->repo->merchant->getMerchant($merchant->getId());

        $merchantBalance = $balance->getBalance();

        $creditsArray = $this->repo->credits->getTypeAggregatedNonRefundMerchantCreditsWithoutActiveDBTransaction($merchant);

        if (empty($creditsArray) || isset($creditsArray[Credits\Type::FEE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_FEE_CREDITS_UNAVAILABLE_FOR_API_MERCHANT,
                $merchant->getId()
            );
        }

        $feeCredits = $creditsArray[Credits\Type::FEE] ?? 0;

        $this->trace->info(TraceCode::COLLECTX_CREDITS_BALANCE_DEBUG, [
            "merchant_id" => $merchant->getId(),
            "merchant_on_cls" => false,
            "balance" => $merchantBalance,
            "fee_credits" => $feeCredits,
        ]);

        return $merchantBalance + $feeCredits;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function validateDuplicateUpiRequest(array $input, string $provider): void
    {
        $providerReferenceId = $input['transaction_id'];

        // Use provider to get provider code when live with more than just Yesbank for UPI
        $payeeVpa = $input["payee_account"] . "@" . ProviderCode::YESBANKLTD;

        $amount = $input["amount"] * 100;

        $upiTransferEntity = $this->repo->upi_transfer->findByProviderReferenceIdAndPayeeVpaAndAmount(
            $providerReferenceId,
            $payeeVpa,
            $amount);

        if ($upiTransferEntity !== null) {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_DUPLICATE_UPI_TRANSFER_REQUEST,
                $input
            );
        }
    }

    protected function checkForAxisValidationCallback(array $input, string $provider) : bool
    {
        // Checking if this is a validation callback for Axis
        if ($provider === Provider::AXIS &&
            $input[Entity::REQUEST_TYPE] === self::VALIDATION_CALLBACK) {
            return true;
        }
        return false;
    }

    protected function pushToCollectXWorker(array $input, string $provider)
    {
        $this->trace->info(
            TraceCode::COLLECTX_PROCESS_TRANSFER_SQS_PUSH_INIT,
            [
                Entity::GATEWAY => $provider,
            ]
        );

        ProcessCollectxTransfer::dispatch($this->mode, $input, $provider);
    }


    protected function isExperimentEnabledForCollectXWorkerFlow(array $input, string $provider, $requestPayload): array
    {
        try
        {
            $formattedInput = $this->formatInputForCollectx($input, $provider, $requestPayload);

            $merchantID = "";

            switch($formattedInput[Entity::MODE]){
                case self::TRANSFER_TYPE_UPI:
                    $merchantID = $this->getMerchantIDForVPAPayment($formattedInput);
                    break;
                case self::TRANSFER_TYPE_IMPS:
                case self::TRANSFER_TYPE_NEFT:
                case self::TRANSFER_TYPE_RTGS:
                case self::TRANSFER_TYPE_FT:
                case self::TRANSFER_TYPE_IFT:
                case self::TRANSFER_TYPE_TRANSFER:
                    $merchantID = $this->getMerchantIDForBankAccountPayment($formattedInput);
                    break;

                default:
                    $ex = new Exception\BadRequestValidationFailureException(
                        ErrorCode::INVALID_MODE_COLLECTX_TRANSFER,
                        $formattedInput[Entity::MODE]
                    );

                    throw $ex;
            }

            $properties = [
                "id" => $merchantID,
                "experiment_name" => RazorxTreatment::COLLECTX_WORKER_FLOW
            ];

            $expEnabled = (new \RZP\Models\Merchant\Core())->isSplitzExperimentEnable($properties, 'enable') === true;

            return [
                "merchant_id" => $merchantID,
                "enabled" => $expEnabled
            ];
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::COLLECTX_WORKER_FLOW_EXPERIMENT_EXCEPTION, [
                    "input" => $input
                ]
            );

            return [
                "merchant_id" => "",
                "enabled" => false
            ];
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function getMerchantIDForVPAPayment(array $formattedInput): string
    {
        // Only available for Yesbank right now
        // This will throw exception internally if VPA or VA does not exist
        $vpa = $this->validateAndGetVpaForCollectxPayment($formattedInput, ProviderCode::YESBANKLTD);

        /** @var VirtualAccountEntity $virtualAccount */
        $virtualAccount = $vpa->source;

        return $virtualAccount->getMerchantId();

    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function getMerchantIDForBankAccountPayment(array $formattedInput): string
    {
        $accountNumber = $formattedInput["payee_account"];

        $ifsc = $formattedInput["payee_ifsc"];

        /* @var VirtualAccountEntity $virtualAccount*/
        $virtualAccount = $this->getVirtualAccountUsingAccountNumberAndIfsc($accountNumber, $ifsc);

        if ($virtualAccount === null) {
            $this->trace->info(TraceCode::COLLECTX_WORKER_FLOW_UNABLE_TO_FIND_VA);

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNKNOWN_BANK_TRANSFER_REQUEST,
                [
                    "input" => $formattedInput
                ]
            );
        }

        return $virtualAccount->getMerchantId();
    }

    protected function handleCollectXCallback(array $input, string $provider, $requestPayload): array
    {
        $response = [];

        $formattedInput = $this->formatInputForCollectx($input, $provider, $requestPayload);

        $this->trace->info(TraceCode::COLLECTX_FORMATTED_INPUT, [
            "input"          => $input,
            "formattedInput" => $formattedInput,
            "provider"       => $provider
        ]);

        $transferMethod = strtoupper($formattedInput[Entity::MODE]);

        $this->trace->count(BankTransferMetrics::COLLECTX_BANK_CALLBACK_COUNT, [
            'provider'          => $provider,
            'transfer_method'   => $transferMethod === self::TRANSFER_TYPE_UPI ? Constants\Entity::UPI_TRANSFER : Constants\Entity::BANK_TRANSFER,
            'request_type'      => $formattedInput[Entity::REQUEST_TYPE],
        ]);

        switch ($transferMethod){

            case self::TRANSFER_TYPE_UPI:
                $response =  self::routeForCollectXUPIRequest($formattedInput, $provider);
                break;

            case self::TRANSFER_TYPE_IMPS:
            case self::TRANSFER_TYPE_NEFT:
            case self::TRANSFER_TYPE_RTGS:
            case self::TRANSFER_TYPE_FT:
            case self::TRANSFER_TYPE_IFT:
            case self::TRANSFER_TYPE_TRANSFER:
                $response =  self::routeForCollectXBankTransferRequest($formattedInput, $provider, $requestPayload, "bank_transfer_process");
                break;

            default:
                $this->trace->info(
                    TraceCode::INVALID_MODE_COLLECTX_BANK_TRANSFER, [
                        'mode' => $transferMethod
                    ]
                );

                $response['valid'] = false;
        }

        $this->trace->info(TraceCode::COLLECTX_TRANSFER_PAYMENT_RESPONSE,[
            'response' => $response,
            'provider' => $provider,
            'mode'     => $transferMethod
        ]);

        return $this->modifyCollectxResponseBasedOnProvider($response, $provider);
    }

    protected function handleCollectXCallbackWorkerFlow(array $input, string $provider, $requestPayload, $merchantId): array
    {
        try {
            $this->trace->info(TraceCode::COLLECTX_WORKER_FLOW_MERCHANT_START, [
                "input" => $input,
                "provider" => $provider
            ]);

            $response = [];

            $formattedInput = $this->formatInputForCollectx($input, $provider, $requestPayload);

            // Metric update, no business logic
            $this->incrementCollectxCallbackMetric($formattedInput, $provider);

            // 1. Perform validations for both modes
            $this->performCollectxValidations($formattedInput, $provider, $merchantId);

            // if AXIS Validation callback, we directly return
            if ($this->checkForAxisValidationCallback($formattedInput, $provider))
            {
                return $this->handleAxisValidationCallback($input, $provider);
            }

            // 2. Push to worker, worker will create the required entities
            $this->pushToCollectXWorker($formattedInput, $provider);

            // 3. Respond to the bank with success or non success response
            $response['valid'] = true;
        }

        catch(\Exception $ex) {

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::COLLECTX_HANDLE_WORKER_FLOW_EXCEPTION, [
                'provider'       => $provider,
                'utr' => $input[Entity::REQ_UTR]
            ]);

            $response['valid'] = false;
        }

        return $this->modifyCollectxResponseBasedOnProvider($response, $provider);
    }

    protected function modifyCollectxResponseBasedOnProvider(array $response, string $provider): array
    {
        $valid = $response['valid'];

        switch ($provider)
        {
            case Provider::YESBANK:
                return [
                    'validateResponse' => [
                        'decision' => $valid ? 'pass' : 'reject'
                    ]
                ];

            case Provider::RBL:
            case Provider::AXIS:
                $response['isCollectXResponse'] = true;

                return $response;
        }

        return $response;
    }

    protected function formatInputForCollectx(array $input, string $provider, $requestPayload): array
    {
        $formattedPayload = $input;
        switch ($provider)
        {
            case Provider::YESBANK:
                $formattedPayload = $this->formatYesbankInputForCollectX($input);
                break;

            case Provider::RBL:
                $formattedPayload = $this->formatRblInputForCollectX($input, $requestPayload);
                break;

            case Provider::AXIS:
                $formattedPayload = $this->formatAxisInputForCollectX($input);
                break;

            default:
                $this->trace->info(
                    TraceCode::INVALID_PROVIDER_COLLECTX_BANK_TRANSFER, [
                        'provider' => $provider
                    ]
                );
        }

        $this->trace->info(TraceCode::COLLECTX_FORMATTED_INPUT, [
            "input"          => $input,
            "formattedInput" => $formattedPayload,
            "provider"       => $provider
        ]);

        $formattedPayload[Entity::MODE] = strtoupper($formattedPayload[Entity::MODE]);

        return $formattedPayload;
    }

    protected function formatAxisInputForCollectX(array $input): array
    {
        $input['payee_ifsc'] = Provider::getIFSC(true)[Provider::AXIS];

        return $input;
    }

    protected function formatRblInputForCollectX(array $input, $requestPayload): array
    {
        // adding credit account number field here because this would be used for bene account validation later
        $input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER] = $requestPayload['Data'][0][BankTransferConstants::CREDIT_ACCOUNT_NUMBER];

        // this is to handle the case where the mode is set to UPI in controller but the actual mode is IMPS
        // Make sure this is changed once rbl is live in for UPI, in that case we have to make changes in bank transfer controller where this is set.
        if ($input[Entity::MODE] === BankTransferModes::UPI) {
            $input[Entity::MODE] = BankTransferModes::IMPS;
        }

        return $input;
    }

    protected function formatYesbankInputForCollectX(array $input): array
    {
        if (array_key_exists(self::COLLECTX_VALIDATION_YESBANK_CALLBACK_IDENTIFIER, $input) === false) {
            return [];
        }

        $input = $input[self::COLLECTX_VALIDATION_YESBANK_CALLBACK_IDENTIFIER];

        return [
            "amount"            => $input["transfer_amt"],
            "description"       => $input["rmtr_to_bene_note"],
            "mode"              => $input["transfer_type"],
            "payee_account"     => $input["bene_account_no"],
            "payee_ifsc"        => $input["bene_account_ifsc"],
            "payer_account"     => $input["rmtr_account_no"],
            "payer_ifsc"        => $input["rmtr_account_ifsc"],
            "payer_name"        => $input["rmtr_full_name"],
            "time"              => Carbon::createFromFormat('Y-m-d H:i:s', $input["transfer_timestamp"], Timezone::IST)->timestamp,
            "transaction_id"    => $input["transfer_unique_no"],
            "request_type"      => self::VALIDATION_CALLBACK
        ];
    }

    public function routeForCollectXUPIRequest(array $input, string $provider): array
    {
        try
        {
            $this->checkForUnexpectedUpiTransferPayments($input, $provider);

            return (new UpiTransfer\Service())->processUpiTransferPayment($input, Gateway::UPI_YESBANK, isCollectXPayment: true);
        }
        catch (\Exception $ex)
        {
            return [
                'valid' => false,
                'message' => null,
                'transaction_id' => $input['transaction_id'] ?? '',
            ];
        }
    }

    public function routeForCollectXBankTransferRequestViaWorkerFlow(array $input, string $provider, $routeName): array
    {
        try
        {
            // need to unset here because input will be used to build BTR and BT entities.
            if (isset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER])) {
                unset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER]);
            }

            $bankTransferRequest = (new BankTransferRequest\Core())->create(
                $input,
                $provider,
                $input,
                [],
                $routeName
            );

            $bankTransferRequest->markAsCollectXBankTransfer();

            $this->processBankTransfer($bankTransferRequest);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::COLLECTX_BANK_TRANSFER_SAVE_REQUEST_FAILED, [
                'provider'       => $provider,
                'transaction_id' => $input[Entity::REQ_UTR]
            ]);

            return [
                'valid' => false,
                'message' => null,
                'transaction_id' => $input[Entity::REQ_UTR] ?? '',
            ];
        }
        return [
            'valid' => true,
            'message' => null,
            'transaction_id' => $input[Entity::REQ_UTR] ?? '',
        ];
    }

    protected function routeForCollectXBankTransferRequest(array $input, string $provider, $requestPayload, $routeName): array
    {
        try
        {
            // Will throw an exception if duplicate request
            $this->validateDuplicateRequest($input, $routeName, true);

            $this->checkForUnexpectedBankTransferPayments($input, $provider);

            // if current call is validation call for axis, we return success response after checks
            if ($provider === Provider::AXIS &&
                $input[Entity::REQUEST_TYPE] === self::VALIDATION_CALLBACK) {

                return $this->handleAxisValidationCallback($input, $provider);

            }

            // need to unset here because input will be used to build BTR and BT entities.
            if (isset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER])) {
                unset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER]);
            }

            $bankTransferRequest = (new BankTransferRequest\Core())->create(
                $input,
                $provider,
                $requestPayload ?? $input,
                [],
                $routeName
            );

            return $this->dispatchBankTransferToQueue($bankTransferRequest, true);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::COLLECTX_BANK_TRANSFER_SAVE_REQUEST_FAILED, [
                    'provider'       => $provider,
                    'transaction_id' => $input[Entity::REQ_UTR]
                ]);

            return [
                'valid' => false,
                'message' => null,
                'transaction_id' => $input[Entity::REQ_UTR] ?? '',
            ];
        }
    }

    protected function handleAxisValidationCallback(array $input, string $provider): array
    {
        $this->trace->info(TraceCode::COLLECTX_BANK_TRANSFER_VALIDATION_WEBHOOK_VALIDATED, [
            'provider' => $provider,
            'input'    => $input
        ]);

        return [
            'valid' => true,
            'message' => null,
            'transaction_id' => $input[Entity::REQ_UTR] ?? '',
        ];
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function checkForUnexpectedBankTransferPayments(array $input, string $provider): void
    {
        try
        {
            $this->validateProviderForCollectxBankTransfer($provider, $input);

            $bankAccount = $this->validateAndGetBankAccountForCollectxPayment($input);

            /** @var VirtualAccountEntity $virtualAccount */
            $virtualAccount = $bankAccount->source;

            $balance = $this->repo->balance->findOrFailById($virtualAccount->getBalanceId());

            $this->validateVirtualAccountStatusForCollectxPayments($virtualAccount, $input);

            $this->validateBalanceTypeForCollectxPayments($balance, $input);

            $this->validateCreditAccountNumberForRblCollectxPayments($balance, $provider, $input);

            $this->validateTpvForCollectxPayments($virtualAccount, $input, $provider);
        }
        catch (\Exception $ex)
        {
            $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $input, $provider, Constants\Entity::BANK_TRANSFER);

            throw $ex;
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    public function checkForUnexpectedUpiTransferPayments(array $input, string $provider): void
    {
        try
        {
            $this->validateProviderForCollectxUPI($provider, $input);

            $vpa = $this->validateAndGetVpaForCollectxPayment($input, ProviderCode::YESBANKLTD);

            /** @var VirtualAccountEntity $virtualAccount */
            $virtualAccount = $vpa->source;

            $balance = $this->repo->balance->findOrFailById($virtualAccount->getBalanceId());

            $this->validateVirtualAccountStatusForCollectxPayments($virtualAccount, $input);

            $this->validateTpvForCollectxPayments($virtualAccount, $input, $provider);

            // not adding below check here because some of Swiggy VAs are attached with primary balance.
            // TODO: Uncomment this once fix is live for VA creation.
            // $this->validateBalanceTypeForCollectxPayments($balance, $input);
        }
        catch (\Exception $ex)
        {
            $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $input, $provider, Constants\Entity::UPI_TRANSFER);

            throw $ex;
        }
    }

    protected function traceExceptionAndPushUnexpectedPaymentMetric(\Exception $ex, array $input, string $provider, $method): void
    {
        $this->trace->traceException(
            $ex,
            Trace::CRITICAL,
            TraceCode::COLLECTX_UNEXPECTED_PAYMENT_TRANSFER_ERROR,
            [
                'input' => $input,
                'provider' => $provider,
                'error_code' => $ex->getCode(),
                'error_message' => $ex->getMessage()
            ]);

        $this->trace->count(
            BankTransferMetrics::COLLECTX_UNEXPECTED_PAYMENT_TRANSFER_COUNT,
            [
                Payment\Entity::PROVIDER => $provider,
                'method'                 => $method,
                'error_code'             => $ex->getMessage()
            ]);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateAndGetBankAccountForCollectxPayment(array $input): object
    {
        $accountNumber = $input["payee_account"];

        $ifsc = $input["payee_ifsc"];

        $bankAccount = $this->repo
            ->bank_account
            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $ifsc, true);

        if ($bankAccount === null || $bankAccount->source === null) {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNKNOWN_BANK_TRANSFER_REQUEST,
                $input
            );

        }

        return $bankAccount;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateAndGetVpaForCollectxPayment(array $input, string $handle): object
    {
        $payeeVpa = $input["payee_account"]."@".$handle;

        $vpa = $this->repo
            ->vpa
            ->findByAddressAndEntityTypes($payeeVpa, [Entity::VIRTUAL_ACCOUNT], true);

        if ($vpa === null || $vpa->source === null) {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNKNOWN_UPI_TRANSFER_REQUEST,
                $input
            );

        }

        return $vpa;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateProviderForCollectxUPI(string $provider, array $input): void
    {
        if (in_array($provider, Provider::COLLECTX_UPI_PROVIDERS) === false) {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNSUPPORTED_UPI_TRANSFER_REQUEST,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateProviderForCollectxBankTransfer(string $provider, array $input): void
    {
        if (in_array($provider, Provider::COLLECTX_BANK_TRANSFER_PROVIDER) === false) {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNSUPPORTED_BANK_TRANSFER_REQUEST,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateVirtualAccountStatusForCollectxPayments(VirtualAccountEntity $virtualAccount, array $input): void
    {
        if ($virtualAccount->getStatus() === "closed") {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateBalanceTypeForCollectxPayments(Merchant\Balance\Entity $balance, array $input): void
    {
        if ($balance->isTypeBanking() === false || $balance->isAccountTypeDirect() === false) {

            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_NON_DIRECT_OR_NON_BANKING__VA,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateCreditAccountNumberForRblCollectxPayments(Merchant\Balance\Entity $balance, string $provider, array $input): void
    {
        if ($provider === Provider::RBL) {

            $attachedCreditAccountNumber = $balance->getAccountNumber();

            $receivedCreditAccountNumber = $input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER];

            if ($attachedCreditAccountNumber !== $receivedCreditAccountNumber) {

                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_CREDIT_ACCOUNT_MISMATCH,
                    $input);
            }
        }
    }

    protected function validateTpvForCollectxPayments(VirtualAccountEntity $virtualAccount, array $input, string $provider)
    {
        if ($provider != Provider::RBL)
        {
            $processor = new Processor();

            $isVerifiedPayer = $processor->verifyPayerUsingTPVForCollectX($virtualAccount, $input);

            if ($isVerifiedPayer === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_BY_NON_ALLOWED_PAYER,
                    $input
                );

            }
        }
    }

    protected function processValidationRequest(array $input, string $provider = null)
    {
        if ((array_key_exists('request_type', $input)) and
            ($input['request_type'] == 'validation') and
            (in_array($provider, Provider::VALIDATE_CALLBACK_PROVIDERS) === true)) {
            try {
                if (empty($provider) === false) {
                    $this->provider = $provider;
                }

                $valid = $this->core->validationsForCallback($input, $this->provider);

                if ($valid !== true) {
                    throw new Exception\BadRequestValidationFailureException(
                        "Validations failed for callback", $input);
                }

                //Validate duplicate request
                if (!isset($input[Entity::AMOUNT], $input[Entity::REQ_UTR], $input[Entity::PAYEE_ACCOUNT]) === true) {
                    throw new Exception\BadRequestValidationFailureException(
                        "Amount/UTR/Payee account number not valid", $input);
                }

                (new Validator)->validateInput('validateDuplicateReq', array(Entity::AMOUNT => $input[Entity::AMOUNT],
                    Entity::REQ_UTR => $input[Entity::REQ_UTR],
                    Entity::PAYEE_ACCOUNT => $input[Entity::PAYEE_ACCOUNT]));

                $duplicateBankTransfer = $this->repo
                    ->bank_transfer
                    ->findByUtrAndPayeeAccountAndAmount($input[Entity::REQ_UTR],
                        $input[Entity::PAYEE_ACCOUNT],
                        $input[Entity::AMOUNT] * 100);

                if ($duplicateBankTransfer !== null) {
                    throw new Exception\BadRequestValidationFailureException(
                        "Duplicate Amount/UTR/Payee account number for bank transfer request", $input);
                }

                return [
                    'valid' => true,
                    'message' => null,
                    'transaction_id' => $input[Entity::REQ_UTR] ?? '',
                ];

            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::BANK_TRANSFER_VALIDATE_REQUEST_FAILED);

                return [
                    'valid' => false,
                    'message' => null,
                    'transaction_id' => $input[Entity::REQ_UTR] ?? '',
                ];
            }
        }
        return [];
    }

    protected function validateAndProcessRequest(
        array                      $input,
        BankTransferRequest\Entity $bankTransferRequest,
        string                     $provider = null,
        bool                       $checkForIfsc = false,
        bool                       $skipPayeeAccountLengthValidation = false,
        bool                       $isCollectXBankTransfer = false)
    {
        if ($bankTransferRequest !== null and $bankTransferRequest->getPayeeAccount() !== null)
        {
            if ($skipPayeeAccountLengthValidation === false) {
                $response = $this->validateProviderSpecificFields($bankTransferRequest);

                if (empty($response) === false) {
                    return $response;
                }
            }

            return $this->dispatchBankTransferToQueue($bankTransferRequest);
        }

        return $this->process($input, $provider, $checkForIfsc);
    }

    public function removeInvalidRegexFromPayerAccount(&$input)
    {
        if (isset($input[Entity::PAYER_ACCOUNT]) === true) {
            $payerAccountNumber = $input[Entity::PAYER_ACCOUNT];

            $payerAccountInvalidRegexes = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES]);

            foreach ($payerAccountInvalidRegexes as $invalidRegex) {
                $invalidPrefixRegex = '/' . $invalidRegex . '/i';

                $payerAccountNumber = preg_replace($invalidPrefixRegex, '', $payerAccountNumber); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval
            }

            $input[Entity::PAYER_ACCOUNT] = $payerAccountNumber;
        }
    }

    /**
     * HSBC data shows an exception where payer account has the value `IN` and its correct value is part of the payer name
     * This method is used to extract out payer account from the incoming payer name and update the payer name
     */
    private function extractPayerNameAndAccountFromPayerName(&$input)
    {
        $invalidPayerAccountValue = 'IN';

        if (isset($input[Entity::PAYER_ACCOUNT]) === true && $input[Entity::PAYER_ACCOUNT] === $invalidPayerAccountValue) {
            $payerName = $input[Entity::PAYER_NAME];

            $payerAccountNameInvalidRegexes = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::PAYER_ACCOUNT_NAME_INVALID_REGEXES]);

            foreach ($payerAccountNameInvalidRegexes as $invalidRegex) {
                $invalidPrefixRegex = '/' . $invalidRegex . '/i';

                $payerName = preg_replace($invalidPrefixRegex, '', $payerName); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval
            }

            $payerAccountAndNameArr = explode(" ", trim($payerName), 2);

            if (sizeof($payerAccountAndNameArr) === 2) {
                $input[Entity::PAYER_ACCOUNT] = $payerAccountAndNameArr[0];

                $input[Entity::PAYER_NAME] = $payerAccountAndNameArr[1];
            }
        }
    }

    private function getQrBankAccount(array $input)
    {
        $payeeAccount = $input['payee_account'];

        return $this->repo->bank_account->getBankAccountsFromAccountNumberAndType($payeeAccount, BankAccount\Type::QR_CODE, true);
    }

    public function processBankTransfer(BankTransferRequest\Entity $bankTransferRequest)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            $bankTransferRequest->toArrayTrace()
        );

        $this->provider = $bankTransferRequest->getGateway();

        $this->validateProvider($bankTransferRequest->getUtr());

        $this->checkBlocksAndUpdateRequest($bankTransferRequest);

        $valid = $this->core->processBankTransfer($bankTransferRequest);

        return [
            'valid' => $valid,
            'message' => null,
            'transaction_id' => $bankTransferRequest->getUtr() ?? '',
        ];
    }

    /**
     * Entry point for Kotak or other providers. Response contains
     * UTR because it was requested, no idea how it's useful.
     *
     * @param array $input
     *
     * @param string|null $provider
     * @param bool $checkForIfsc
     * @return array
     * @throws Exception\BadRequestException
     * @throws LogicException
     */
    public function process(array $input, string $provider = null, bool $checkForIfsc = false): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            $this->core->removePiiForLogging($input)
        );

        if (empty($provider) === false) {
            $this->provider = $provider;
        }

        $this->validateProvider($input[Entity::REQ_UTR]);

        $this->checkBlocks($input);

        $valid = $this->core->process($input, $this->provider);

        return [
            'valid' => $valid,
            'message' => null,
            'transaction_id' => $input[Entity::REQ_UTR] ?? '',
        ];
    }

    public function processFile(array $input, $batchType): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            [
                'input' => $input,
                'batch_type' => $batchType,
            ]
        );

        Batch\Type::validateType($batchType);

        $source = $this->getRequestSource();

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST_SOURCE,
            [
                'source' => $source,
                'batch_type' => $batchType,
            ]
        );

        $requestProcessor = $this->getRequestProcessor($source);

        $fileDetails = $requestProcessor->processForVa($input);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            [
                'file details' => $fileDetails,
            ]
        );

        $batchCore = new Batch\Core;

        if (isset($fileDetails['file_details']) === true) {
            $file = new File($fileDetails['file_details'][0]['file_path']);

            $params = [
                Batch\Entity::TYPE => $batchType,
                Batch\Entity::FILE => $file,
            ];

            $sharedMerchant = $this->repo
                ->merchant
                ->findOrFailPublic(Account::SHARED_ACCOUNT);

            $batch = $batchCore->create($params, $sharedMerchant);

            return $batch->toArrayPublic();
        }

        return [];
    }

    protected function checkAndReplaceForIfsc(array &$input, string $provider = null)
    {
        if ($provider === Provider::ICICI) {
            if ((isset($input[Entity::PAYER_IFSC]) === false) or
                ($input[Entity::PAYER_IFSC] === '')) {
                $input[Entity::PAYER_IFSC] = BankCodes::IFSC_ICIC;
            }
        }

        if (isset($input[Entity::PAYER_IFSC]) === false) {
            return;
        }

        $ifsc = $input[Entity::PAYER_IFSC];

        $ifscValidator = new BankAccount\Validator;

        try {
            $ifscValidator->validateIfscCode([BankAccount\Entity::IFSC_CODE => $ifsc]);
        } catch (Exception\BadRequestValidationFailureException $exception) {
            $bankCode = substr($ifsc, 0, 4);

            $defaultIfscCode = BankCodes::getIfscForBankCode($bankCode);

            if ($defaultIfscCode === null) {
                $input[Entity::PAYER_IFSC] = '';

                return;
            }

            $input[Entity::PAYER_IFSC] = $defaultIfscCode;
        }
    }

    /**
     * Kotak has a second route that it hits to notify us of a bank transfer payment.
     * It was useful when these APIs were being planned, but serves no real purpose now.
     *
     * @param array $input
     *
     * @return array
     */
    public function notify(array $input): array
    {
        $inputTrace = $input;

        $this->unsetPIIData($inputTrace);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_NOTIFY_REQUEST,
            $inputTrace
        );

        // For CollectX, money transfer is happening once the validation callback succeeds
        // This should be extended to record notification of money transfer but for now, giving 200 response
        // to all CollectX notification callbacks
        if ($this->isCollectXCallback($input, Provider::YESBANK) === true and
            array_key_exists(self::COLLECTX_NOTIFICATION_YESBANK_CALLBACK_IDENTIFIER, $input)
        )
        {
            $response = [
                'notifyResult' => [
                    'result' => "ok"
                ]
            ];

            $this->trace->info(
                TraceCode::COLLECTX_YESB_NOTIFY_RESPONSE,
                [
                    'response' => $response
                ]);

            return $response;
        }

        $this->validateProvider();

        $success = $this->core->notify($input, $this->provider);

        return [
            'success' => $success,
            'message' => null,
            'transaction_id' => $input[Entity::REQ_UTR] ?? '',
        ];
    }

    public function unsetPIIData(array &$input)
    {
        unset($input[Entity::PAYER_NAME]);
        unset($input[Entity::PAYER_ACCOUNT]);
    }

    /**
     * Manual insertion of a bank transfer on behalf of another provider.
     *
     * @param string $provider
     * @param array $input
     *
     * @return array
     */
    public function insert(string $provider, array $input): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_MANUAL_PROCESS_REQUEST,
            [
                'provider' => $provider,
                'input' => $this->core->removePiiForLogging($input, [
                    Entity::PAYEE_ACCOUNT,
                    Entity::PAYER_ACCOUNT,
                    Entity::PAYER_NAME]),
            ]
        );

        if ($this->mode === Mode::LIVE) {
            Provider::validateLiveProvider($provider);
        }

        return $this->saveRequestAndProcess($input, $provider);
    }

    /**
     * This is used by the payment_bank_transfer_fetch route. Bank transfer
     * public entity contains payer bank account info for use by the merchant.
     *
     * @param string $paymentId
     *
     * @return array
     */
    public function fetchBankTransferForPayment(string $paymentId)
    {
        $payment = Tracer::inSpan(['name' => Constants\HyperTrace::BANK_TRANSFER_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT], function () use ($paymentId) {
            return $this->repo
                ->payment
                ->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $bankTransfer = Tracer::inSpan(['name' => Constants\HyperTrace::BANK_TRANSFER_SERVICE_FIND_BY_PAYMENT], function () use ($payment) {
            return $this->repo
                ->bank_transfer
                ->findByPayment($payment);
        });

        $response = $bankTransfer->toArrayPublic();

        // Bank transfer doesn't include VA in a public setter,
        // but it is required in this response. Adding explcitly.
        $response[Entity::VIRTUAL_ACCOUNT] = $bankTransfer->virtualAccount->toArrayPublic();

        return $response;
    }

    /**
     * Mutex lock on processing of failed bank transfer refunds
     *
     * @param array $input
     *
     * @return array
     */
    public function retryBankTransferRefund(array $input)
    {
        // Adding a lock for 15 minutes to avoid race conditions on the cron.
        // This cron is only executed once a day for now.
        $summary = $this->mutex->acquireAndRelease(
            'bank_transfer_refund_retry',
            function () use ($input) {
                return $this->core->retryBankTransferRefund($input);
            },
            self::FIFTEEN_MINUTES,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(
            TraceCode::REFUND_RETRY_RESULT,
            [
                'summary' => $summary
            ]);

        return $summary;
    }

    /**
     * An IP check is performed to ensure requests are coming from whitelisted IPs.
     *
     * @throws Exception\BadRequestException
     */
    protected function validateProvider(string $utr = null)
    {
        if ((Provider::validateIp($this->provider, $this->ip) === false) or
            (Provider::validateMode($this->provider, $this->mode) === false)) {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_PROVIDER_VALIDATION_FAILED,
                [
                    'provider' => $this->provider,
                    'ip' => $this->ip,
                    'mode' => $this->mode,
                    Entity::UTR => $utr
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function checkBlocks(array &$input)
    {
        if (($this->areBankTransfersBlockedForYesBank() === true) and
            ($this->provider === Provider::YESBANK)) {
            throw new LogicException('Payment made via YesBank');
        }

        if ($this->areBankTransfersBlockedForAllMerchants() === true) {
            //
            // VA va_B1zCTFrop7UWBT belongs to a test account
            // See Account::DEMO_VA_TEST
            //
            $input[Entity::PAYEE_ACCOUNT] = '5432100130473700';

            $this->trace->warning(TraceCode::BANK_TRANSFER_PROCESSING_REDIRECTED, $input);
        }
    }

    public function editPayerBankAccount(string $id, array $input)
    {
        $bankTransfer = $this->repo->bank_transfer->findByPublicId($id);

        (new BankTransferHistory\Service())->backupPayerBankAccount($bankTransfer, $input);

        $bankTransfer = $this->core->editPayerBankAccount($bankTransfer, $input);

        return $bankTransfer->toArrayPublic();
    }

    public function stripPayerBankAccounts(array $input)
    {
        $bankTransfers = $this->repo->bank_transfer->fetch($input);

        foreach ($bankTransfers as $bankTransfer) {
            $payerAccount = $bankTransfer->getPayerAccount();

            $this->core->editPayerBankAccount($bankTransfer, [
                'account_number' => BankCodes::modifyPayerAccount($payerAccount),
            ]);
        }

        return $bankTransfers->getPublicIds();
    }

    protected function areBankTransfersBlockedForAllMerchants(): bool
    {
        return $this->isBlockedByConfig(ConfigKey::BLOCK_SMART_COLLECT);
    }

    protected function areBankTransfersBlockedForYesBank(): bool
    {
        return $this->isBlockedByConfig(ConfigKey::BLOCK_YESBANK);
    }

    protected function isBlockedByConfig(string $key): bool
    {
        $block = false;

        try {
            $block = (bool)Cache::get($key);
        } catch (\Throwable $ex) {
            $this->trace->traceException($ex, Trace::CRITICAL);

            $block = false;
        }

        return $block;
    }

    protected function getRequestSource(): string
    {
        if ($this->isLambdaRequest() === true) {
            return RequestProcessor\Base::LAMBDA;
        } else {
            return RequestProcessor\Base::MAILGUN;
        }
    }

    /**
     * Checks if the request originated via an AWS Lambda trigger.
     *
     * @return bool
     */
    protected function isLambdaRequest(): bool
    {
        return ($this->auth->isLambda());
    }

    protected function getRequestProcessor(string $source)
    {
        $source = studly_case($source);

        $requestProcessor = 'RZP\\Reconciliator\\RequestProcessor\\' . $source;

        return new $requestProcessor();
    }

    private function dispatchBankTransferToQueue($bankTransferRequest, $isCollectXBankTransfer = false)
    {
        $isPushedToSqs = false;

        try {

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_SQS_PUSH_INIT,
                [
                    Entity::GATEWAY => $bankTransferRequest->getGateway(),
                    Entity::REQ_UTR => $bankTransferRequest->getUtr(),
                    'bankTransferRequestId' => $bankTransferRequest->getId(),
                    Entity::REQUEST_SOURCE => $bankTransferRequest->getRequestSource(),
                    'isCollectXBankTransfer' => $isCollectXBankTransfer,
                ]
            );

            BankTransferCreateProcess::dispatch($this->mode, $bankTransferRequest->getId(), $isCollectXBankTransfer);

            $isPushedToSqs = true;

        } catch (\Exception $e) {

            $this->trace->critical(
                TraceCode::BANK_TRANSFER_PROCESS_SQS_PUSH_FAILED,
                [
                    Entity::GATEWAY => $bankTransferRequest->getGateway(),
                    Entity::REQ_UTR => $bankTransferRequest->getUtr(),
                    'message' => $e->getMessage(),
                ]);

        }

        (new Metric())->pushSqsPushMetrics(
            Constants\Entity::BANK_TRANSFER,
            $bankTransferRequest->getGateway(),
            $isPushedToSqs,
            $isCollectXBankTransfer);

        return [
            'valid' => true,
            'message' => null,
            'transaction_id' => $bankTransferRequest->getUtr(),
        ];
    }

    protected function checkBlocksAndUpdateRequest(BankTransferRequest\Entity $bankTransferInput)
    {
        $input = [];

        $this->checkBlocks($input);

        if (isset($input[Entity::PAYEE_ACCOUNT]) === true) {
            $bankTransferInput->setPayeeAccount($input[Entity::PAYEE_ACCOUNT]);
        }
    }

    private function validateDuplicateRequest(array $input, $routeName = null, $isCollectXValidation = false)
    {
        if ($routeName === null) {
            $routeName = $this->app['api.route']->getCurrentRouteName();
        }

        if (($routeName === 'bank_transfer_process_rbl_internal') or
            ($routeName === 'bank_transfer_process_icici_internal') or
            ($routeName === 'bank_transfer_process_yesbank_internal') or
            ($routeName === 'bank_transfer_process_axis') or
            ($routeName === 'bank_transfer_process_axis_test') or
            ($routeName === 'bank_transfer_process_axis_internal') or
            ($routeName === 'bank_transfer_process_ibl') or
            ($routeName === 'bank_transfer_process_ibl_test') or
            ($routeName === 'bank_transfer_process_ibl_internal') or
            ($routeName === 'bank_transfer_validate_idfc') or
            ($routeName === 'bank_transfer_process_idfc') or
            ($routeName === 'bank_transfer_validate_idfc_test') or
            ($routeName === 'bank_transfer_process_idfc_test') or
            $isCollectXValidation) {

            if (!isset($input[Entity::AMOUNT], $input[Entity::REQ_UTR], $input[Entity::PAYEE_ACCOUNT]) === true) {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE, $input);
            }

            (new Validator)->validateInput('validateDuplicateReq', array(Entity::AMOUNT => $input[Entity::AMOUNT],
                Entity::REQ_UTR => $input[Entity::REQ_UTR],
                Entity::PAYEE_ACCOUNT => $input[Entity::PAYEE_ACCOUNT]));

            if ($this->doesRequestBelongToX($input)) {
                $duplicateBankTransfer = $this->repo->bank_transfer->findByCaseInsensitiveUtrAndPayeeAccountAndAmount($input[Entity::REQ_UTR],
                    $input[Entity::PAYEE_ACCOUNT],
                    $input[Entity::AMOUNT] * 100);

            } else {
                $duplicateBankTransfer = $this->repo->bank_transfer->findByUtrAndPayeeAccountAndAmount($input[Entity::REQ_UTR],
                    $input[Entity::PAYEE_ACCOUNT],
                    $input[Entity::AMOUNT] * 100);
            }


            if ($duplicateBankTransfer !== null) {

                if (($routeName === 'bank_transfer_process_axis') or
                    ($routeName === 'bank_transfer_process_axis_test') or
                    ($routeName === 'bank_transfer_process_axis_internal') or
                    ($routeName === 'bank_transfer_process_ibl') or
                    ($routeName === 'bank_transfer_process_ibl_test') or
                    ($routeName === 'bank_transfer_process_ibl_internal') or
                    ($routeName === 'bank_transfer_validate_idfc') or
                    ($routeName === 'bank_transfer_process_idfc') or
                    ($routeName === 'bank_transfer_validate_idfc_test') or
                    ($routeName === 'bank_transfer_process_idfc_test') or
                    $isCollectXValidation) {

                    throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK, $input);

                }

                return [
                    'valid' => true,
                    'message' => null,
                    'transaction_id' => $input[Entity::REQ_UTR] ?? '',
                ];
            }
        }

        return [];
    }

    protected function doesRequestBelongToX($input = []): bool
    {
        $payerIfsc = $input['payee_ifsc'] ?? '';
        if ($payerIfsc === Provider::getIFSC(true)[Provider::AXIS]) {
            return true;
        }

        return false;
    }

    protected function getProvider()
    {
        if (in_array($this->auth->getInternalApp(), ['merchant_dashboard', 'admin_dashboard']) === true) {
            return 'dashboard';
        }

        return $this->auth->getInternalApp();
    }

    protected function validateProviderSpecificFields(BankTransferRequest\Entity $bankTransferRequest)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Validation for ICICI (We are keeping this based on the route).
        if (($routeName === 'bank_transfer_process_icici_internal') or
            ($routeName === 'bank_transfer_process_icici')) {
            $payeeAccount = trim($bankTransferRequest->getPayeeAccount());

            $processor = new BankTransferProcessor();

            $isBankingType = $processor->getTransferTypeBasedOnPayeeAccount($payeeAccount);

            if ($isBankingType and strlen($payeeAccount) !== 16) {
                $this->trace->info(TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                    [
                        $bankTransferRequest->toArrayTrace()
                    ]);

                (new BankTransferRequest\Core)->updateBankTransferRequest($bankTransferRequest->getUtr(),
                    false,
                    TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                    $bankTransferRequest);

                $traceInfo = [
                    'message' => TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                    'transaction_id' => $bankTransferRequest->getUtr() ?? '',
                ];

                (new SlackNotification)->send(
                    'Received Payee Account Number with invalid length',
                    $traceInfo,
                    null,
                    1,
                    'x-finops');

                return [
                    'valid' => false,
                    'message' => TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                    'transaction_id' => $bankTransferRequest->getUtr() ?? '',
                ];
            }
        }

        return [];
    }

    /**
     * remove sender sensitive fields (account number, VA etc.) which are not required to be logged on the behalf of there banks callback.
     * @param array $input
     * @param string $traceCode
     * @return array
     */
    public function removeSenderSensitiveInfoFromLogging(array $input, string $provider)
    {
        switch ($provider) {
            case Provider::RBL:

                unset($input['Data'][0]['senderAccountNumber']);
                break;

            case Provider::ICICI :

                unset($input['Virtual_Account_Number_Verification_IN'][0]['payer_account']);
                break;

            case Provider::HDFC_ECMS :

                unset($input['Remitter_Account_No'], $input['Account_Number']);
                break;

            case Provider::IDFC:

                $sensitiveFieldsForIdfc = BankTransferConstants::SENSITIVE_DATA_FOR_VA_IDFC_CALLBACK;
                foreach ($sensitiveFieldsForIdfc as $key) {
                    if (isset($input[$key])) {
                        unset($input[$key]);
                    }
                }

                break;

            default:
                break;
        }
        return $input;
    }

    /**
     * Add test balance periodically to X Demo account
     */
    public function processBankTransferXDemoCron()
    {
        $merchant_id = \RZP\Models\Merchant\Account::X_DEMO_PROD_ACCOUNT;

        $x_demo_bank_account = \RZP\Constants\BankingDemo::BANK_ACCOUNT;

        $this->app['basicauth']->setMerchantById($merchant_id);

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $input = array(
            Entity::MODE => \RZP\Models\BankTransfer\Mode::NEFT,
            Entity::AMOUNT => 4000, // 1000 INR x 4 Demo payouts
            Entity::PAYER_ACCOUNT => $x_demo_bank_account,
            Entity::PAYEE_IFSC => 'RAZR0000001',
            Entity::PAYEE_ACCOUNT => $x_demo_bank_account,
            Entity::PAYER_IFSC => 'RAZR0000001',
            Entity::PAYER_NAME => 'Acme Corp',
            Entity::TIME => $timestamp,
            Entity::REQ_UTR => 'RX-' . $merchant_id . '-' . $timestamp,
            Entity::DESCRIPTION => 'NEFT payment of 4000 amount'
        );

        return $this->saveRequestAndProcess($input, 'dashboard', false, $input);
    }

    public function createBankTransferViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        $this->core->createBankTransferViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }

    public function modifyInvalidInputForPJSB(&$input)
    {
        if (starts_with($input[Entity::PAYER_IFSC], IFSC::PJSB)) {
            if (str_contains(strtolower($input[Entity::PAYER_ACCOUNT]), strtolower($input[Entity::PAYER_NAME]))) {
                $payer_account = str_replace(strtolower(addslashes($input[Entity::PAYER_NAME])), '', strtolower($input[Entity::PAYER_ACCOUNT]));

                $this->trace->info(TraceCode::BANK_TRANSFER_REQUEST_PJSB_INVALID_INPUT_MODIFICATION, [
                    'field' => Entity::PAYER_ACCOUNT,
                ]);

                $input[Entity::PAYER_ACCOUNT] = $payer_account;
            }
        }
    }

    public function createAccountForCurrencyCloud($input)
    {
        $merchantId = $this->merchant->getId();

        try {

            (new Validator)->validateInput('create_account_for_currency_cloud', $input);

            $eddStatus = (new MerchantDetailsCore)->getEDDStatus(['merchant_id' => $merchantId]);

            if ($eddStatus !== MerchantDetailsConstants::VERIFIED) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_EDD_STATUS_NOT_VERIFIED, null,
                    [
                        'edd_status' => $eddStatus,
                    ]);
            }

            if ($this->merchant->hasValidPurposeCodeForGlobalBankTransfer() === false) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PURPOSE_CODE_FOR_INTL_PAYMENTS, null,
                    [
                        'purpose_code' => $this->merchant->getPurposeCode() ?? '',
                    ]);
            }

            // List as per: https://razorpay.atlassian.net/browse/CB-1864
            // Slack: https://razorpay.slack.com/archives/C024U3B04LD/p1692271580539599?thread_ts=1692271525.102929&cid=C024U3B04LD
            //
            if (in_array($this->merchant->getCategory(), BankTransferConstants::BLACKLISTED_MCC_FOR_CURRENCY_CLOUD) === true) {
                $merchantMcc = $this->merchant->getCategory() ?? '';

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                    null,
                    null,
                    "Currently, we do not support ACH and SWIFT account for the MCC " . $merchantMcc
                );
            }

            if (empty($input['va_currency'])) {
                $input['va_currency'] = Currency::USD;
            }
            $va_currency = strtoupper($input['va_currency']);

            $enableAllCurrencies = false;
            if (isset($input['enable_all_currencies']) && boolval($input['enable_all_currencies']) === true) {
                $enableAllCurrencies = true;
            }

            //For updated flow where all currencies will be activated at once, iec_code will be mandatory, keeping both checks to maintain backward compatibility
            // IEC code required for some purpose codes
            // https://razorpay.slack.com/archives/C024U3B04LD/p1689314331594219?thread_ts=1688468005.859769&cid=C024U3B04LD
            if (
                ($enableAllCurrencies === true && empty($this->merchant->getIecCode()) === true) ||
                (in_array($this->merchant->getPurposeCode(), PurposeCodeList::IEC_REQUIRED) === true && empty($this->merchant->getIecCode()) === true)
            ) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_IEC_CODE_REQUIRED_FOR_SELECTED_PURPOSE_CODE, null,
                    [
                        'purpose_code' => $this->merchant->getPurposeCode() ?? '',
                    ]);
            }

            if ($enableAllCurrencies === false && Gateway::isVACurrencySupportedForInternationalBankTransfer($va_currency) === false) {
                throw new \Exception("Currency/Method Not Supported for International Bank Transfer");
            }

            /* Removing dependency where merchant needs to be international for activating money saver
            if ($this->merchant->isInternational() === false) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INTERNATIONAL_NOT_ENABLED_FOR_INTL_BANK_TRANSFER, null,
                    [
                        'international' => $this->merchant->isInternational(),
                    ]);
            }
            */

            if (boolval($input['accept_b2b_tnc']) === false) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TERMS_AND_CONDITIONS_NOT_CHECKED, null, [
                    'merchant_id' => $merchantId,
                    't&c' => $input['accept_b2b_tnc'],
                ]);
            }

            $mutex_key = "create_account_cc_" . $merchantId;

            $this->mutex->acquireAndRelease($mutex_key,
                function () use ($merchantId, $va_currency, $enableAllCurrencies) {
                    $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                        $merchantId, Constants\Entity::CURRENCY_CLOUD);

                    if (isset($mii)) {
                        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_VA_ALREADY_EXISTS, [
                            'merchant_id' => $merchantId,
                            'mii_id' => $mii->getId(),
                        ]);
                    } else {
                        try {
                            $requestBody = $this->createRequestBodyForAccountCreation($merchantId);
                        } catch (\Throwable $e) {
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [
                                'error_desc' => $e->getMessage(),
                                'error_code' => $e->getCode(),
                            ]);
                        }

                        try {
                            $responseBody = $this->app->mozart->sendMozartRequest('onboarding', Constants\Entity::CURRENCY_CLOUD, 'account_create', $requestBody);
                        } catch (\Exception $ex) {
                            // handle mozart service error
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CREATION_FAILED, null,
                                [
                                    'error_desc' => $ex->getMessage() ?? '',
                                    'error_code' => $ex->getCode() ?? '',
                                ]);
                        }

                        if (!isset($responseBody['data']) || !isset($responseBody['data']['account_id']) || !isset($responseBody['data']['contact_id'])) {
                            // handle CC gateway errors
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CREATION_FAILED, null, [
                                'response' => $responseBody,
                            ]);
                        }

                        $merchantInternationalIntegrations = [
                            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
                            InternationalIntegration\Entity::INTEGRATION_ENTITY => Constants\Entity::CURRENCY_CLOUD,
                            InternationalIntegration\Entity::INTEGRATION_KEY => $responseBody['data']['account_id'],
                            InternationalIntegration\Entity::REFERENCE_ID => $responseBody['data']['contact_id'],
                        ];

                        (new InternationalIntegration\Core)->createMerchantInternationalIntegration($merchantInternationalIntegrations);
                    }

                    try {
                        //assign default pricing in case we are on-boarding the merchant for the first time
                        $this->setDefaultPricing($merchantId, $va_currency, $enableAllCurrencies);
                    } catch (\Throwable $e) {

                        $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_FAILED,
                            [
                                'merchant_id' => $merchantId,
                                'va_currency' => $va_currency,
                            ]
                        );

                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UNABLE_TO_ASSIGN_PRICING_PLAN_FOR_B2B_EXPORT,
                            null,
                            [
                                'error_desc' => $e->getMessage(),
                                'error_code' => $e->getCode(),
                            ]);
                    }

                    $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                        $merchantId, Constants\Entity::CURRENCY_CLOUD);

                    (new Merchant\Service)->addFeatureFlag(
                        [
                            Feature\Constants::ENABLE_B2B_EXPORT
                        ], true
                    );

                    $this->trace->info(TraceCode::B2B_FEATURE_FLAG_ADDED, [
                        'merchant_id' => $merchantId,
                        'feature_flag' => Feature\Constants::ENABLE_B2B_EXPORT,
                    ]);

                    $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                        $merchantId, Constants\Entity::CURRENCY_CLOUD);

                    $mii = $this->updateBankAccountDetailsByVACurrency($merchantId, $mii, $va_currency, $enableAllCurrencies);

                    $this->setMerchantProductInternationalPACB();

                }, 600,
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

            $payload = [
                'mode' => $this->mode ?? Mode::LIVE,
                'action' => CrossBorderCommonUseCases::DISABLE_ON_DEMAND_SETTLEMENT,
                'merchant_id' => $merchantId
            ];
            CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60, 1000) % 601);

            try {
                $this->updateIntlBankTransferSettlementSchedule($merchantId);
            } catch (\Exception $e) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UPDATE_SCHEDULE_FAILED, null, [
                    'merchant_id' => $merchantId,
                    'error_desc' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                ]);
            }

            return (new InternationalIntegration\Core)->fetchIntlVirtualBankAccountsForGateway($merchantId, Constants\Entity::CURRENCY_CLOUD);
        } catch (\Exception $ex)
        {
            $this->trace->traceException($ex,
                Trace::ERROR,
                TraceCode::ACTIVATE_INTERNATIONAL_VIRTUAL_ACCOUNT_FAILED);

            // add metric
            $this->trace->count(BankTransferMetrics::INTERNATIONAL_B2B_CURRENCY_CLOUD_BANK_ACCOUNT_CREATION_FAILED, [
                'error_code' => $ex->getCode()
            ]);

            throw $ex;
        }
    }

    public function updateIntlBankTransferSettlementSchedule($merchantId) {

        if (Environment::isTestingEnvironment($this->app['env']) === true ||
            Environment::isEnvironmentQA($this->app['env']) === true ||
            Environment::isEnvironmentItf($this->app['env']) === true )
        {
            return;
        }

        $app = App::getFacadeRoot();
        $mode = $app['rzp.mode'];
        $scheduleId = $app['config']->get('applications.b2b_export_schedule.'.$mode);
        $data = [
            'merchant_id' => $merchantId,
            'schedules' => [
                'payment' => [
                    "international:intl_bank_transfer"=> $scheduleId
                ]
            ]
        ];
        try {
            $this->settlementService->updateSchedule($data);
        } catch (\Throwable $e) {
            $this->trace->traceException($e, null, TraceCode::B2B_SETTLEMENT_SCHEDULE_UPDATE_FAILED, [
                'merchant_id' => $merchantId,
                'error_desc' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            return;
        }
    }

    public function updateBankAccountDetailsByVACurrency($merchantId, $merchantInternationalIntegrations, $va_currency, $enableAllCurrencies)
    {
        $bankAccounts = $merchantInternationalIntegrations->getBankAccount();

        if (!isset($bankAccounts) or empty($bankAccounts)) {
            // If $bankAccounts is not set or is empty, initialize it as an empty array
            $bankAccounts = array();
        } else {
            // If $bankAccounts is set and not empty, try to decode it from JSON
            $bankAccounts = json_decode($bankAccounts, true);

            // Check if json_decode was successful
            if ($bankAccounts === null) {
                // Handle the case where decoding failed, possibly log an error or take appropriate action
                // For example, you might set $bankAccounts to an empty array to avoid issues later
                $bankAccounts = array();
            }
        }

        $currenciesToProcess = $enableAllCurrencies ? array_keys(Gateway::CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER) : [$va_currency];

        $enable_methods = [];
        foreach ($currenciesToProcess as $currency) {
            $isSwift = $currency === Gateway::SWIFT;
            $request = [
                'payment_type' => $isSwift ? self::PRIORITY : self::REGULAR,
                'account_id' => $merchantInternationalIntegrations->getIntegrationKey(),
                'contact_id' => $merchantInternationalIntegrations->getReferenceId(),
                'currency' => $isSwift ? Currency::USD : $currency,
            ];

            try {
                $bankAccount = $this->getFundingAccountDetailsByCurrency($request, $currency);
            } catch (\Exception $ex) {
               // log the error, metric and continue
                $this->trace->info(TraceCode::B2B_BANK_ACCOUNT_FETCH_ACCOUNT_BY_CURRENCY_FAILED, [
                    'merchant_id' => $merchantId,
                    'currency' => $currency,
                    'error_desc' => $ex->getMessage() ?? '',
                ]);
                $this->trace->count(BankTransferMetrics::B2B_BANK_ACCOUNT_FETCH_ACCOUNT_BY_CURRENCY_FAILED, [
                    'currency' => $currency
                ]);

                continue;
            }

            // add currency in methods
            $enable_methods[Merchant\Methods\Entity::INTL_BANK_TRANSFER] = array_merge(
                $enable_methods[Merchant\Methods\Entity::INTL_BANK_TRANSFER] ?? [],
                [Payment\Gateway::getIntlBankTransferModeByCurrency($currency) => 1]
            );

            if (!in_array($bankAccount, $bankAccounts)) {
                // Check if $bankAccount is not already in $bankAccounts
                // If not present, add $bankAccount to the end of $bankAccounts
                $bankAccounts[] = $bankAccount;
            } else {
                $this->trace->info(TraceCode::B2B_BANK_ACCOUNT_ALREADY_EXISTS_FOR_GIVEN_CURRENCY, [
                    'va_currency' => $va_currency
                ]);
            }

        }

        $mii = [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Constants\Entity::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY => $merchantInternationalIntegrations->getIntegrationKey(),
            InternationalIntegration\Entity::REFERENCE_ID => $merchantInternationalIntegrations->getReferenceId(),
            InternationalIntegration\Entity::BANK_ACCOUNT => json_encode($bankAccounts)
        ];

        if (count($enable_methods) > 0) {
            $methods = $this->merchant->methods;
            $methods->setMethods($enable_methods);
            $this->repo->saveOrFail($methods);
        }

        return (new InternationalIntegration\Core)->editMerchantInternationalIntegrations($mii);
    }

    protected function getFundingAccountDetailsByCurrency($request, $va_currency)
    {
        try {
            $response = $this->app->mozart->sendMozartRequest('onboarding', Constants\Entity::CURRENCY_CLOUD, 'get_funding_account', $request, 'v1', true);
        } catch (\Exception $ex) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INTL_BANK_TRANSFER_ACCOUNT_DOES_NOT_EXIST, null,
                [
                    'error_data' => $ex->getData() ?? [],
                ]);
        }

        $funding_accounts = $response['data']['funding_accounts'];

        $virtualAccountDetails = [
            'account_number' => $funding_accounts[0]['account_number'],
            'va_currency' => $va_currency,
            'beneficiary_name' => $funding_accounts[0]['account_holder_name'],
            'bank_name' => $funding_accounts[0]['bank_name'],
            'bank_address' => $funding_accounts[0]['bank_address']
        ];

        $routing_codes = [];

        foreach ($funding_accounts as $funding_account) {
            // Storing routing_code_type as routing_type in our systems as designs suggests
            $routing_code['routing_type'] = $funding_account['routing_code_type'];
            $routing_code['routing_code'] = $funding_account['routing_code'];
            array_push($routing_codes, $routing_code);
        }

        $virtualAccountDetails['routing_details'] = $routing_codes;

        return $virtualAccountDetails;
    }

    protected function createRequestBodyForAccountCreation($merchantId)
    {
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $name = explode(' ', trim($merchantDetail->getPromoterPanName()), 2);

        $address = [
            'street' => $merchantDetail->getBusinessRegisteredAddress(),
            'city' => $merchantDetail->getBusinessRegisteredCity(),
            'state' => $merchantDetail->getBusinessRegisteredState(),
            'country' => $merchantDetail->getBusinessRegisteredCountry() ?? "IN",
            'pin' => $merchantDetail->getBusinessRegisteredPin(),
        ];

        $contact = [
            'first_name' => $name[0],
            'last_name' => isset($name[1]) ? $name[1] : "LNU",
            'email' => $merchantDetail->getContactEmail(),
            'phone' => $merchantDetail->getContactMobile(),
            'login_id' => $merchantId . "_razorpay"
        ];

        $requestBody = [
            'account_name' => trim($this->merchant->getName()),
            'address' => $address,
            'contact' => $contact,
        ];

        return $requestBody;
    }

    public function notificationsFromCurrencyCloud($input, $header): array
    {
        $this->trace->info(TraceCode::CURRENCY_CLOUD_NOTIFICATION_REQUEST, [
            'input' => $input,
            'header' => $header,
        ]);


        if ($this->app['env'] === Environment::BETA or
            $this->app['env'] === Environment::AUTOMATION or
            $this->app['env'] === Environment::TESTING
        ) {
            DefaultConnection::set(Mode::TEST);
            $this->app['rzp.mode'] = Mode::TEST;
        } else {
            $this->app['rzp.mode'] = Mode::LIVE;
        }

        switch ($header) {
            case self::CASH_MANAGER_TRANSACTION_NOTIFICATION:
                $this->fundsArrivedFlowFromCurrencyCloud($input);
                break;

            case self::PAYMENT_RELEASED_NOTIFICATION:
                $this->paymentReleasedFlowFromCurrencyCloud($input);
                break;

            case self::TRANSFER_COMPLETED_NOTIFICATION:
                $this->transferCompletedFlowFromCurrencyCloud($input);
                break;

            default:
                $this->trace->info(TraceCode::CURRENCY_CLOUD_INVALID_NOTIFICATION, [
                    'header' => $header,
                    'input' => $input,
                ]);
                break;
        }

        return [];
    }

    // Collect customer billing address from merchant dashboard
    // https://razorpay.slack.com/archives/C024U3B04LD/p1682496775025409?thread_ts=1681996740.555379&cid=C024U3B04LD
    public function createAddressEntityForB2B($input = [], $paymentId = '')
    {
        $this->trace->info(TraceCode::ADDRESS_CREATE_REQUEST, $input);

        [$payment, $addresses] = $this->getAddressEntityForB2B($paymentId);

        if ((in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER]) === false) and
            ($addresses->isNotEmpty() === true)) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $addresses,
                [
                    'error_desc' => 'Address already saved',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);
        }

        if ((empty($input) === false) and
            (isset($input['country']) === true) and
            ((strtolower($input['country']) === 'in') or (strtolower($input['country']) === 'ind') or (strtolower($input['country']) === 'india'))) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $input,
                [
                    'error_desc' => 'Invalid country',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);
        }
        return $this->saveBillingAddress($payment, $input);
    }

    private function saveBillingAddress($payment, $input) {

        $formattedAddress = [
            'type' => Address\Type::BILLING_ADDRESS,
            'name' => $input['name'],
            'zipcode' => $input['zipcode'],
            'line1' => $input['line1'],
            'city' => $input['city'],
            'country' => $input['country'],
            'state' => $input['state'] ?? '',
        ];

        return (new Address\Core)->create($payment, $payment->getEntity(), $formattedAddress);
    }


    public function getAddressEntityForB2B($paymentId = '')
    {
        /* Removing dependency where merchant needs to be international for activating money saver
        if ($this->merchant->isInternational() === false) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'error_desc' => 'Merchant not allowed',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);
        }
        */

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        if (($payment->isAuthorized() === false) or
            ($payment->isB2BExportCurrencyCloudPayment() === false)) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'error_desc' => 'Invalid payment status or method',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);
        }

        $addresses = $this->repo->address->fetchAddressesForEntity($payment,
            ['type' => Address\Type::BILLING_ADDRESS]);

        return [$payment, $addresses];
    }

    public function captureCronForB2BPayments($input)
    {
        if ($this->app['env'] != Environment::TESTING) {
            $this->app['rzp.mode'] = Mode::LIVE;
        }

        $limit = isset($input['limit']) ? $input['limit'] : 10;

        $payments = $this->repo->payment->getPaymentsWithReferenceId(Constants\Entity::CURRENCY_CLOUD, Payment\Status::AUTHORIZED, $limit);

        foreach ($payments as $payment) {
            try {
                $merchantId = $payment->getMerchantId();

                $merchant = $this->repo->merchant->find($merchantId);

                if ($payment->isDirectSettlement() === true) {
                    $this->trace->info(TraceCode::B2B_TRANSFER_NOT_APPLICABLE_FOR_THIS_PAYMENT, [
                        'payment_id' => $payment->getId(),
                        'is_direct_settlement' => $payment->isDirectSettlement(),
                    ]);

                    continue;
                }

                // merchant should add customer billing address before payments can be captured
                $addresses = $this->repo->address->fetchAddressesForEntity($payment,
                    ['type' => Address\Type::BILLING_ADDRESS]);

                // Transfer_id which we get from CC is stored in Reference16 attribute
                if ($payment->getReference16() != null or
                    !$merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B) or
                    ($addresses->isEmpty() === true)) {
                    $this->trace->info(TraceCode::B2B_TRANSFER_COMPLETION_PENDING, [
                        'payment_id' => $payment->getId(),
                        'payment_transfer_id' => $payment->getReference16(),
                        'settlement_flow_by_risk' => $merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B),
                        'address_empty' => ($addresses->isEmpty() ? 'yes' : 'no'),
                    ]);

                    continue;
                }

                $merchantInternationalIntegration = (new \RZP\Models\Merchant\InternationalIntegration\Repository)->getByMerchantIdAndIntegrationEntity($merchantId, Constants\Entity::CURRENCY_CLOUD);

                if ($merchantInternationalIntegration->isInternationalVirtualAccountDisabled() === true) {
                    continue;
                }

                $parentRZPAccountId = $this->app['config']->get('gateway.currency_cloud.rzp_parent_account_id');

                $request = [
                    'currency' => $payment->getCurrency(),
                    'amount' => strval($payment->getAmount() / 100),
                    'reason' => BankTransferConstants::HOUSE_ACCOUNT_TRANSFER_REASON . "; " . $payment->getId(),
                    'destination_account_id' => $parentRZPAccountId,
                    'payment_id' => $payment->getId(),
                    'source_account_id' => $merchantInternationalIntegration->getIntegrationKey(),
                ];

                $response = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'create_transfer', $request);

                $payment = $this->repo->payment->findOrFail($payment->getId());

                $payment->setReference16($response['data']['id']);

                $this->repo->payment->saveOrFail($payment);

                $this->trace->info(TraceCode::B2B_SETTLEMENT_TO_RZP_PARENT_ACCOUNT, [
                    'payment_id' => $payment->getId(),
                    'payment_transfer_id' => $response['data']['id'],
                    'amount' => $response['data']['amount'],
                    'currency' => $response['data']['currency'],
                ]);
            } catch (\Exception $ex) {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::B2B_SETTLEMENT_TO_RZP_PARENT_ACCOUNT_FAILED,
                    [
                        'message' => 'Sub Account Transfer to House Failed',
                        'payment_id' => $payment->getId(),
                    ]
                );
            }
        }
    }

    /**
     * @throws \Exception in case pricing plan is not set
     */
    private function setDefaultPricing($merchantId, $va_currency, $enableAllCurrencies): void
    {

        $defaultPricing = $this->fetchDefaultPricing($merchantId, $va_currency, $enableAllCurrencies);

        $pricingPlan = (new PricingService)->postAddBulkPricingRules($defaultPricing);

        foreach ($pricingPlan["items"] as $plan) {
            if ($plan["success"] === false) {
                if ($plan["error"]["code"] === ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED
                    || $plan["error"]["code"] === ErrorCode::BAD_REQUEST_SAME_PRICING_RULE_ALREADY_EXISTS) {
                    // in case the pricing plan is already present don't throw the exception
                    $this->trace->info(TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_SUCCESSFUL, [
                        'merchant_id' => $merchantId,
                        'description' => $plan["error"]["description"],
                        'code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED
                    ]);

                    return;
                }

                throw new Exception\ServerErrorException('Default Pricing plan creation error',
                    ErrorCode:: SERVER_ERROR_UNABLE_TO_ASSIGN_PRICING_PLAN_FOR_B2B_EXPORT, [
                        'error_desc' => $plan["error"]["description"],
                        'error_code' => $plan["error"]["code"],
                    ]);
            } else {
                $this->trace->info(TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_SUCCESSFUL, [
                    'merchant_id' => $merchantId,
                    'pricing_plan' => $plan,
                    'va_currency' => $va_currency,
                    'enable_all_currencies' => $enableAllCurrencies
                ]);
            }
        }
    }

    private function fetchDefaultPricing($merchantId, $va_currency, $enableAllCurrencies): array
    {
        $mode = match ($va_currency) {
            Currency::USD => IntlBankTransfer::ACH,
            Currency::EUR => IntlBankTransfer::SEPA,
            Currency::GBP => IntlBankTransfer::FPS,
            GATEWAY::SWIFT => IntlBankTransfer::SWIFT,
        };
        $defaultPricing = [
            PricingEntity::PRODUCT => Product::PRIMARY,
            PricingEntity::FEATURE => \RZP\Models\Pricing\Feature::PAYMENT,
            PricingEntity::PAYMENT_METHOD => Payment\Method::INTL_BANK_TRANSFER,
            PricingEntity::PAYMENT_METHOD_SUBTYPE => "",
            PricingEntity::INTERNATIONAL => "0",
            PricingEntity::MERCHANT_ID => $merchantId,
        ];

        $defaultPricingArray = [];

        // During Swift onboarding, we also have to add 2 default pricing plans(sepa, bacs) along with swift
        if ($mode === IntlBankTransfer::SWIFT || $enableAllCurrencies === true) {
            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::FPS;
            $defaultPricingArray = array(array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::FPS)));

            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::SEPA;
            array_push($defaultPricingArray, array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::SEPA)));

            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::ACH;
            array_push($defaultPricingArray, array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::ACH)));

            $defaultPricing = array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::SWIFT));
            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::SWIFT;
        } else if ($mode === IntlBankTransfer::ACH) {
            $defaultPricing = array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::ACH));
            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::ACH;
        } else if ($mode === IntlBankTransfer::SEPA) {
            $defaultPricing = array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::SEPA));
            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::SEPA;
        } else if ($mode === IntlBankTransfer::FPS) {
            $defaultPricing = array_merge($defaultPricing, $this->fetchDefaultPricingIntlBankTransfer(IntlBankTransfer::FPS));
            $defaultPricing["idempotency_key"] = $merchantId . "_" . IntlBankTransfer::FPS;
        }
        array_push($defaultPricingArray, $defaultPricing);

        return $defaultPricingArray;
    }

    private function fetchDefaultPricingIntlBankTransfer($paymentNetwork): array
    {

        $defaultPricing = [];
        $staticPricing = [
            PricingEntity::PERCENT_RATE => 200,
            PricingEntity::FIXED_RATE => 0,
            PricingEntity::PAYMENT_NETWORK => $paymentNetwork
        ];

        switch ($paymentNetwork) {
            case IntlBankTransfer::ACH:
                $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_ACH);
                break;
            case IntlBankTransfer::SWIFT:
                $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_SWIFT);
                break;
            case IntlBankTransfer::SEPA:
                $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_SEPA);
                break;
            case IntlBankTransfer::FPS:
                $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_FPS);
                break;
        }

        if ($defaultPricing == null) {
            return $staticPricing;
        }

        return [
            PricingEntity::PERCENT_RATE => $defaultPricing[PricingEntity::PERCENT_RATE] ?? $staticPricing[PricingEntity::PERCENT_RATE],
            PricingEntity::FIXED_RATE => $defaultPricing[PricingEntity::FIXED_RATE] ?? $staticPricing[PricingEntity::FIXED_RATE],
            PricingEntity::PAYMENT_NETWORK => $staticPricing[PricingEntity::PAYMENT_NETWORK]
        ];
    }

    public function settleFundsFromCurrencyCloudCron()
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        $payload = [
            'action' => CrossBorderCommonUseCases::INTL_BANK_TRANSFER_SWIFT_SETTLEMENT,
            'mode' => $this->mode,
        ];

        foreach (Payment\Gateway::INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES as $currency) {

            $payload['body'] = [
                'settlement_currency' => $currency,
                'gateway' => Constants\Entity::CURRENCY_CLOUD,
            ];

            try {
                CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60, 1000) % 601);

                $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCHED, [
                    'payload' => $payload,
                ]);
            } catch (\Exception $ex) {
                $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCH_FAILED, [
                    'payload' => $payload,
                ]);
            }
        }

    }

    public function settlementFromCurrencyCloud($payload)
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        $gateway = $payload['gateway'];
        $currency = $payload['settlement_currency'];

        if ($gateway === Constants\Entity::CURRENCY_CLOUD && in_array($currency, Payment\Gateway::INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES)) {
            try {

                $getBalanceRequest = [
                    "currency" => $currency,
                ];

                $getBalanceResponse = $this->callCurrencyCloudGetBalance($getBalanceRequest);

                if (!isset($getBalanceResponse['data']['amount']) || $getBalanceResponse['data']['amount'] < 1) {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE, null, [
                        'gateway' => Payment\Gateway::CURRENCY_CLOUD,
                        'data' => $getBalanceResponse['data'],
                        'action' => "get_balance",
                    ]);
                }
                $settlementCurrency = Payment\Gateway::getSettlementCurrencyByGateway(Payment\Gateway::CURRENCY_CLOUD, $currency);

                $createPaymentRequest = [
                    'currency' => $settlementCurrency,
                    'amount' => $getBalanceResponse['data']['amount'],
                    'reason' => 'For Settling Money from RZP House account to Merchants',
                    'reference' => $getBalanceResponse['data']['id'],
                    'beneficiary_id' => $this->getBeneficiaryIdForCurrency($settlementCurrency),
                    'unique_request_id' => UniqueIdEntity::generateUniqueId()
                ];

                if ($settlementCurrency !== $currency) {
                    $createConversionRequest = [
                        'buy_currency' => $settlementCurrency,
                        'sell_currency' => $currency,
                        'fixed_side' => 'sell',
                        'amount' => $getBalanceResponse['data']['amount'],
                        'term_agreement' => "true",
                    ];

                    $createConversionResponse = $this->app->mozart->sendMozartRequest('payments', $gateway, 'create_conversion', $createConversionRequest);

                    if (!isset($createConversionResponse['data']['client_buy_amount']) || $createConversionResponse['data']['client_buy_amount'] < 1) {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE, null, [
                            'gateway' => Payment\Gateway::CURRENCY_CLOUD,
                            'data' => $createConversionResponse['data'],
                            'action' => "create_conversion",
                        ]);
                    }

                    $createPaymentRequest['conversion_id'] = $createConversionResponse['data']['id'];
                    $createPaymentRequest['amount'] = $createConversionResponse['data']['client_buy_amount'];

                }

                $this->app->mozart->sendMozartRequest('payments', $gateway, 'payment_create', $createPaymentRequest, 'v2');
            } catch (\Exception $ex) {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::B2B_PAYMENTS_SETTLED_WITH_BANKING_PARTNER_FAILED,
                    [
                        'message' => 'House Account To Nostro Account Payment Request Failed',
                        'currency' => $currency,
                        'settlement_currency' => $settlementCurrency,
                    ]
                );

                if ($ex->getCode() !== ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE) {
                    throw $ex;
                }
            }
        }
    }

    protected function fundsArrivedFlowFromCurrencyCloud($input)
    {
        $mii = (new \RZP\Models\Merchant\InternationalIntegration\Repository)->getByIntegrationEntityAndKey(Constants\Entity::CURRENCY_CLOUD, $input['account_id']);

        if (isset($mii) == false) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED, null, [
                'txn_id' => $input['id'],
                'related_entity_id' => $input['related_entity_id'],
                'account_id' => $input['account_id']
            ]);
        }

        $merchantId = $mii->getMerchantId();

        $request = [
            'txn_id' => $input['related_entity_id'],
            'contact_id' => $mii->getReferenceId(),
        ];

        $response = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'get_sender_detail', $request);

        $payments = $this->core->createAndAuthorizePaymentForIntlBankTransfer($response['data'], $merchantId, $input);

        return [
            'success' => 'true',
            'payment_ids' => array_pluck($payments, 'id'),
        ];
    }

    protected function transferCompletedFlowFromCurrencyCloud($input)
    {
        $reason = $input['reason'];

        if (isset($reason) === false || empty($reason) === true) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED, null, [
                'reason' => $input['reason'],
            ]);
        }
        $reasonConstants = explode(";", $reason)[0];

        if ($reasonConstants === BankTransferConstants::COMMISSION_TRANSFER_REASON) {
            return [];
        }

        $payment_id = trim(explode(";", $reason)[1]);

        $payment = $this->repo->payment->findOrFail($payment_id);

        // merchants should add customer billing addresses before payments can be captured
        $addresses = $this->repo->address->fetchAddressesForEntity($payment,
            ['type' => Address\Type::BILLING_ADDRESS]);

        if ($addresses->isEmpty() === true) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'error_desc' => 'Address not present',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);
        }

        $payment->setGatewayCaptured(true);

        $this->repo->payment->saveOrFail($payment);

        $payment = $this->core->capturePaymentForB2B($payment, $input);
    }

    protected function paymentReleasedFlowFromCurrencyCloud($input)
    {
        $this->trace->info(TraceCode::B2B_PAYMENTS_SETTLED_WITH_BANKING_PARTNER, $input);
    }

    protected function getBeneficiaryIdForCurrency($currency)
    {
        $configValue = strtolower($currency) . '_beneficiary_id';
        $beneficiaryId = $this->app['config']->get('gateway.currency_cloud.' . $configValue);

        return $beneficiaryId;
    }

    public function getBalanceForMerchantVA($input, $va_currency)
    {
        $merchantId = $this->merchant->getId();

        if (($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === true) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT))) {
            $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $merchantId, Constants\Entity::CURRENCY_CLOUD);

            $request = [
                'on_behalf_of' => $mii->getReferenceId(),
                'currency' => $va_currency
            ];

            $response = $this->callCurrencyCloudGetBalance($request);

            $getBalanceResponse = [
                'amount' => $response['data']['amount'],
                'currency' => $response['data']['currency'],
                'account_id' => $response['data']['account_id']
            ];

            return $getBalanceResponse;
        } else {
            $this->trace->info(TraceCode::FETCH_BALANCE_ON_VA_FAILED, [
                'currency' => $va_currency,
                'merchant_id' => $merchantId
            ]);
            throw new \Exception(TraceCode::FETCH_BALANCE_ON_VA_FAILED);
        }
    }

    protected function callCurrencyCloudGetBalance($request)
    {
        $response = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'get_balance', $request);

        return $response;
    }

    protected function getCommissionFeeForPayouts()
    {
        $commissionFee = ConfigKey::get(ConfigKey::COMMISSION_FEE_FOR_CC_MERCHANT_PAYOUT);
        if (isset($commissionFee) === false or empty($commissionFee) === true) {
            $commissionFee = BankTransferConstants::COMMISSION_FEE_FOR_CURRENCY_CLOUD_PAYOUT;
        }

        return $commissionFee;
    }

    public function createBeneficiaryForMerchantInCC($input)
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if (($merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled, Beneficiary creation not allowed"
            );
        }

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId, Constants\Entity::CURRENCY_CLOUD);

        try {

            $createBeneficiaryRequest = $this->createRequestBodyForBeneficiaryCreation($input, $mii);

            $createBeneficiaryResponse = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'create_beneficiary', $createBeneficiaryRequest, 'v2');

            $this->trace->info(TraceCode::BENEFICIARY_CREATION_SUCCESSFUL, [
                'status' => $createBeneficiaryResponse['data']['status'],
                'beneficiary_id' => $createBeneficiaryResponse['data']['id'],
            ]);

        } catch (\Exception $ex) {
            $this->trace->info(TraceCode::BENEFICIARY_CREATION_FAILED, [
                'error_message' => $ex->getMessage()
            ]);
            throw $ex;
        }
        $notes = $mii->getNotes();
        $notes = isset($notes) === true ? $notes->toArray() : [];
        $notes['beneficiary_id'] = $createBeneficiaryResponse['data']['id'];
        $mii->setNotes($notes);
        $this->repo->merchant_international_integrations->saveOrFail($mii);

        return $createBeneficiaryResponse['data'];
    }

    public function getBeneficiaryDetailsForMerchantPayout($input)
    {
        if (isset($input['merchant_id'])) {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
        } else {
            $merchant = $this->merchant;
        }

        $merchantId = $merchant->getId();

        if (($merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId, Constants\Entity::CURRENCY_CLOUD);

        if (isset($mii)) {
            $notes = $mii->getNotes();
            if (isset($notes['beneficiary_id'])) {
                $request = [
                    'on_behalf_of' => $mii->getReferenceId(),
                    'beneficiary_id' => $notes['beneficiary_id'],
                ];
                $response = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'get_beneficiary', $request, 'v2');

                $response['data']['commission_fee'] = $this->getCommissionFeeForPayouts();

                return $response['data'];

            } else {
                return ["status" => "No beneficiary is present"];
            }
        } else {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Beneficiary creation blocked, Please create VA for the merchant"
            );
        }
    }

    public function merchantPayoutFromVAToBeneficiary($input)
    {
        if (($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $merchantId = $this->merchant->getId();

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId, Constants\Entity::CURRENCY_CLOUD);

        $request = [
            'on_behalf_of' => $mii->getReferenceId(),
            'currency' => $input['currency']
        ];

        $response = $this->callCurrencyCloudGetBalance($request);

        $balanceAmount = ((float)$response['data']['amount']);
        $commissionFee = $this->getCommissionFeeForPayouts();
        $amountToBeDeducted = $input['amount'] + $commissionFee;

        if (($input['amount'] < BankTransferConstants::MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT)) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT,
                null,
                sprintf("Minimum payout amount is %s", BankTransferConstants::MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT)
            );
        }

        if ($amountToBeDeducted > $balanceAmount) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
                null,
                sprintf("Payout is not possible with current balance %s", $balanceAmount)
            );
        }

        $this->core->makePayoutAndTransferCommission($input, $mii, $merchantId, $commissionFee);
    }

    protected function createRequestBodyForBeneficiaryCreation($input, $mii)
    {
        $request = [
            'name' => $input['name'],
            'bank_account_holder_name' => $input['bank_account_holder_name'],
            'bank_country' => $input['bank_country'],
            'currency' => $input['currency'],
            'beneficiary_address' => $input['beneficiary_address'],
            'beneficiary_country' => $input['beneficiary_country'],
            'account_number' => $input['account_number'],
            'bank_address' => $input['bank_address'],
            'bank_name' => $input['bank_name'],
            'beneficiary_entity_type' => $input['beneficiary_entity_type'],
            'beneficiary_company_name' => $input['beneficiary_company_name'],
            'beneficiary_city' => $input['beneficiary_city'],
            'bic_swift' => $input['bic_swift'],
            'on_behalf_of' => $mii->getReferenceId(),
            'beneficiary_postcode' => $input['beneficiary_postcode'],
            'beneficiary_state_or_province' => $input['beneficiary_state_or_province']
        ];

        return $request;
    }

    public function fetchAllPayoutsForIntlVA($input)
    {
        if (($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $merchantId = $this->merchant->getId();

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId, Constants\Entity::CURRENCY_CLOUD);

        if (!isset($input['skip'])) {
            $input['skip'] = 0;
        }

        $fetchPayoutsRequest = [
            'on_behalf_of' => $mii->getReferenceId(),
            'page' => strval((int)($input['skip'] / BankTransferConstants::PAYOUT_ENTRIES_PER_PAGE) + 1)
        ];

        $fetchPayoutsResponse = $this->app->mozart->sendMozartRequest('payments', Constants\Entity::CURRENCY_CLOUD, 'get_payments', $fetchPayoutsRequest, 'v2');

        $responseList['payouts'] = [];

        foreach ($fetchPayoutsResponse['data']['body']['payments'] as $payout) {
            $finalPayoutResponse = [
                'payout_id' => $payout['id'],
                'status' => BankTransferConstants::CURRENCY_CLOUD_PAYOUT_MAPPING_WITH_OUR_STATUS[$payout['status']],
                'amount' => $payout['amount'],
                'currency' => $payout['currency'],
                'created_date' => $payout['payment_date'],
                'beneficiary_id' => $payout['beneficiary_id'],
                'reason' => $payout['reason'],
            ];
            array_push($responseList['payouts'], $finalPayoutResponse);
        }

        $responseList['is_last_page'] = 0;

        if ($fetchPayoutsResponse['data']['body']['pagination']['next_page'] === -1) {
            $responseList['is_last_page'] = 1;
        }

        return $responseList;
    }

    public function sendNotificationForB2B($input)
    {
        $this->increaseAllowedSystemLimits();

        // send emails
        $emailReports = $this->notifyViaEmail($input);

        $consolidateReports = [
            'email_reports' => $emailReports,
        ];

        return $consolidateReports;
    }

    protected function notifyViaEmail($input = [])
    {
        if ($this->app['env'] != Environment::TESTING) {
            $this->app['rzp.mode'] = Mode::LIVE; // trigger email in test mode as well
        }

        $emailReports = [
            'upload_invoice' => $this->notifyUploadInvoice($input),
        ];

        return $emailReports;

    }

    protected function notifyUploadInvoice($input = [])
    {
        $paymentIds = $input['payment_ids'] ?? [];

        $includeMerchantList = $input['include_merchants'] ?? [];

        $excludeMerchantList = $input['exclude_merchants'] ?? [];

        $limit = $input['limit'] ?? 0;

        $offset = $input['offset'] ?? 0;

        $payments = $this->repo->payment->getPaymentsWithoutReferenceId(Constants\Entity::CURRENCY_CLOUD,
            Payment\Status::AUTHORIZED,
            Payment\Method::INTL_BANK_TRANSFER,
            $paymentIds,
            $includeMerchantList,
            $excludeMerchantList,
            $limit,
            $offset);

        $successCount = 0;
        $failureCount = 0;
        $failureTrace = [];

        $event = Payment\Event::B2B_UPLOAD_INVOICE;

        foreach ($payments as $payment) {
            if ($payment->getBaseAmount() < 85000) {
                continue;
            }
            try {
                $this->triggerEmail($payment, $event);

                $successCount++;
            } catch (\Exception $e) {
                $traceData = [
                    'input' => $input ?? "",
                    'payment_id' => $payment->getId() ?? "",
                    'merchant_id' => $payment->getMerchantId() ?? "",
                    'error_code' => $e->getCode() ?? "",
                    'error_message' => $e->getMessage() ?? "",
                ];

                array_push($failureTrace, $traceData);

                $failureCount++;
            }
        }

        $report = [
            'total_payments' => count($payments),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failure_trace' => $failureTrace,
        ];

        $this->trace->info(TraceCode::B2B_NOTIFICATION_REPORT, $report);

        return $report;
    }

    protected function triggerEmail($payment, $event)
    {
        // notify does not throw ex. Will have to check from logs
        (new Notify($payment))->trigger($event);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMaxExecTime(7200);
    }

    public function cbInvoiceWorkflowCallback($input)
    {
        try {
            (new Validator)->validateInput("crossBorderInvoiceWorkflowCallback", $input);
        } catch (\Throwable $e) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILED, null, [
                'error_description' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
        }

        $paymentId = $input['payment_id'];
        $merchantId = $input['merchant_id'];
        $workflowStatus = $input['workflow_status'];
        $priority = $input['priority'];

        $merchant = $this->repo->merchant->findOrFail($merchantId);
        if (!isset($merchant)) {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
        }
        $this->trace->info(TraceCode::CROSS_BORDER_INVOICE_WORKFLOW_CALLBACK_REQUEST, [
            "payment_id" => $paymentId,
            "merchant_id" => $merchantId,
            "workflow_status" => $workflowStatus,
            "priority" => $priority,
        ]);

        $payment = $this->repo->payment->findByIdAndMerchant($paymentId, $merchant);
        if (isset($payment) === true) {
            if (!$payment->isB2BExportCurrencyCloudPayment()) {
                throw new Exception\BadRequestException(
                    Error\ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID);
            }
            if ($workflowStatus == WorkflowBuilder\Constants::APPROVED) {
                if (!$merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B)) {
                    $featureParams = [
                        Feature\Entity::ENTITY_ID => $merchant->getId(),
                        Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                        Feature\Entity::NAMES => [Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B],
                        Feature\Entity::SHOULD_SYNC => true
                    ];
                    (new Feature\Service)->addFeatures($featureParams);
                    $this->trace->info(TraceCode::B2B_SETTLEMENT_ENABLE_FEATURE_FLAG_ADDED, [
                        "payment_id" => $paymentId,
                        "merchant_id" => $merchantId,
                    ]);
                }
            } else {
                try {
                    CrossBorderCommonUseCases::sendSlackNotification(
                        $paymentId, $merchantId, $priority, "", WorkflowBuilder\Constants::REJECTED);
                } catch (\Throwable $e) {
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::CROSS_BORDER_INVOICE_WORKFLOW_NOTIFICATION_FAILED,
                        [
                            'payload' => $this->payload,
                        ]
                    );
                }
            }
        }
        return ["success" => true];
    }

    public function captureCronForPACBBankTransferPayments($input) {

        if ($this->app['env'] != Environment::TESTING) {
            $this->app['rzp.mode'] = Mode::LIVE;
        }
        $payload = [
            'action' => CrossBorderCommonUseCases::CAPTURE_PACB_BANK_TRANSFER_PAYMENT,
            'mode' => $this->app['rzp.mode'],
            'body' => $input
        ];

        CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60, 1000) % 601);
    }

    public function capturePACBBankTransferPayments($input) {
        if ($this->app['env'] != Environment::TESTING) {
            $this->app['rzp.mode'] = Mode::LIVE;
        }

        $payments = $this->repo->payment->getIntlBankTransferPayments( $input, Payment\Status::AUTHORIZED, Gateway::PING_PONG);
        $encryptionKey = $this->app['config']['app']['cross_border_handle']['aes_encryption_key'];
        $orderIds = (new \RZP\Models\Order\OrderMeta\Core())->getOrderIdForPayment($payments);

        $orderMetas = $this->repo->order_meta->fetchByOrderIdsAndTypeFromTiDB($orderIds, \RZP\Models\Order\OrderMeta\Type::CART_INFO);
        $orderIdToOrderMetaMap = (new \RZP\Models\Order\OrderMeta\Core())->getOrderIdToOrderMetaMap($orderMetas);

        foreach ($payments as $payment) {
            try {
                $orderMeta =$orderIdToOrderMetaMap[$payment->order->getId()] ?? null;

                if (!$orderMeta) {
                    $this->trace->info(TraceCode::PACB_BANK_TRANSFER_EMPTY_ORDER_META, [
                        'payment_id' => $payment->getId(),
                        'order_id' => $payment->order->getId(),
                    ]);
                    continue;
                }

                $decryptedCartInfo = (new \RZP\Models\Order\OrderMeta\Core())->decryptCartInfo($orderMeta['value'], $encryptionKey);

                $formattedAddress = [
                    'name' => $decryptedCartInfo["customer_details"]["name"],
                    'zipcode' => $decryptedCartInfo["customer_details"]["billing_address"]["zipcode"],
                    'line1' => $decryptedCartInfo["customer_details"]["billing_address"]["line1"],
                    'line2' => $decryptedCartInfo["customer_details"]["billing_address"]["line2"],
                    'city' => $decryptedCartInfo["customer_details"]["billing_address"]["city"],
                    'country' => $decryptedCartInfo["customer_details"]["billing_address"]["country"] ,
                    'state' => $decryptedCartInfo["customer_details"]["billing_address"]["state"],
                ];

                $this->saveBillingAddress($payment, $formattedAddress);
                $payment = $this->repo->payment->findOrFail($payment->getId());
                $payment->setGatewayCaptured(true);
                $this->repo->payment->saveOrFail($payment);
                $this->core->capturePaymentForB2B($payment);

            } catch (\Exception $ex) {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::PACB_BANK_TRANSFER_PAYMENT_CAPTURE_FAILED,
                    [
                        'payment_id' => $payment->getId(),
                        'status'=> $payment->getStatus(),
                        'message' => $ex->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * @throws BadRequestException
     */
    public function createInternationalVirtualAccountInternally($merchantID)
    {
        $this->merchant = $this->repo->merchant->findOrFail($merchantID);

        $this->app['basicauth']->setMerchant($this->merchant);

        $this->app['rzp.mode'] = Mode::LIVE;

        $input = [
            'accept_b2b_tnc' => true,
            'enable_all_currencies' => true
        ];

        $this->createAccountForCurrencyCloud($input);
    }


    /**
     * @throws BadRequestException
     * @throws LogicException
     */
    public function toggleInternationalVirtualAccountForMerchant($input): array
    {
        try {
            (new Validator)->validateInput('toggle_virtual_account_for_currency_cloud', $input);

            $merchantId = $this->merchant->getId();

            $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $merchantId, Constants\Entity::CURRENCY_CLOUD);

            if (!isset($mii)) {
                $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_VA_DOES_NOT_EXIST, [
                    'merchant_id' => $merchantId,
                ]);

                return ['success' => true];
            }

            $inputAction = $input['action'];

            $reason = $inputAction === 'activate' ? "Merchant enabled virtual account" : "Merchant disabled virtual account";

            $action = $inputAction === 'activate' ? "activated" : "deactivated";

            if ($action === "deactivated" && $mii->isInternationalVirtualAccountDisabled() === true) {
                return ['success' => true];
            }

            $notes = $mii->getNotes();
            $notes = isset($notes) === true ? $notes->toArray() : [];
            $notes[self::STATUS] = $action;
            $notes[self::REASON] = $reason;
            $mii->setNotes($notes);
            $this->repo->merchant_international_integrations->saveOrFail($mii);
            return ['success' => true];
        } catch (\Exception $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::MERCHANT_INTERNATIONAL_VA_TOGGLE_FAILED,
                [
                    'message' => $ex->getMessage()
                ]
            );
            throw $ex;
        }
    }

    /**
     * @throws LogicException
     */
    private function setMerchantProductInternationalPACB(): void
    {
        $enabledStatus = '1';

        $productInternational = $this->merchant->getProductInternational();

        $productPacbPosition = ProductInternationalMapper::PRODUCT_POSITION['products_pa_cb'];

        $currentStatus = $productInternational[$productPacbPosition];

        if ($currentStatus !== $enabledStatus)
        {
            $productInternational[$productPacbPosition] = $enabledStatus;

            $this->merchant->setProductInternational((string) $productInternational);
        }

        $this->repo->merchant->saveOrFail($this->merchant);
    }

    public function isAsyncInternationalVirtualAccountActivationEnabled(string $merchantId): bool
    {
        $default_variant = 'variant_on';

        try {
            $properties = [
                'id' => $merchantId,
                'experiment_id' => $this->app['config']->get('app.enable_intl_va_async'),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === $default_variant;
        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id' => $merchantId,
                'experiment_id' => $this->app['config']->get('app.enable_intl_va_async') ?? null
            ]);

            return false;
        }
    }

    public function isMoneySaverMerchant(string $merchantId): bool
    {
        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId, Constants\Entity::CURRENCY_CLOUD);

        if (!isset($mii)) {
            return false;
        }

        if ($mii->isInternationalVirtualAccountDisabled() === true) {
            return false;
        }

        return true;
    }

    public function encryptIdfcBankCallbackData(array $inputData, $iv)
    {
        $data = json_encode($inputData);

        if($data === false) {
            return ;
        }

        // Decoding the Hexadecimal key to bytes
        $skeySpec = hex2bin(env('IDFC_AES_ENCRYPTION_KEY'));

        $params = $this->getAesEncrytionHeaders($skeySpec, $iv);

        $encryptor = new AESEncryption($params);

        $encryptedData = $encryptor->encrypt($data);

        // Encrypting the payload
        // Creating a final byte array, with iv length + length of encryptedBytes
        $finalarray = $iv . $encryptedData;

        // Encoding the combined IV and encrypted payload in Base64
        $finalEncryptedPayload = base64_encode($finalarray);

        return $finalEncryptedPayload;
    }

    public function decryptIdfcBankCallbackData(string $encrypted){
        $skeySpec = hex2bin(env('IDFC_AES_ENCRYPTION_KEY'));

        // Getting the IV from combined byte array
        $iv = $this->getIvFromRequest($encrypted);
        if ($iv === null) {
            return null;
        }

        // Decoding the Base64 string to combined byte array
        $encryptedCombinedBytes = base64_decode($encrypted);

        // Get the encrypted bytes from combined array for decryption
        $encryptedPayload = substr($encryptedCombinedBytes, 16);

        // Decrypting the payload
        $params = $this->getAesEncrytionHeaders($skeySpec, $iv);

        $encryptor = new AESEncryption($params);

        $decryptedText = $encryptor->decrypt($encryptedPayload);

        return json_decode($decryptedText, true);
    }

    private function getAesEncrytionHeaders($secret, $iv) {
        return [
            AESEncryption::IV => $iv,
            AESEncryption::MODE => AES::MODE_CBC,
            AESEncryption::SECRET => $secret,
        ];
    }


    public function getIvFromRequest(string $encrypted) {
        // Decoding the Base64 string to combined byte array
        $encryptedCombinedBytes = base64_decode($encrypted);

        if ($encryptedCombinedBytes === false) {
            return null;
        }

        // Getting the IV from combined byte array
        return substr($encryptedCombinedBytes, 0, 16);
    }

    public function validateIdfcBankTransfer($request)
    {
        $this->trace->info(TraceCode::IDFC_VA_VALIDATION_CALLBACK,
            ['request' => $request]);

        $this->traceXFundLoadingMetricsBankWise("", Provider::IDFC);

        try {
            $iv = $this->getIvFromRequest($request);

            if (empty($iv) === true)
            {
                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED,
                    Provider::IDFC);

                $errorResp = [
                    "status" => "F",
                    "statusDesc" =>  "Failed",
                    "errorCode" =>  "02",
                    "errorDesc" => "INVALID_DATA"
                ];

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                    'message'   => 'empty iv',
                    'response' => $errorResp
                ]);

                $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

                return response()->make($encryptedRespone, 401, ['Content-Type' => 'text/plain']);
            }

            $input = $this->decryptIdfcBankCallbackData($request);

            $this->trace->info(TraceCode::IDFC_VA_VALIDATION_CALLBACK,
                ['request' => $input]);

            if($input === null)
            {

                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_DECRYPTION_FAILED,
                    Provider::IDFC);

                $errorResp = [
                    "status" => "F",
                    "statusDesc" =>  "Failed",
                    "errorCode" =>  "02",
                    "errorDesc" => "INVALID_DATA"
                ];

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_DATA_DECRYPTION_FAILED , [
                    'message'   => 'decryption failed',
                    'response' => $errorResp
                ]);

                $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

                return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
            }

            $inputList = $this->modifyIdfcDataToEntityForValidationApi($input);

            $provider = $inputList['gateway_provider']['provider'];

            $response = $this->saveRequestAndProcess($inputList['input'], $provider, false, $input);

            if ($response['valid'] === false)
            {
                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA,
                    Provider::IDFC);

                $errorResp = [
                    "vaNum" => $inputList['input']['payee_account'],
                    "bankRef" => $inputList['input']['transaction_id'],
                    "status" => "F",
                    "statusDesc" =>  "Failed",
                    "errorCode" =>  "02",
                    "errorDesc" => "INVALID_DATA"
                ];

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                    'message'   => 'invalid response from saveRequestAndProcess',
                    'response' => $errorResp
                ]);

                $encryptedRespone = $this->encryptIdfcBankCallbackData($errorResp, $iv);

                return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
            }

            $successResponse = [
                "vANum"   => $inputList['input']['payee_account'],
                "bankRef" => $inputList['input']['transaction_id'],
                "status" => "000",
                "statusDesc" => "Success",
                "errorCode" => "000",
                "errorDesc" => "Success"
            ];

            $encryptedRespone =  $this->encryptIdfcBankCallbackData($successResponse, $iv);

            if($encryptedRespone == null){
                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED, [
                    'message'   => 'encryption failure at final response',
                ]);

                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED,
                    Provider::IDFC);

                return response()->make($encryptedRespone, 500, ['Content-Type' => 'text/plain']);
            }

            $this->trace->info(TraceCode::IDFC_VA_VALIDATION_CALLBACK_SUCCESSFUL, [
                'message'   => 'success response sent ',
                'response' => $successResponse
            ]);

            return response()->make($encryptedRespone, 200, ['Content-Type' => 'text/plain']);

        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA,
                Provider::IDFC);

            $this->trace->traceException($e);

            $errorResp = [
                "vaNum" => $inputList['input']['payee_account'],
                "bankRef" => $inputList['input']['transaction_id'],
                "status" => "F",
                "statusDesc" =>  "Failed",
                "errorCode" =>  "02",
                "errorDesc" => "INVALID_DATA"
            ];

            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA , [
                'response' => $errorResp
            ]);

            $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

            return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
        }
        catch (\Throwable $e)
        {
            $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_UNKNOWN_FAILURE,
                Provider::IDFC);

            $this->trace->traceException($e);

            $errorResp = [
                "vaNum" => $inputList['input']['payee_account'],
                "bankRef" => $inputList['input']['transaction_id'],
                "status" => "F",
                "statusDesc" =>  "Failed",
                "errorCode" =>  "02",
                "errorDesc" => "INVALID_DATA"
            ];

            $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_UNKNOWN_FAILURE , [
                'response' => $errorResp
            ]);

            return response()->make($encryptedRespone, 500, ['Content-Type' => 'text/plain']);
        }
    }

    public function processIdfcBankTransfer($request)
    {
        $this->trace->info(TraceCode::IDFC_VA_NOTIFICATION_CALLBACK,
            ['request' => $request]);

        $this->traceXFundLoadingMetricsBankWise("", Provider::IDFC);

        try {
            $iv = $this->getIvFromRequest($request);

            if (empty($iv) === true)
            {
                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED,
                    Provider::IDFC);

                $errorResp = [
                    "status" => "F",
                    "statusDesc" =>  "Failed",
                    "errorCode" =>  "02",
                    "errorDesc" => "INVALID_DATA"
                ];

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_DATA_DECRYPTION_FAILED , [
                    'message'   => 'iv is empty',
                    'response' => $errorResp
                ]);

                $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

                return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
            }

            $input = $this->decryptIdfcBankCallbackData($request);

            $this->trace->info(TraceCode::IDFC_VA_NOTIFICATION_CALLBACK,
                ['request' => $input]);

            if($input === null)
            {
                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_DECRYPTION_FAILED,
                    Provider::IDFC);

                $errorResp = [
                    "status" => "F",
                    "statusDesc" =>  "Failed",
                    "errorCode" =>  "02",
                    "errorDesc" => "INVALID_DATA"
                ];

                $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_DATA_DECRYPTION_FAILED , [
                    'message'   => 'decryption failed',
                    'response' => $errorResp
                ]);

                return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
            }

            $this->trace->info(TraceCode::IDFC_VA_NOTIFICATION_CALLBACK,
                $this->removeSenderSensitiveInfoFromLogging($input, Provider::IDFC));

            $inputList = $this->modifyIdfcDataToEntityForNotificationApi($input);

            $provider = $inputList['gateway_provider']['provider'];

            $this->saveRequestAndProcess($inputList['input'], $provider, false, $input);

            $successResponse = [
                "corRefNo"   => $inputList['input']['payee_account'],
                "ReqrefNo" =>  $inputList['input']['transaction_id'],
                "status" => "S",
                "statusDesc" => "Success",
                "errorCode" => "000",
                "errorDesc" => "Success"
            ];

            $encryptedRespone =  $this->encryptIdfcBankCallbackData($successResponse, $iv);

            if($encryptedRespone == null){

                $this->trace->error(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED,
                    [
                        'message'   => 'encryption failure at final response',
                    ]);

                $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_DATA_ENCRYPTION_FAILED,
                    Provider::IDFC);

                return response()->make($encryptedRespone, 500, ['Content-Type' => 'text/plain']);
            }

            $this->trace->info(TraceCode::IDFC_VA_NOTIFICATION_CALLBACK_SUCCESSFUL, [
                'message'   => 'success response sent ',
                'response' => $successResponse
            ]);

            return response()->make($encryptedRespone, 200, ['Content-Type' => 'text/plain']);

        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA,
                Provider::IDFC);

            $this->trace->traceException($e);

            $errorResp = [
                "corRefNo"   => $inputList['input']['payee_account'],
                "ReqrefNo" =>  $inputList['input']['transaction_id'],
                "status" => "F",
                "statusDesc" =>  "Failure",
                "errorCode" =>  "1001",
                "errorDesc" => "INVALID_DATA"
            ];

            $encryptedRespone =  $this->encryptIdfcBankCallbackData($errorResp, $iv);

            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA , [
                'response' => $errorResp
            ]);

            return response()->make($encryptedRespone, 400, ['Content-Type' => 'text/plain']);
        }
        catch (\Throwable $e) {
            $this->traceXFundLoadingMetricsBankWise(TraceCode::IDFC_VA_CALLBACK_UNKNOWN_FAILURE,
                Provider::IDFC);

            $this->trace->traceException($e);

            $errorResp = [
                "corRefNo" => $inputList['input']['payee_account'],
                "ReqrefNo" => $inputList['input']['transaction_id'],
                "status" => "F",
                "statusDesc" => "Failure",
                "errorCode" => "1001",
                "errorDesc" => "INVALID_DATA"
            ];

            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA , [
                'response' => $errorResp,
                'status_code' => 500,
            ]);

            $encryptedRespone = $this->encryptIdfcBankCallbackData($errorResp, $iv);

            return response()->make($encryptedRespone, 500,['Content-Type' => 'text/plain']);
        }
    }

    protected function traceXFundLoadingMetricsBankWise($traceCode, $bankName)
    {
        try {
            if($bankName == Provider::IDFC){
                $metric = empty($traceCode) ? \RZP\Models\Payout\Metric::FUND_LOADING_VA_CALLBACK : \RZP\Models\Payout\Metric::FUND_LOADING_VA_CALLBACK_FAILURE;

                $this->trace->count(
                    $metric,
                    [
                        'trace_code' => $traceCode,
                        'route_name' => $this->app['api.route']->getCurrentRouteName()
                    ]);
            }
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::IDFC_METRIC_LOGGING_ERROR, [
                'message' => $e->getMessage(),
                'trace_code' => $traceCode,
                'route_name' => $this->app['api.route']->getCurrentRouteName()
            ]);
        }
    }

    protected function modifyIdfcDataToEntityForValidationApi($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$idfcRulesForValidationApi)->caller($this)->validate($input);

        // Bank is not sending IFSC code in case of IMPS mode.
        $mode = \RZP\Models\BankTransfer\Mode::NEFT;

        $payerIfsc = $input['remitterBankifsc']?: "";

        if($payerIfsc === "")
        {
            $mode = \RZP\Models\BankTransfer\Mode::IMPS;
        }

        $utr = $input['bankRef'];

        if (($utr === null))
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'utr is null',
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA');
        }

        $provider  = Provider::IDFC;

        $payerAccount = $input['remitterAc'];

        $payeeIfsc = Provider::IDFC_COMMON_IFSC;  // Payee IFSC code is hardcoded at our end

        $payerName = $input['remiterName']??""; // spelling error in bank doc

        $time = Carbon::now(Timezone::IST)->getTimestamp();

        $payeeAccount = $input['VANum'];

        if(in_array(substr($payeeAccount, 0, 4), Provider::IDFC_VA_PREFIX) === false)
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'invalid Va idfc account prefix',
                'va_prefix' => substr($payeeAccount, 0, 4)
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA');
        }

        return array(
            'input' => [
                'request_type'  => 'validation',
                'payee_account'  => $payeeAccount,
                'payee_ifsc'     => $payeeIfsc,
                'payer_account'  => $payerAccount,
                'payer_name'     => $payerName,
                'payer_ifsc'     => $payerIfsc,
                'transaction_id' => $utr,
                'amount'         => number_format($input['txnAmt'], 2, '.', ''),
                'mode'           => $mode,
                'time'           => $time,
                'description'    => null,
                'narration'      => $utr,
            ],
            'gateway_provider' => [
                'provider'       => $provider,
            ]);
    }

    protected function modifyIdfcDataToEntityForNotificationApi($input)
    {
        (new JitValidator)->setStrictFalse()->rules(Validator::$idfcRulesForNotificationApi)->caller($this)->validate($input);

        // setting up the default mode, in case of mode is not available
        $productCode = $input['productCode'];

        if(BankTransferConstants::IDFC_PRODUCT_CODE_TO_MODE_MAPPING[$productCode]=== null)
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'invalid product code in the request',
                'productCode' => $productCode,
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA', null, $input);
        }

        $mode = BankTransferConstants::IDFC_PRODUCT_CODE_TO_MODE_MAPPING[$productCode];
        $utr = $input['utrNo'];

        if (($utr === null))
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'utr is null',
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA', null, $input);
        }

        $provider  = Provider::IDFC;

        $payerAccount = $input['remitterAccountNumber'];
        $payeeAccount = $input['vaNumber'];
        $payeeIfsc = Provider::IDFC_COMMON_IFSC;  // Payee IFSC code is hardcoded at our end

        $payerIfsc = $input['ifscCode']?: "";

        //$payerIfsc cannot be null for NEFT, RTGS and IFT mode. It can be null only for IMPS mode.
        if($payerIfsc === "" && $mode !== \RZP\Models\BankTransfer\Mode::IMPS)
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'payerIfsc is null',
                'mode' => $mode,
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA');
        }

        try
        {
            $time = Carbon::createFromFormat(  'd-M-y h.i.s.u A', $input['creditGenerationTime'], Timezone::IST)->getTimestamp();
        }
        catch (\Throwable $e)
        {

            $this->trace->warning(TraceCode::AXIS_VA_INVALID_CALLBACK_DATA, [
                'message' => 'invalid time format',
                'time'  => $input['Req_dt_time'] ?: null,
            ]);

            $time = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $payerName = $input['remitterName']??"";

        if(in_array(substr($payeeAccount, 0, 4), Provider::IDFC_VA_PREFIX)  === false)
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'invalid Va idfc account prefix',
                'va_prefix' => substr($payeeAccount, 0, 4)
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA');
        }

        // https://razorpay.slack.com/archives/C07PW1M7HAQ/p1737011718760019
        // Check if the request is > 2 days old, reject the request if true
        $diff = Carbon::now(Timezone::IST)->diff(Carbon::createFromTimestamp($time, Timezone::IST));
        if($diff->days >= 2)
        {
            $this->trace->error(TraceCode::IDFC_VA_CALLBACK_INVALID_DATA, [
                'message'   => 'Transaction older than 2 days',
                'va_prefix' => substr($payeeAccount, 0, 4)
            ]);

            throw new BadRequestValidationFailureException('INVALID_DATA');
        }

        return array(
            'input' => [
                'request_type'  => 'notification',
                'payee_account'  => $payeeAccount,
                'payee_ifsc'     => $payeeIfsc,
                'payer_account'  => $payerAccount,
                'payer_ifsc'     => $payerIfsc,
                'payer_name'     => $payerName,
                'transaction_id' => $utr,
                'amount'         => number_format($input['batchAmt'], 2, '.', ''),
                'mode'           => $mode,
                'time'           => $time,
                'description'    => null,
                'narration'      => $utr,
            ],
            'gateway_provider' => [
                'provider'       => $provider,
            ]);
    }


}
