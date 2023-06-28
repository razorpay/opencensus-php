<?php

namespace RZP\Models\BankTransfer;

use App;
use Cache;
use Carbon\Carbon;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Environment;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\Tracer;
use Symfony\Component\HttpFoundation\File\File;
use RZP\Jobs\CrossBorderCommonUseCases;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Address;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\QrPayment;
use RZP\Models\BankAccount;
use RZP\Base\RuntimeManager;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Models\BankTransferHistory;
use RZP\Models\BankTransferRequest;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Metric;
use RZP\Models\VirtualAccount\Provider;
use RZP\Reconciliator\RequestProcessor;
use RZP\Jobs\BankTransferCreateProcess;
use RZP\Models\Payment\Processor\Notify;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;
use RZP\Models\BankTransfer\Processor as BankTransferProcessor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant\InternationalIntegration;
use function GuzzleHttp\default_ca_bundle;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Models\Pricing\Service as PricingService;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Models\Workflow\Service\Builder as WorkflowBuilder;

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
    }

    public function processPendingBankTransfer(array $input)
    {
        (new Validator)->validateInput('pending_bank_transfer', $input);

        $bankTransferRequestId = $input[BankTransferRequest\Entity::BANK_TRANSFER_REQUEST_ID];

        try
        {
            /** @var BankTransferRequest\Entity $bankTransferRequest */
            $bankTransferRequest = $this->repo->bank_transfer_request->findOrFailPublic($bankTransferRequestId);

            // $input as first parameter is not required. $bankTransferRequest is sufficient to process the request
            // This is because: Following call has been deprecated $this->process($input, $provider, $checkForIfsc);
            // in validateAndProcessRequest
            return $this->validateAndProcessRequest([], $bankTransferRequest, Provider::ICICI, true, true);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESS_REQUEST_NOT_FOUND,
                [
                    'message'   =>  'Bank Transfer Request not found',
                    BankTransferRequest\Entity::BANK_TRANSFER_REQUEST_ID => $bankTransferRequestId,
                ]
            );

            throw $ex;
        }
    }

    public function processBankTransferInScService(array $input,
                                                   string $provider = null,
                                                   $requestPayload = null)
    {
        $response = $this->smartCollectService->processBankTransfer(['data'            => $input,
                                                                     'gateway'         => $provider,
                                                                     'request_payload' => $requestPayload]);
        if ((isset($response['status_code']) === true) and
            ($response['status_code'] === 200))
        {
            return $response['body'];
        }

        return $this->saveRequestAndProcess($input, $provider, false, $requestPayload);
    }

    public function saveRequestAndProcessInternal(array $input, string $routeName = null)
    {
        $data           = $input['data'];
        $provider       = $input['gateway'];
        $requestPayload = $input['request_payload'];

        return $this->saveRequestAndProcess($data, $provider, false, $requestPayload, $routeName);
    }

    public function saveRequestAndProcess(
        array $input,
        string $provider = null,
        bool $checkForIfsc = false,
        $requestPayload = null,
        string $routeName = null
    )
    {
        $response = $this->validateDuplicateRequest($input, $routeName);

        if (empty($response) === false)
        {
            return $response;
        }

        $bankTransferRequest = null;

        try
        {
            if ($checkForIfsc === true)
            {
                $this->checkAndReplaceForIfsc($input, $provider ?? $this->provider);
            }

            $this->removeInvalidRegexFromPayerAccount($input);

            $this->extractPayerNameAndAccountFromPayerName($input);

            $this->modifyInvalidInputForPJSB($input);

            $bankAccount = $this->getQrBankAccount($input);

            if ($bankAccount !== null)
            {
                return (new QrPayment\Service())->processBankTransfer($input, $provider ?? $this->provider,
                                                                      $requestPayload, $bankAccount);
            }

            $bankTransferRequest = (new BankTransferRequest\Core())->create(
                $input,
                $provider ?? $this->provider,
                $requestPayload ?? $input, [], $routeName
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex,
                                         Trace::ERROR,
                                         TraceCode::BANK_TRANSFER_SAVE_REQUEST_FAILED,
                                         [
                                             'transaction_id' => $input[Entity::REQ_UTR]
                                         ]);
        }

        return $this->validateAndProcessRequest($input, $bankTransferRequest, $provider, $checkForIfsc);
    }

    protected function validateAndProcessRequest(
        array $input,
        BankTransferRequest\Entity $bankTransferRequest,
        string $provider = null,
        bool $checkForIfsc = false,
        bool $skipPayeeAccountLengthValidation = false)
    {
        if ($bankTransferRequest !== null and $bankTransferRequest->getPayeeAccount() !== null)
        {
            if ($skipPayeeAccountLengthValidation === false)
            {
                $response = $this->validateProviderSpecificFields($bankTransferRequest);

                if (empty($response) === false)
                {
                    return $response;
                }
            }

            return $this->dispatchBankTransferToQueue($bankTransferRequest);
        }

        return $this->process($input, $provider, $checkForIfsc);
    }

    public function removeInvalidRegexFromPayerAccount(& $input)
    {
        if (isset($input[Entity::PAYER_ACCOUNT]) === true)
        {
            $payerAccountNumber = $input[Entity::PAYER_ACCOUNT];

            $payerAccountInvalidRegexes = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::PAYER_ACCOUNT_NUMBER_INVALID_REGEXES]);

            foreach ($payerAccountInvalidRegexes as $invalidRegex)
            {
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
    private function extractPayerNameAndAccountFromPayerName(& $input)
    {
        $invalidPayerAccountValue = 'IN';

        if (isset($input[Entity::PAYER_ACCOUNT]) === true && $input[Entity::PAYER_ACCOUNT] === $invalidPayerAccountValue)
        {
            $payerName = $input[Entity::PAYER_NAME];

            $payerAccountNameInvalidRegexes = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::PAYER_ACCOUNT_NAME_INVALID_REGEXES]);

            foreach ($payerAccountNameInvalidRegexes as $invalidRegex)
            {
                $invalidPrefixRegex = '/' . $invalidRegex . '/i';

                $payerName = preg_replace($invalidPrefixRegex, '', $payerName); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval
            }

            $payerAccountAndNameArr = explode(" ", trim($payerName), 2);

            if (sizeof($payerAccountAndNameArr) === 2)
            {
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
            'valid'          => $valid,
            'message'        => null,
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

        if (empty($provider) === false)
        {
            $this->provider = $provider;
        }

        $this->validateProvider($input[Entity::REQ_UTR]);

        $this->checkBlocks($input);

        $valid = $this->core->process($input, $this->provider);

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input[Entity::REQ_UTR] ?? '',
        ];
    }

    public function processFile(array $input, $batchType): array
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            [
                'input'      => $input,
                'batch_type' => $batchType,
            ]
        );

        Batch\Type::validateType($batchType);

        $source = $this->getRequestSource();

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST_SOURCE,
            [
                'source'     => $source,
                'batch_type' => $batchType,
            ]
        );

        $requestProcessor = $this->getRequestProcessor($source);

        $fileDetails = $requestProcessor->processForVa($input);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_REQUEST,
            [
                'file details'    => $fileDetails,
            ]
        );

        $batchCore = new Batch\Core;

        if (isset($fileDetails['file_details']) === true)
        {
            $file = new File($fileDetails['file_details'][0]['file_path']);

            $params = [
                Batch\Entity::TYPE          => $batchType,
                Batch\Entity::FILE          => $file,
            ];

            $sharedMerchant = $this->repo
                                   ->merchant
                                   ->findOrFailPublic(Account::SHARED_ACCOUNT);

            $batch = $batchCore->create($params, $sharedMerchant);

            return $batch->toArrayPublic();
        }

        return [];
    }

    protected function checkAndReplaceForIfsc(array & $input, string $provider = null)
    {
        if ($provider === Provider::ICICI)
        {
            if ((isset($input[Entity::PAYER_IFSC]) === false) or
                ($input[Entity::PAYER_IFSC] === ''))
            {
                $input[Entity::PAYER_IFSC] = BankCodes::IFSC_ICIC;
            }
        }

        if (isset($input[Entity::PAYER_IFSC]) === false)
        {
            return;
        }

        $ifsc = $input[Entity::PAYER_IFSC];

        $ifscValidator = new BankAccount\Validator;

        try
        {
            $ifscValidator->validateIfscCode([BankAccount\Entity::IFSC_CODE => $ifsc]);
        }
        catch (Exception\BadRequestValidationFailureException $exception)
        {
            $bankCode = substr($ifsc, 0, 4);

            $defaultIfscCode = BankCodes::getIfscForBankCode($bankCode);

            if ($defaultIfscCode === null)
            {
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

        $this->validateProvider();

        $success = $this->core->notify($input, $this->provider);

        return [
            'success'        => $success,
            'message'        => null,
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
                'input'    => $this->core->removePiiForLogging($input, [
                                                                        Entity::PAYEE_ACCOUNT,
                                                                        Entity::PAYER_ACCOUNT,
                                                                        Entity::PAYER_NAME]),
            ]
        );

        if ($this->mode === Mode::LIVE)
        {
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
        $payment = Tracer::inSpan(['name' => Constants\HyperTrace::BANK_TRANSFER_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT], function() use($paymentId)
        {
            return $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $bankTransfer = Tracer::inSpan(['name' => Constants\HyperTrace::BANK_TRANSFER_SERVICE_FIND_BY_PAYMENT], function() use($payment)
        {
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
            function() use ($input)
            {
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
            (Provider::validateMode($this->provider, $this->mode) === false))
        {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_PROVIDER_VALIDATION_FAILED,
                [
                    'provider'          => $this->provider,
                    'ip'                => $this->ip,
                    'mode'              => $this->mode,
                    Entity::UTR         => $utr
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function checkBlocks(array & $input)
    {
        if (($this->areBankTransfersBlockedForYesBank() === true) and
            ($this->provider === Provider::YESBANK))
        {
            throw new LogicException('Payment made via YesBank');
        }

        if ($this->areBankTransfersBlockedForAllMerchants() === true)
        {
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

        foreach ($bankTransfers as $bankTransfer)
        {
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

        try
        {
            $block = (bool) Cache::get($key);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL);

            $block = false;
        }

        return $block;
    }

    protected function getRequestSource() : string
    {
        if ($this->isLambdaRequest() === true)
        {
            return RequestProcessor\Base::LAMBDA;
        }
        else
        {
            return RequestProcessor\Base::MAILGUN;
        }
    }

    /**
     * Checks if the request originated via an AWS Lambda trigger.
     *
     * @return bool
     */
    protected function isLambdaRequest() : bool
    {
        return ($this->auth->isLambda());
    }

    protected function getRequestProcessor(string $source)
    {
        $source = studly_case($source);

        $requestProcessor = 'RZP\\Reconciliator\\RequestProcessor\\' . $source;

        return new $requestProcessor();
    }

    private function dispatchBankTransferToQueue($bankTransferRequest)
    {
        $isPushedToSqs = false;
        try
        {
            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_SQS_PUSH_INIT,
                [
                    Entity::GATEWAY         => $bankTransferRequest->getGateway(),
                    Entity::REQ_UTR         => $bankTransferRequest->getUtr(),
                    'bankTransferRequestId' => $bankTransferRequest->getId(),
                    Entity::REQUEST_SOURCE  => $bankTransferRequest->getRequestSource(),
                ]
            );

            BankTransferCreateProcess::dispatch($this->mode, $bankTransferRequest->getId());

            $isPushedToSqs = true;
        }
        catch (\Exception $e)
        {
            $this->trace->critical(
                TraceCode::BANK_TRANSFER_PROCESS_SQS_PUSH_FAILED,
                [
                    Entity::GATEWAY => $bankTransferRequest->getGateway(),
                    Entity::REQ_UTR => $bankTransferRequest->getUtr(),
                    'message'       => $e->getMessage(),
                ]);
        }

        (new Metric())->pushSqsPushMetrics(Constants\Entity::BANK_TRANSFER, $bankTransferRequest->getGateway(), $isPushedToSqs);

        return [
            'valid'          => true,
            'message'        => null,
            'transaction_id' => $bankTransferRequest->getUtr(),
        ];
    }

    protected function checkBlocksAndUpdateRequest(BankTransferRequest\Entity $bankTransferInput)
    {
        $input = [];

        $this->checkBlocks($input);

        if (isset($input[Entity::PAYEE_ACCOUNT]) === true)
        {
            $bankTransferInput->setPayeeAccount($input[Entity::PAYEE_ACCOUNT]);
        }
    }

    private function validateDuplicateRequest(array $input, $routeName = null)
    {
        if ($routeName === null)
        {
            $routeName = $this->app['api.route']->getCurrentRouteName();
        }

        if (($routeName === 'bank_transfer_process_rbl_internal') or
            ($routeName === 'bank_transfer_process_icici_internal') or
            ($routeName === 'bank_transfer_process_yesbank_internal'))
        {
            if(!isset($input[Entity::AMOUNT], $input[Entity::REQ_UTR], $input[Entity::PAYEE_ACCOUNT]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE, $input);
            }

            (new Validator)->validateInput('validateDuplicateReq', array(Entity::AMOUNT => $input[Entity::AMOUNT],
                                                                         Entity::REQ_UTR => $input[Entity::REQ_UTR],
                                                                         Entity::PAYEE_ACCOUNT => $input[Entity::PAYEE_ACCOUNT]));

            $duplicateBankTransfer = $this->repo
                                          ->bank_transfer
                                          ->findByUtrAndPayeeAccountAndAmount($input[Entity::REQ_UTR],
                                                                              $input[Entity::PAYEE_ACCOUNT],
                                                                              $input[Entity::AMOUNT] * 100);

            if ($duplicateBankTransfer !== null)
            {
                return [
                    'valid'          => true,
                    'message'        => null,
                    'transaction_id' => $input[Entity::REQ_UTR] ?? '',
                ];
            }
        }

        return  [];
    }

    protected function getProvider()
    {
        if(in_array($this->auth->getInternalApp(),['merchant_dashboard','admin_dashboard']) === true)
        {
            return 'dashboard';
        }

        return $this->auth->getInternalApp();
    }

    protected function validateProviderSpecificFields(BankTransferRequest\Entity $bankTransferRequest)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Validation for ICICI (We are keeping this based on the route).
        if (($routeName === 'bank_transfer_process_icici_internal') or
            ($routeName === 'bank_transfer_process_icici'))
        {
            $payeeAccount = trim($bankTransferRequest->getPayeeAccount());

            $processor = new BankTransferProcessor();

            $isBankingType = $processor->getTransferTypeBasedOnPayeeAccount($payeeAccount);

            if ($isBankingType and strlen($payeeAccount) !== 16)
            {
                $this->trace->info(TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                                   [
                                       $bankTransferRequest->toArrayTrace()
                                   ]);

                (new BankTransferRequest\Core)->updateBankTransferRequest($bankTransferRequest->getUtr(),
                                                                          false,
                                                                          TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                                                                          $bankTransferRequest);

                $traceInfo = [
                    'message'        => TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
                    'transaction_id' => $bankTransferRequest->getUtr() ?? '',
                ];

                (new SlackNotification)->send(
                    'Received Payee Account Number with invalid length',
                    $traceInfo,
                    null,
                    1,
                    'x-finops');

                return [
                    'valid'          => false,
                    'message'        => TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
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
        switch ($provider)
        {
            case Provider::RBL:

                unset($input['Data'][0]['senderAccountNumber']);
                break;

            case Provider::ICICI :

                unset($input['Virtual_Account_Number_Verification_IN'][0]['payer_account']);
                break;

            case Provider::HDFC_ECMS :

                unset($input['Remitter_Account_No'], $input['Account_Number']);
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

        $timestamp  = Carbon::now(Timezone::IST)->getTimestamp();

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
        if (starts_with($input[Entity::PAYER_IFSC], IFSC::PJSB))
        {
            if (str_contains(strtolower($input[Entity::PAYER_ACCOUNT]), strtolower($input[Entity::PAYER_NAME])))
            {
                $payer_account = str_replace(strtolower(addslashes($input[Entity::PAYER_NAME])),'', strtolower($input[Entity::PAYER_ACCOUNT]));

                $this->trace->info(TraceCode::BANK_TRANSFER_REQUEST_PJSB_INVALID_INPUT_MODIFICATION, [
                    'field'         => Entity::PAYER_ACCOUNT,
                ]);

                $input[Entity::PAYER_ACCOUNT] = $payer_account;
            }
        }
    }

    public function createAccountForCurrencyCloud($input)
    {
        (new Validator)->validateInput('create_account_for_currency_cloud', $input);

        $merchantId = $this->merchant->getId();

        if(!isset($input['va_currency']))
        {
            $input['va_currency'] = Currency::USD;
        }
        $va_currency = strtoupper($input['va_currency']);

        if(Gateway::isVACurrencySupportedForInternationalBankTransfer($va_currency) === false){
            throw new \Exception("Currency/Method Not Supported for International Bank Transfer");
        }

        if (($this->merchant->isInternational() === false) or
            ($input['va_currency'] === Currency::USD and boolval($input['accept_b2b_tnc']) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_FEATURE_NOT_ENABLED,null,[
                'international'         => $this->merchant->isInternational(),
                't&c'                   => $input['accept_b2b_tnc'],
            ]);
        }

        $mutex_key = "create_account_cc_" . $merchantId;

        $this->mutex->acquireAndRelease($mutex_key,
            function () use ($merchantId,$va_currency)
            {
                $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                    $merchantId,Constants\Entity::CURRENCY_CLOUD);

                if(isset($mii))
                {
                    $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_VA_ALREADY_EXISTS, [
                        'merchant_id' => $merchantId,
                        'mii_id'      => $mii->getId(),
                    ]);
                }
                else
                {
                    try
                    {
                        $requestBody = $this->createRequestBodyForAccountCreation($merchantId);
                    }
                    catch (\Throwable $e)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null,[
                            'error_desc' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                        ]);
                    }

                    $responseBody = $this->app->mozart->sendMozartRequest('onboarding',Constants\Entity::CURRENCY_CLOUD,'account_create',$requestBody);

                    if(!isset($responseBody['data']['account_id']) || !isset($responseBody['data']['contact_id']))
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_CREATION_FAILED, null, [
                            'response' => $responseBody,
                        ]);
                    }

                    $merchantInternationalIntegrations = [
                        InternationalIntegration\Entity::MERCHANT_ID        => $merchantId,
                        InternationalIntegration\Entity::INTEGRATION_ENTITY => Constants\Entity::CURRENCY_CLOUD,
                        InternationalIntegration\Entity::INTEGRATION_KEY    => $responseBody['data']['account_id'],
                        InternationalIntegration\Entity::REFERENCE_ID       => $responseBody['data']['contact_id'],
                    ];

                    (new InternationalIntegration\Core)->createMerchantInternationalIntegration($merchantInternationalIntegrations);
                }

                try{
                    //assign default pricing in case we are on-boarding the merchant for the first time
                    $this->setDefaultPricing($merchantId,$va_currency);
                } catch (\Throwable $e) {

                    $this->trace->traceException(
                        $e,
                        null,
                        TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_FAILED,
                        [
                            'merchant_id'   =>  $merchantId,
                            'va_currency'  =>  $va_currency,
                        ]
                    );

                    throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_UNABLE_TO_ASSIGN_PRICING_PLAN_FOR_B2B_EXPORT,
                        null,
                        [
                            'error_desc' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                        ]);
                }

                $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                    $merchantId,Constants\Entity::CURRENCY_CLOUD);

                (new Merchant\Service)->addFeatureFlag(
                    [
                        Feature\Constants::ENABLE_B2B_EXPORT
                    ], true
                );

                $this->trace->info(TraceCode::B2B_FEATURE_FLAG_ADDED,[
                    'merchant_id' => $merchantId,
                    'feature_flag' => Feature\Constants::ENABLE_B2B_EXPORT,
                ]);

                $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                    $merchantId,Constants\Entity::CURRENCY_CLOUD);

                $mii = $this->updateBankAccountDetailsByVACurrency($merchantId,$mii,$va_currency);
            },20,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

            return (new InternationalIntegration\Core)->fetchIntlVirtualBankAccountsForGateway($merchantId,Constants\Entity::CURRENCY_CLOUD);
    }

    public function updateBankAccountDetailsByVACurrency($merchantId, $merchantInternationalIntegrations,$va_currency)
    {
        if($va_currency === Gateway::SWIFT)
        {
            $request = [
                'payment_type'      => self::PRIORITY,
                'account_id'        => $merchantInternationalIntegrations->getIntegrationKey(),
                'contact_id'        => $merchantInternationalIntegrations->getReferenceId(),
                'currency'          => Currency::USD,
            ];
        }
        else
        {
            $request = [
                'payment_type'      => self::REGULAR,
                'account_id'        => $merchantInternationalIntegrations->getIntegrationKey(),
                'contact_id'        => $merchantInternationalIntegrations->getReferenceId(),
                'currency'          => $va_currency,
            ];
        }

        $bankAccounts = $merchantInternationalIntegrations->getBankAccount();

        $bankAccount = $this->getFundingAccountDetailsByCurrency($request, $va_currency);

        if(isset($bankAccounts) === false || empty($bankAccounts) === true)
        {
            $bankAccounts = array();
        }
        else{
            $bankAccounts = json_decode($bankAccounts);
        }
        $bankAccounts[] = $bankAccount;

        $mii = [
            InternationalIntegration\Entity::MERCHANT_ID        => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => Constants\Entity::CURRENCY_CLOUD,
            InternationalIntegration\Entity::INTEGRATION_KEY    => $merchantInternationalIntegrations->getIntegrationKey(),
            InternationalIntegration\Entity::REFERENCE_ID       => $merchantInternationalIntegrations->getReferenceId(),
            InternationalIntegration\Entity::BANK_ACCOUNT       => json_encode($bankAccounts)
        ];

        $enable_methods = [
            Merchant\Methods\Entity::INTL_BANK_TRANSFER => [
                Payment\Gateway::getIntlBankTransferModeByCurrency($va_currency) => 1,
            ]
        ];

        $methods = $this->merchant->methods;
        $methods->setMethods($enable_methods);
        $this->repo->saveOrFail($methods);

        return (new InternationalIntegration\Core)->editMerchantInternationalIntegrations($mii);
    }

    protected function getFundingAccountDetailsByCurrency($request, $va_currency)
    {
        $response = $this->app->mozart->sendMozartRequest('onboarding',Constants\Entity::CURRENCY_CLOUD,'get_funding_account',$request);

        $funding_accounts = $response['data']['funding_accounts'];

        $virtualAccountDetails = [
            'account_number'      => $funding_accounts[0]['account_number'],
            'va_currency'         => $va_currency,
            'beneficiary_name'    => $funding_accounts[0]['account_holder_name'],
            'bank_name'           => $funding_accounts[0]['bank_name'],
            'bank_address'        => $funding_accounts[0]['bank_address']
        ];

        $routing_codes = [];

        foreach($funding_accounts as $funding_account){
            // Storing routing_code_type as routing_type in our systems as designs suggests
            $routing_code['routing_type']       =  $funding_account['routing_code_type'];
            $routing_code['routing_code']       =  $funding_account['routing_code'];
            array_push($routing_codes,$routing_code);
        }

        $virtualAccountDetails['routing_details'] = $routing_codes;

        return $virtualAccountDetails;
    }

    protected function createRequestBodyForAccountCreation($merchantId)
    {
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $name = explode(' ',$merchantDetail->getPromoterPanName(),2);

        $address = [
            'street' => $merchantDetail->getBusinessRegisteredAddress(),
            'city'   => $merchantDetail->getBusinessRegisteredCity(),
            'state'  => $merchantDetail->getBusinessRegisteredState(),
            'country'=> $merchantDetail->getBusinessRegisteredCountry() ?? "IN",
            'pin'    => $merchantDetail->getBusinessRegisteredPin(),
        ];

        $contact = [
            'first_name' => $name[0],
            'last_name'  => isset($name[1]) ? $name[1] : "_",
            'email'      => $merchantDetail->getContactEmail(),
            'phone'      => $merchantDetail->getContactMobile(),
            'login_id'   => $merchantId."_razorpay"
        ];

        $requestBody = [
            'account_name' => $this->merchant->getName(),
            'address'      => $address,
            'contact'      => $contact,
        ];

        return $requestBody;
    }

    public function notificationsFromCurrencyCloud($input, $header): array
    {
        $this->trace->info(TraceCode::CURRENCY_CLOUD_NOTIFICATION_REQUEST,[
            'input'  => $input,
            'header' => $header,
        ]);

        if ($this->app['env'] === Environment::BVT or
            $this->app['env'] === Environment::AUTOMATION or
            $this->app['env'] === Environment::TESTING)
        {
            $this->app['rzp.mode']=Mode::TEST;
        } else {
            $this->app['rzp.mode']=Mode::LIVE;
        }

        switch($header)
        {
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
                $this->trace->info(TraceCode::CURRENCY_CLOUD_INVALID_NOTIFICATION,[
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
            ($addresses->isNotEmpty() === true))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $addresses,
                        [
                            'error_desc' => 'Address already saved',
                            'error_code' => 'BAD_REQUEST_ERROR',
                        ]);
        }

        if ((empty($input) === false) and
            (isset($input['country']) === true) and
            ((strtolower($input['country']) === 'in') or (strtolower($input['country']) === 'ind') or (strtolower($input['country']) === 'india')))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $input,
                        [
                            'error_desc' => 'Invalid country',
                            'error_code' => 'BAD_REQUEST_ERROR',
                        ]);
        }

        $formattedAddress = [
            'type'      => Address\Type::BILLING_ADDRESS,
            'name'      => $input['name'],
            'zipcode'   => $input['zipcode'],
            'line1'     => $input['line1'],
            'city'      => $input['city'],
            'country'   => $input['country'],
            'state'     => $input['state'] ?? '',
        ];

        return (new Address\Core)->create($payment, $payment->getEntity(), $formattedAddress);
    }

    public function getAddressEntityForB2B($paymentId = '')
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_INTL_BANK_TRANSFER) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                        [
                            'error_desc' => 'Merchant not allowed',
                            'error_code' => 'BAD_REQUEST_ERROR',
                        ]);
        }

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        if (($payment->isAuthorized() === false) or
            ($payment->isB2BExportCurrencyCloudPayment() === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                        [
                            'error_desc' => 'Invalid payment status or method',
                            'error_code' => 'BAD_REQUEST_ERROR',
                        ]);
        }

        $addresses = $this->repo->address->fetchAddressesForEntity($payment,
            ['type'=> Address\Type::BILLING_ADDRESS]);

        return [$payment, $addresses];
    }


    public function captureCronForB2BPayments($input)
    {
        if($this->app['env'] != Environment::TESTING)
        {
            $this->app['rzp.mode']=Mode::LIVE;
        }

        $limit = isset($input['limit']) ? $input['limit'] : 10;

        $payments =  $this->repo->payment->getPaymentsWithReferenceId(Constants\Entity::CURRENCY_CLOUD,Payment\Status::AUTHORIZED, $limit);

        foreach ($payments as $payment)
        {
            try
            {
                $merchantId = $payment->getMerchantId();

                $merchant = $this->repo->merchant->find($merchantId);

                if($payment->isDirectSettlement() === true)
                {
                    $this->trace->info(TraceCode::B2B_TRANSFER_NOT_APPLICABLE_FOR_THIS_PAYMENT, [
                        'payment_id' => $payment->getId(),
                        'is_direct_settlement' => $payment->isDirectSettlement(),
                    ]);

                    continue;
                }

                // merchant should add customer billing address before payments can be captured
                $addresses = $this->repo->address->fetchAddressesForEntity($payment,
                                ['type'=> Address\Type::BILLING_ADDRESS]);

                // Transfer_id which we get from CC is stored in Reference16 attribute
                if($payment->getReference16() != null or
                    !$merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B) or
                    ($addresses->isEmpty() === true))
                {
                    $this->trace->info(TraceCode::B2B_TRANSFER_COMPLETION_PENDING,[
                        'payment_id'                => $payment->getId(),
                        'payment_transfer_id'       => $payment->getReference16(),
                        'settlement_flow_by_risk'   => $merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B),
                        'address_empty'             => ($addresses->isEmpty() ? 'yes' : 'no'),
                    ]);

                    continue;
                }

                $merchantInternationalIntegration  = (new \RZP\Models\Merchant\InternationalIntegration\Repository)->getByMerchantIdAndIntegrationEntity($merchantId,Constants\Entity::CURRENCY_CLOUD);

                $parentRZPAccountId = $this->app['config']->get('gateway.currency_cloud.rzp_parent_account_id');

                $request = [
                    'currency'              => $payment->getCurrency(),
                    'amount'                => strval($payment->getAmount()/100),
                    'reason'                => BankTransferConstants::HOUSE_ACCOUNT_TRANSFER_REASON."; " . $payment->getId(),
                    'destination_account_id'=> $parentRZPAccountId,
                    'payment_id'            => $payment->getId(),
                    'source_account_id'     => $merchantInternationalIntegration->getIntegrationKey(),
                ];

                $response = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'create_transfer',$request);

                $payment = $this->repo->payment->findOrFail($payment->getId());

                $payment->setReference16($response['data']['id']);

                $this->repo->payment->saveOrFail($payment);

                $this->trace->info(TraceCode::B2B_SETTLEMENT_TO_RZP_PARENT_ACCOUNT,[
                'payment_id'          => $payment->getId(),
                'payment_transfer_id' => $response['data']['id'],
                'amount'              => $response['data']['amount'],
                'currency'            => $response['data']['currency'],
                ]);
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::B2B_SETTLEMENT_TO_RZP_PARENT_ACCOUNT_FAILED,
                    [
                        'message'   =>  'Sub Account Transfer to House Failed',
                        'payment_id' => $payment->getId(),
                    ]
                );
            }
        }
    }

    /**
     * @throws \Exception in case pricing plan is not set
     */
    private function setDefaultPricing($merchantId, $va_currency):void {

        $defaultPricing =  $this->getDefaultPricingForInternationalBankTransfer($merchantId,$va_currency);

        $pricingPlan = (new PricingService)->postAddBulkPricingRules([$defaultPricing]);

        if($pricingPlan["items"][0]["success"] === false)
        {
            if ($pricingPlan["items"][0]["error"]["code"] === ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED )
            {
                // in case the pricing plan is already present don't throw the exception
                $this->trace->info(TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_SUCCESSFUL,[
                    'merchant_id'   => $merchantId,
                    'description'  => $pricingPlan["items"][0]["error"]["description"],
                    'code'   => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED
                ]);

                return;
            }

            throw new Exception\ServerErrorException('Default Pricing plan creation error',
                ErrorCode:: SERVER_ERROR_UNABLE_TO_ASSIGN_PRICING_PLAN_FOR_B2B_EXPORT,[
                    'error_desc' => $pricingPlan["items"][0]["error"]["description"],
                    'error_code' => $pricingPlan["items"][0]["error"]["code"],
                ]);
        }
        else
        {
            $this->trace->info(TraceCode::B2B_EXPORT_DEFAULT_PRICING_PLAN_CREATION_SUCCESSFUL,[
                'merchant_id'   => $merchantId,
                'pricing_plan'  => $pricingPlan,
                'va_currency'   => $va_currency
            ]);
        }
    }

    private function getDefaultPricingForInternationalBankTransfer($merchantId, $va_currency):array {
        $defaultStaticPricing = [
            PricingEntity::PRODUCT                  =>  Product::PRIMARY,
            PricingEntity::FEATURE                  =>  \RZP\Models\Pricing\Feature::PAYMENT,
            PricingEntity::PAYMENT_METHOD           =>  Payment\Method::INTL_BANK_TRANSFER,
            PricingEntity::PAYMENT_METHOD_SUBTYPE   =>  "",
            PricingEntity::INTERNATIONAL            =>  "0",
            PricingEntity::MERCHANT_ID              =>  $merchantId,
        ];

        $mode = IntlBankTransfer::ACH;
        if(strcasecmp($va_currency, Gateway::SWIFT) === 0)
        {
            $mode = IntlBankTransfer::SWIFT;
        }

        return array_merge($this->fetchDefaultPricing($mode,$merchantId),$defaultStaticPricing);
    }

    private function fetchDefaultPricing($mode,$merchantId): array {
        $defaultPricing = [];
        if($mode === IntlBankTransfer::SWIFT)
        {
            $defaultPricing = array_merge($defaultPricing,$this->fetchSWIFTDefaultPricing());
            $defaultPricing["idempotency_key"] = $merchantId."_".IntlBankTransfer::SWIFT;
        }
        else
        {
            $defaultPricing = array_merge($defaultPricing,$this->fetchACHDefaultPricing());
            $defaultPricing["idempotency_key"] = $merchantId."_".IntlBankTransfer::ACH;
        }

        return $defaultPricing;
    }

    private function fetchACHDefaultPricing() : array {
        $staticPricing = [
            PricingEntity::PAYMENT_NETWORK  => IntlBankTransfer::ACH,
            PricingEntity::PERCENT_RATE     => 200,
            PricingEntity::FIXED_RATE       => 0,
        ];
        $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_ACH);

        if($defaultPricing == null)
        {
            return $staticPricing;
        }

        return [
            PricingEntity::PAYMENT_NETWORK  => $staticPricing[PricingEntity::PAYMENT_NETWORK],
            PricingEntity::PERCENT_RATE     => $defaultPricing[PricingEntity::PERCENT_RATE]??$staticPricing[PricingEntity::PERCENT_RATE],
            PricingEntity::FIXED_RATE       => $defaultPricing[PricingEntity::FIXED_RATE]??$staticPricing[PricingEntity::FIXED_RATE],
            ];
    }

    private function fetchSWIFTDefaultPricing() : array {
        $staticPricing = [
            PricingEntity::PAYMENT_NETWORK   => IntlBankTransfer::SWIFT,
            PricingEntity::PERCENT_RATE      => 200,
            PricingEntity::FIXED_RATE        => 0,
        ];

        $defaultPricing = ConfigKey::get(ConfigKey::DEFAULT_PRICING_FOR_SWIFT);

        if($defaultPricing == null)
        {
            return $staticPricing;
        }

        return [
            PricingEntity::PAYMENT_NETWORK   => $staticPricing[PricingEntity::PAYMENT_NETWORK ],
            PricingEntity::PERCENT_RATE      => $defaultPricing[PricingEntity::PERCENT_RATE]??$staticPricing[PricingEntity::PERCENT_RATE],
            PricingEntity::FIXED_RATE        => $defaultPricing[PricingEntity::FIXED_RATE]??$staticPricing[PricingEntity::FIXED_RATE],
        ];
    }

    public function settleFundsFromCurrencyCloudCron()
    {
        $this->app['rzp.mode']=Mode::LIVE;

        $payload = [
            'action' => CrossBorderCommonUseCases::INTL_BANK_TRANSFER_SWIFT_SETTLEMENT,
            'mode'   => $this->mode,
        ];

        foreach (Payment\Gateway::INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES as $currency)
        {

            $payload['body'] = [
              'settlement_currency' => $currency,
              'gateway'             => Constants\Entity::CURRENCY_CLOUD,
            ];

            try
            {
                CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);

                $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCHED,[
                    'payload' => $payload,
                ]);
            }
            catch(\Exception $ex)
            {
                $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCH_FAILED,[
                    'payload' => $payload,
                ]);
            }
        }

    }

    public function settlementFromCurrencyCloud($payload)
    {
        $this->app['rzp.mode']=Mode::LIVE;

        $gateway = $payload['gateway'];
        $currency = $payload['settlement_currency'];

        if ($gateway === Constants\Entity::CURRENCY_CLOUD && in_array($currency,Payment\Gateway::INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES))
        {
            try{

                $getBalanceRequest = [
                    "currency" => $currency,
                ];

                $getBalanceResponse = $this->callCurrencyCloudGetBalance($getBalanceRequest);

                if(!isset($getBalanceResponse['data']['amount']) || $getBalanceResponse['data']['amount'] < 1)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,null,[
                        'gateway' => Payment\Gateway::CURRENCY_CLOUD,
                        'data'    => $getBalanceResponse['data'],
                        'action'  => "get_balance",
                    ]);
                }
                $settlementCurrency = Payment\Gateway::getSettlementCurrencyByGateway(Payment\Gateway::CURRENCY_CLOUD, $currency);

                $createPaymentRequest = [
                    'currency'              => $settlementCurrency,
                    'amount'                => $getBalanceResponse['data']['amount'],
                    'reason'                => 'For Settling Money from RZP House account to Merchants',
                    'reference'             => $getBalanceResponse['data']['id'],
                    'beneficiary_id'         => $this->getBeneficiaryIdForCurrency($settlementCurrency),
                    'unique_request_id'     => UniqueIdEntity::generateUniqueId()
                ];

                if($settlementCurrency !== $currency)
                {
                    $createConversionRequest = [
                        'buy_currency'    => $settlementCurrency,
                        'sell_currency'   => $currency,
                        'fixed_side'      => 'sell',
                        'amount'          => $getBalanceResponse['data']['amount'],
                        'term_agreement'  => "true",
                    ];

                    $createConversionResponse = $this->app->mozart->sendMozartRequest('payments',$gateway,'create_conversion',$createConversionRequest);

                    if(!isset($createConversionResponse['data']['client_buy_amount']) || $createConversionResponse['data']['client_buy_amount'] < 1)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,null,[
                            'gateway' => Payment\Gateway::CURRENCY_CLOUD,
                            'data'    => $createConversionResponse['data'],
                            'action'  => "create_conversion",
                        ]);
                    }

                    $createPaymentRequest['conversion_id'] = $createConversionResponse['data']['id'];
                    $createPaymentRequest['amount'] = $createConversionResponse['data']['client_buy_amount'];

                }

                $response = $this->app->mozart->sendMozartRequest('payments',$gateway,'payment_create',$createPaymentRequest);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::B2B_PAYMENTS_SETTLED_WITH_BANKING_PARTNER_FAILED,
                    [
                        'message'               =>  'House Account To Nostro Account Payment Request Failed',
                        'currency'              => $currency,
                        'settlement_currency'   => $settlementCurrency,
                    ]
                );
            }
        }
    }

    protected function fundsArrivedFlowFromCurrencyCloud($input)
    {
        $mii = (new \RZP\Models\Merchant\InternationalIntegration\Repository)->getByIntegrationEntityAndKey(Constants\Entity::CURRENCY_CLOUD,$input['account_id']);

        if(isset($mii)==false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED,null,[
                'txn_id'                => $input['id'],
                'related_entity_id'     => $input['related_entity_id'],
                'account_id'            => $input['account_id']
            ]);
        }

        $merchantId = $mii->getMerchantId();

        $request = [
            'txn_id'     => $input['related_entity_id'],
            'contact_id' => $mii->getReferenceId(),
        ];

        $response = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'get_sender_detail',$request);

        $payment = $this->core->createAndAuthorizePaymentForIntlBankTransfer($response['data'],$merchantId,$input);

        return [
            'success' => 'true',
            'payment_id'=> $payment->getId(),
        ];
    }

    protected function transferCompletedFlowFromCurrencyCloud($input)
    {
        $reason = $input['reason'];

        if(isset($reason) === false || empty($reason) === true){
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED,null,[
                'reason'                => $input['reason'],
            ]);
        }
        $reasonConstants = explode(";",$reason)[0];

        if($reasonConstants === BankTransferConstants::COMMISSION_TRANSFER_REASON)
        {
            return [];
        }

        $payment_id = trim(explode(";",$reason)[1]);

        $payment = $this->repo->payment->findOrFail($payment_id);

        // merchants should add customer billing addresses before payments can be captured
        $addresses = $this->repo->address->fetchAddressesForEntity($payment,
            ['type'=> Address\Type::BILLING_ADDRESS]);

        if ($addresses->isEmpty() === true)
        {
             throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null,
                        [
                            'error_desc' => 'Address not present',
                            'error_code' => 'BAD_REQUEST_ERROR',
                        ]);
        }

        $payment->setGatewayCaptured(true);

        $this->repo->payment->saveOrFail($payment);

        $payment = $this->core->capturePaymentForB2B($input,$payment);
    }

    protected function paymentReleasedFlowFromCurrencyCloud($input)
    {
        $this->trace->info(TraceCode::B2B_PAYMENTS_SETTLED_WITH_BANKING_PARTNER,$input);
    }

    protected function getBeneficiaryIdForCurrency($currency)
    {
        $configValue = strtolower($currency).'_beneficiary_id';
        $beneficiaryId = $this->app['config']->get('gateway.currency_cloud.'.$configValue);

        return $beneficiaryId;
    }

    public function getBalanceForMerchantVA($input, $va_currency)
    {
        $merchantId = $this->merchant->getId();

        if(($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === true) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)))
        {
            $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $merchantId,Constants\Entity::CURRENCY_CLOUD);

            $request = [
                'on_behalf_of' => $mii->getReferenceId(),
                'currency'     => $va_currency
            ];

            $response = $this->callCurrencyCloudGetBalance($request);

            $getBalanceResponse = [
                'amount' => $response['data']['amount'],
                'currency' => $response['data']['currency'],
                'account_id'=> $response['data']['account_id']
            ];

            return $getBalanceResponse;
        }
        else{
            $this->trace->info(TraceCode::FETCH_BALANCE_ON_VA_FAILED,[
                'currency'      => $va_currency,
                'merchant_id'   => $merchantId
            ]);
            throw new \Exception(TraceCode::FETCH_BALANCE_ON_VA_FAILED);
        }
    }

    protected function callCurrencyCloudGetBalance($request)
    {
        $response = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'get_balance',$request);

        return $response;
    }

    protected function getCommissionFeeForPayouts()
    {
        $commissionFee = ConfigKey::get(ConfigKey::COMMISSION_FEE_FOR_CC_MERCHANT_PAYOUT);
        if(isset($commissionFee) === false or empty($commissionFee) === true)
        {
            $commissionFee = BankTransferConstants::COMMISSION_FEE_FOR_CURRENCY_CLOUD_PAYOUT;
        }

        return $commissionFee;
    }

    public function createBeneficiaryForMerchantInCC($input)
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if(($merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled, Beneficiary creation not allowed"
            );
        }

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId,Constants\Entity::CURRENCY_CLOUD);

        try{

            $createBeneficiaryRequest = $this->createRequestBodyForBeneficiaryCreation($input, $mii);

            $createBeneficiaryResponse = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'create_beneficiary',$createBeneficiaryRequest,'v2');

            $this->trace->info(TraceCode::BENEFICIARY_CREATION_SUCCESSFUL,[
                'status' => $createBeneficiaryResponse['data']['status'],
                'beneficiary_id' => $createBeneficiaryResponse['data']['id'],
            ]);

        }catch (\Exception $ex)
        {
            $this->trace->info(TraceCode::BENEFICIARY_CREATION_FAILED,[
                'error_message' => $ex->getMessage()
            ]);
            throw $ex;
        }
        $notes = [
            'beneficiary_id' => $createBeneficiaryResponse['data']['id']
        ];

        // Todo: If notes for currency cloud is used somewhere else then we have to check and set

        $mii->setNotes($notes);
        $this->repo->merchant_international_integrations->saveOrFail($mii);

        return $createBeneficiaryResponse['data'];
    }

    public function getBeneficiaryDetailsForMerchantPayout($input)
    {
        if(isset($input['merchant_id']))
        {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
        }else
        {
            $merchant = $this->merchant;
        }

        $merchantId = $merchant->getId();

        if(($merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId,Constants\Entity::CURRENCY_CLOUD);

        if (isset($mii))
        {
            $notes = $mii->getNotes();
            if (isset($notes['beneficiary_id']))
            {
                $request = [
                    'on_behalf_of'      => $mii->getReferenceId(),
                    'beneficiary_id'    => $notes['beneficiary_id'],
                ];
                $response = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'get_beneficiary',$request,'v2');

                $response['data']['commission_fee'] = $this->getCommissionFeeForPayouts();

                return $response['data'];

            }else{
                return ["status" => "No beneficiary is present"];
            }
        }
        else{
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Beneficiary creation blocked, Please create VA for the merchant"
            );
        }
    }

    public function merchantPayoutFromVAToBeneficiary($input)
    {
        if(($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $merchantId = $this->merchant->getId();

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId,Constants\Entity::CURRENCY_CLOUD);

        $request = [
            'on_behalf_of' => $mii->getReferenceId(),
            'currency'     => $input['currency']
        ];

        $response = $this->callCurrencyCloudGetBalance($request);

        $balanceAmount = ((float)$response['data']['amount']);
        $commissionFee = $this->getCommissionFeeForPayouts();
        $amountToBeDeducted = $input['amount'] + $commissionFee;

        if(($input['amount'] < BankTransferConstants::MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT,
                null,
                sprintf("Minimum payout amount is %s",BankTransferConstants::MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT)
            );
        }

        if ($amountToBeDeducted > $balanceAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
                null,
                sprintf("Payout is not possible with current balance %s",$balanceAmount)
            );
        }

        $this->core->makePayoutAndTransferCommission($input, $mii, $merchantId, $commissionFee);
    }

    protected function createRequestBodyForBeneficiaryCreation($input, $mii)
    {
        $request = [
            'name'                      => $input['name'],
            'bank_account_holder_name'  => $input['bank_account_holder_name'],
            'bank_country'              => $input['bank_country'],
            'currency'                  => $input['currency'],
            'beneficiary_address'       => $input['beneficiary_address'],
            'beneficiary_country'       => $input['beneficiary_country'],
            'account_number'            => $input['account_number'],
            'bank_address'              => $input['bank_address'],
            'bank_name'                 => $input['bank_name'],
            'beneficiary_entity_type'   => $input['beneficiary_entity_type'],
            'beneficiary_company_name'  => $input['beneficiary_company_name'],
            'beneficiary_city'          => $input['beneficiary_city'],
            'bic_swift'                 => $input['bic_swift'],
            'on_behalf_of'              => $mii->getReferenceId(),
            'beneficiary_postcode'      => $input['beneficiary_postcode'],
            'beneficiary_state_or_province' => $input['beneficiary_state_or_province']
        ];

        return $request;
    }

    public function fetchAllPayoutsForIntlVA($input)
    {
        if(($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GLOBAL_ACCOUNT) === false) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_B2B_EXPORT)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED,
                null,
                "Global Bank Account is not enabled"
            );
        }

        $merchantId = $this->merchant->getId();

        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
            $merchantId,Constants\Entity::CURRENCY_CLOUD);

        if(!isset($input['skip']))
        {
            $input['skip'] = 0;
        }

        $fetchPayoutsRequest = [
            'on_behalf_of' => $mii->getReferenceId(),
            'page'         => strval((int)($input['skip']/BankTransferConstants::PAYOUT_ENTRIES_PER_PAGE) + 1 )
        ];

        $fetchPayoutsResponse = $this->app->mozart->sendMozartRequest('payments',Constants\Entity::CURRENCY_CLOUD,'get_payments',$fetchPayoutsRequest,'v2');

        $responseList['payouts'] = [];

        foreach ($fetchPayoutsResponse['data']['body']['payments'] as $payout)
        {
            $finalPayoutResponse = [
                'payout_id'         => $payout['id'],
                'status'            => BankTransferConstants::CURRENCY_CLOUD_PAYOUT_MAPPING_WITH_OUR_STATUS[$payout['status']],
                'amount'            => $payout['amount'],
                'currency'          => $payout['currency'],
                'created_date'      => $payout['payment_date'],
                'beneficiary_id'    => $payout['beneficiary_id'],
                'reason'            => $payout['reason'],
            ];
            array_push($responseList['payouts'],$finalPayoutResponse);
        }

        $responseList['is_last_page'] = 0;

        if($fetchPayoutsResponse['data']['body']['pagination']['next_page'] === -1)
        {
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
        if($this->app['env'] != Environment::TESTING)
        {
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

        $payments =  $this->repo->payment->getPaymentsWithoutReferenceId(Constants\Entity::CURRENCY_CLOUD,
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

        foreach ($payments as $payment)
        {
            try
            {
                $this->triggerEmail($payment, $event);

                $successCount++;
            }
            catch(\Exception $e)
            {
                $traceData = [
                    'input'         => $input ?? "",
                    'payment_id'    => $payment->getId() ?? "",
                    'merchant_id'   => $payment->getMerchantId() ?? "",
                    'error_code'    => $e->getCode() ?? "",
                    'error_message' => $e->getMessage() ?? "",
                ];

                array_push($failureTrace, $traceData);

                $failureCount++;
            }
        }

        $report = [
            'total_payments' => count($payments),
            'success_count'  => $successCount,
            'failure_count'  => $failureCount,
            'failure_trace'  => $failureTrace,
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

    public function cbInvoiceWorkflowCallback($input) {
        try{
            (new Validator)->validateInput("crossBorderInvoiceWorkflowCallback", $input);
        }
        catch (\Throwable $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILED,null,[
                'error_description' => $e->getMessage(),
                'error_code'        => $e->getCode(),
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

}
