<?php

namespace RZP\Models\BankTransfer;

use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Account;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\Tracer;
use Symfony\Component\HttpFoundation\File\File;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Batch;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\QrPayment;
use RZP\Models\BankAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Models\BankTransferHistory;
use RZP\Models\BankTransferRequest;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Metric;
use RZP\Models\VirtualAccount\Provider;
use RZP\Reconciliator\RequestProcessor;
use RZP\Jobs\BankTransferCreateProcess;
use RZP\Models\BankTransfer\Processor as BankTransferProcessor;

class Service extends Base\Service
{
    protected $validator;
    protected $provider;
    protected $ip;
    protected $mutex;
    protected $core;

    // Seconds in 15 minutes
    const FIFTEEN_MINUTES = 900;

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

    public function saveRequestAndProcess(
        array $input,
        string $provider = null,
        bool $checkForIfsc = false,
        $requestPayload = null
    )
    {
        $response = $this->validateDuplicateRequest($input);

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
                $requestPayload ?? $input
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

    private function validateDuplicateRequest(array $input)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

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

    public function createBankTransferViaLedgerCronJob(array $blacklistIds, array $forcedMerchantIds, int $limit)
    {
        $this->core->createBankTransferViaLedgerCronJob($blacklistIds, $forcedMerchantIds, $limit);
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
}
