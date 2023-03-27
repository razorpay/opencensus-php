<?php

namespace RZP\Models\SubscriptionRegistration;

use View;
use Queue;
use RZP\Constants;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Http\BasicAuth;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Http\RequestHeader;
use RZP\Constants\HyperTrace;
use RZP\Models\Customer\Token;
use RZP\Error\PublicErrorDescription;
use RZP\Jobs\TokenRegistrationAutoCharge;
use RZP\Models\PaperMandate\FileUploader;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Models\PaperMandate\Entity as PaperMandateEntity;
use RZP\Models\PaperMandate\PaperMandateUpload\Entity as PaperMandateUploadEntity;
use RZP\Models\PaperMandate\PaperMandateUpload\Status as PaperMandateUploadStatus;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function listTokens(array $input): array
    {
        (new Validator)->validateInput(__FUNCTION__, $input);

        $customerFetchInput = [];

        if (empty($input[Entity::CUSTOMER_CONTACT]) === false)
        {
            $customerFetchInput[CustomerEntity::CONTACT] = array_pull($input, Entity::CUSTOMER_CONTACT);
        }

        if (empty($input[Entity::CUSTOMER_EMAIL]) === false)
        {
            $customerFetchInput[CustomerEntity::EMAIL] = array_pull($input, Entity::CUSTOMER_EMAIL);
        }

        if (empty($customerFetchInput) === false)
        {
            $customers = $this->repo->customer->fetch($customerFetchInput, $this->merchant->getId());

            if ($customers->count() === 0)
            {
                return (new Base\PublicCollection)->toArrayPublic();
            }

            $input[Token\Entity::CUSTOMER_ID] = $customers->getIds();
        }

        if (empty($input[Entity::PAYMENT_ID]) === false)
        {
            $paymentId = array_pull($input, Entity::PAYMENT_ID);

            try
            {
                $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId,$this->merchant);

                $input[Token\Entity::ID] = $payment->getTokenId();
            }
            catch (Exception\BadRequestException $e)
            {
                return (new Base\PublicCollection)->toArrayPublic();
            }
        }

       /* $result = $this->repo->subscription_registration->fetchRecurringTokensByMerchant(
            $this->merchant,
            $input);*/

        $result= $this->repo->useSlave(function() use ($input) {
            return $this->repo->subscription_registration->fetchRecurringTokensByMerchant(
                $this->merchant,
                $input);
            });
        return $result->toArrayPublic();
    }

    public function listAuthLinks(array $input): array
    {
        $invoices = $this->repo->invoice->fetchForEntityType(
            $input,
            $this->merchant->getId(),
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        return $invoices->toArrayPublic();
    }

    public function createAuthLink(array $input): array
    {
        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

        if ($batchId !== null)
        {
            $this->trace->info(TraceCode::AUTH_LINK_BATCH_INPUT,
                [
                    'input'    => $input,
                    'batch_id' => $batchId,
                ]);
        }

        $invoice = $this->core->createAuthLink($input, $this->merchant,null, null, $batchId);

        return $invoice->toArrayPublic();
    }

    /*
     * PAYAPPS-1490
     */
    public function migrateNach(array $input): array
    {
        $this->trace->count(Metric::AUTH_LINK_MIGRATION_STARTED, ['mode' => $this->mode]);

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

        if ($batchId !== null)
        {
            $this->trace->info(TraceCode::AUTH_LINK_BATCH_INPUT,
                [
                    'input'    => $input,
                    'batch_id' => $batchId,
                ]);
        }

        try {
            $emandatePaymentMethodEnabled = $input['emandate_payment_enabled'];
            $nachPaymentMethodEnabled     = $input['nach_payment_enabled'];

            $this->makeMigrateNachPayload($input);

            $data     = $this->core->migrateNach($input, $batchId);
            $response = $data->toArrayPublic();

            $response['emandate_payment_enabled'] = $emandatePaymentMethodEnabled;
            $response['nach_payment_enabled']     = $nachPaymentMethodEnabled;

        } catch (Exception\BadRequestException | Exception\BadRequestValidationFailureException $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST,
                [
                    'batchID' => $batchId,
                    'mode'    => $this->mode
                ]
            );
            throw $ex;
        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST,
                [
                    'batchID' => $batchId,
                    'mode'    => $this->mode
                ]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Generic Error : Parameter Missing: please provide correct input',
                "",
                $ex->getMessage()
            );
        }

        $this->trace->count(Metric::AUTH_LINK_MIGRATION_COMPLETED, ['mode' => $this->mode]);

        return $response;
    }

    private function makeMigrateNachPayload(array &$input): void
    {
        $emandateTerminalEnabled      = $input['emandate_terminal_enabled'];
        $nachTerminalEnabled          = $input['nach_terminal_enabled'];
        $merchantId                   = $input['merchant_id'];

        // Removing case sensitivity
        $input[Token\Entity::ACCOUNT_TYPE]  = strtolower($input[Token\Entity::ACCOUNT_TYPE]);
        $input[Token\Entity::METHOD]        = strtolower($input[Token\Entity::METHOD]);
        $input[Token\Entity::BANK]          = strtoupper($input[Token\Entity::BANK]);
        $input[Token\Entity::IFSC]          = strtoupper($input[Token\Entity::IFSC]);
        $input[Token\Entity::GATEWAY_TOKEN] = strtoupper($input[Token\Entity::GATEWAY_TOKEN]);

        $input[Token\Entity::DEBIT_TYPE] = isset($input[Token\Entity::DEBIT_TYPE]) ?
                                                strtolower($input[Token\Entity::DEBIT_TYPE]) :
                                                'max_amount';

        $input[Token\Entity::FREQUENCY] = isset($input[Token\Entity::FREQUENCY]) ?
                                                strtolower($input[Token\Entity::FREQUENCY]) :
                                                'adhoc';

        // validate gateway_token
        $accept_new_axis_umrn_mandate_migration = (new Merchant\Core)->isRazorxExperimentEnable($merchantId,
                                Merchant\RazorxTreatment::ACCEPT_NEW_AXIS_UMRN_MANDATE_MIGRATION);

        if ((ctype_alnum($input[Token\Entity::GATEWAY_TOKEN]) === false) or
            ((strlen($input[Token\Entity::GATEWAY_TOKEN]) !== 20) and
                (strlen($input[Token\Entity::GATEWAY_TOKEN]) !== 10) and
                ((strlen($input[Token\Entity::GATEWAY_TOKEN]) !== 15) and
              ($accept_new_axis_umrn_mandate_migration === false ))))
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_UMRN
            );
        }

        if (($input[Token\Entity::METHOD] === Payment\Method::NACH and $nachTerminalEnabled === false) or
            ($input[Token\Entity::METHOD] === Payment\Method::EMANDATE and $emandateTerminalEnabled === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Terminal is not present/enabled for the payment method"
            );
        }

        unset($input['emandate_payment_enabled'], $input['nach_payment_enabled'],
              $input['emandate_terminal_enabled'], $input['nach_terminal_enabled']);

        // These will be handled by the caller (Batch Service)
        // Here, just for fail safety
        if (isset($input[Token\Entity::EXPIRED_AT]) === false or empty($input[Token\Entity::EXPIRED_AT]) === true)
        {
            $input[Token\Entity::EXPIRED_AT] = Base\ExtendedValidations::EPOCH_DEFAULT_MAX;
        }

        if (isset($input["customer"]) === false or empty($input["customer"][CustomerEntity::EMAIL]) === true or
            empty($input["customer"][CustomerEntity::NAME]) === true or
            empty($input["customer"][CustomerEntity::CONTACT]) === true)
        {

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_NOT_FOUND,
                "customer", "", "Customer Details not provided");
        }

        $input[Token\Entity::BENEFICIARY_NAME] = $input["customer"][CustomerEntity::NAME];

        $input["subscription_registration"] = [
            Entity::METHOD       => $input[Token\Entity::METHOD],
            Entity::MAX_AMOUNT   => $input[Token\Entity::MAX_AMOUNT],
            Entity::EXPIRE_AT    => $input[Token\Entity::EXPIRED_AT],
            Entity::AUTH_TYPE    => Payment\AuthType::MIGRATED,
            Entity::BANK_ACCOUNT => [
                BankAccount\Entity::BANK_NAME          => $input[Token\Entity::BANK],
                BankAccount\Entity::ACCOUNT_NUMBER     => $input[Token\Entity::ACCOUNT_NUMBER],
                BankAccount\Entity::IFSC_CODE          => $input[Token\Entity::IFSC],
                BankAccount\Entity::BENEFICIARY_NAME   => $input["customer"][CustomerEntity::NAME],
                BankAccount\Entity::BENEFICIARY_EMAIL  => $input["customer"][CustomerEntity::EMAIL],
                BankAccount\Entity::BENEFICIARY_MOBILE => $input["customer"][CustomerEntity::CONTACT],
                BankAccount\Entity::ACCOUNT_TYPE       => $input[Token\Entity::ACCOUNT_TYPE],
            ],
        ];

    }

    public function fetchAuthLink(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            $input,
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        return (new ViewDataSerializer($invoice))->serializeForApi();
    }

    public function fetchAuthLinkInternal(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            $input,
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $data = (new ViewDataSerializer($invoice))->serializeForApiInternal();

        return $data;
    }

    public function fetchToken(String $id, array $input): array
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return (new Token\ViewDataSerializer($token))->serializeForSubscriptionRegistration();
    }

    public function deleteToken(String $id): array
    {
        return $this->core->deleteToken($id, $this->merchant);
    }

    public function chargeToken(String $id, array $input, String $idempotent_key=null): array
    {
        $this->trace->count(Metric::AUTH_LINK_CHARGE_TOKEN_INITIATED, ['mode' => $this->mode]);

        $route = $this->app['api.route']->getCurrentRouteName();

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

        if ($route === 'subscription_registration_charge_token_bulk'){
            $rowIdempotentId = $idempotent_key ?? null;
        } else {
            $rowIdempotentId = $this->app['request']->header(RequestHeader::X_Batch_Row_Id) ?? null;
        }

        if ($batchId !== null)
        {
            $this->trace->info(TraceCode::AUTH_LINK_BATCH_INPUT,
                [
                    'token_id' => $id,
                    'input'    => $input,
                    'batch_id' => $batchId,
                    'row_id'   => $rowIdempotentId,
                ]);
        }

        $response = Tracer::inSpan(['name' => HyperTrace::SUBSCRIPTION_REGISTRATION_CHARGE_TOKEN_CORE], function () use ($id,
            $input, $batchId, $rowIdempotentId)
        {
            return $this->core->chargeToken($id, $input, $this->merchant, $batchId, $rowIdempotentId);
        });

        if($this->auth->getInternalApp() === "batch")
        {
            $this->setOrderId($response);
        }

        $this->trace->count(Metric::AUTH_LINK_CHARGE_TOKEN_SUBMITTED, ['mode' => $this->mode]);

        return $response;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function chargeTokenBulk(array $input)
    {
        $chargeTokenBatch = new Base\PublicCollection();

        foreach ($input as $item)
        {
            try
            {
                $idempotency_key = isset($item['idempotency_key']) ? $item['idempotency_key'] : null;

                $token_id = isset($item['token']) ? $item['token'] : null;

                $response = $this->chargeToken($token_id, $item, $idempotency_key);

                $successResponseMap = [
                    'idempotency_key' => $idempotency_key,
                ];

                $response = array_merge($response, $successResponseMap);

                $chargeTokenBatch->push($response);

            }
            catch (Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::INFO,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
                $exceptionData = [
                    'idempotency_key' => $idempotency_key,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $chargeTokenBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    'idempotency_key' => $idempotency_key,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $chargeTokenBatch->push($exceptionData);
            }
        }

        return $chargeTokenBatch->toArrayWithItems();
    }

    public function processAutoCharges(array $input)
    {
        $validator = new Validator();

        $validator->validateInput('autocharge', $input);

        $count = $input['count'] ?? 100;

        $merchantIds = $input['merchant_ids'] ?? [];

        $tokenRegistrationsToCharge = $this->repo->subscription_registration->getTokenRegistrationsForFirstCharge($merchantIds, $count);

        $tokenRegistrationsPicked = [];

        foreach ($tokenRegistrationsToCharge as $tokr)
        {
            try
            {
                $autoChargeJob = new TokenRegistrationAutoCharge($this->mode, $tokr);

                Queue::push($autoChargeJob);

                $this->trace->info(TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_JOB_INITIATED,
                    [
                       'id'   => $tokr->getId(),
                       'mode' => $this->mode
                    ]);
                array_push($tokenRegistrationsPicked, $tokr->getId());
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_QUEUE_FAILED,
                    [
                        'token.registration_id'  => $tokr->getId(),
                        'mode'                   => $this->mode
                    ]
                );
            }
        }

        return $tokenRegistrationsPicked;
    }

    public function associateToken(string $id, array $input)
    {
        $tokenRegistration = $this->repo->subscription_registration->findByPublicId($id);

        $validator = $tokenRegistration->getValidator();

        $validator->validateInput('associate_token', $input);

        $validator->validateTokenRegistrationToAssociate();

        $token = $this->repo->token->findByPublicId($input[Entity::TOKEN_ID]);

        $this->core->associateToken($tokenRegistration, $token);

        return $tokenRegistration->toArrayAdmin();
    }

    public function authenticateTokens(array $input)
    {
        $validator = new Validator;

        $validator->validateInput('authenticate_tokens', $input);

        $subscriptionRegistrations = [];

        $failed = [];

        foreach ($input[Entity::IDS] as $id)
        {
            try
            {
                $validator->validateInput('public_id', ['id' => $id]);

                $subscriptionRegistration = $this->authenticateToken($id);

                array_push($subscriptionRegistrations, $subscriptionRegistration[Entity::ID]);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                array_push($failed, $id);
            }
        }

        return [
            'failed'    => $failed,
            'succeeded' => $subscriptionRegistrations,
        ];
    }

    /*
     * Compare the fields that are not matching and return the mismatched details
     */
    public function fetchPaperMandateUpload(array $input): array
    {
        $data = [];

        try
        {
            $invoiceId = $input[Entity::AUTH_LINK_ID];

            $invoice = $this->repo
                            ->invoice
                            ->findByPublicId($invoiceId);

            (new Validator)->validateInvoiceCreatedForTokenRegistration($invoice);

            $subscriptionRegistration = $invoice->tokenRegistration;

            $paperMandate = $subscriptionRegistration->paperMandate;

            $paperMandateUploads =
                $this->repo->paper_mandate_upload->findLatestByMandateId($paperMandate->getId())->toArrayAdmin();

            if ($paperMandateUploads['count'] === 0)
            {
                return [
                    'comments'  => 'No uploads found',
                    'canManual' => false
                ];
            }

            $paperMandateUploads = $paperMandateUploads['items'][0];

            if ($paperMandateUploads[PaperMandateUploadEntity::STATUS] !== PaperMandateUploadStatus::REJECTED)
            {
                return [
                    'status'    => $paperMandateUploads[PaperMandateUploadEntity::STATUS],
                    'comments'  => $paperMandateUploads[PaperMandateUploadEntity::STATUS_REASON] ?? 'No Reason found',
                    'canManual' => false
                ];
            }

            if (($paperMandateUploads[PaperMandateUploadEntity::NOT_MATCHING] === null) or
                (empty($paperMandateUploads[PaperMandateUploadEntity::NOT_MATCHING]) === true))
            {
                return [
                    'status'    => $paperMandateUploads[PaperMandateUploadEntity::STATUS],
                    'comments'  => $paperMandateUploads[PaperMandateUploadEntity::STATUS_REASON] ?? 'No Reason found',
                    'canManual' => false
                ];
            }

            $mismatchArr = [];
            foreach ($paperMandateUploads[PaperMandateUploadEntity::NOT_MATCHING] as $key => $val)
            {
                $mismatchArr = array_merge($mismatchArr, [$val => $paperMandateUploads[$val]]);
            }

            $imageUri = (new FileUploader($paperMandate))
                            ->getSignedShortUrl($paperMandateUploads[PaperMandateUploadEntity::ENHANCED_FILE_ID]);

            $data = [
                'auth_link_id'     => $invoiceId,
                'paper_mandate_id' => $paperMandate->getId(),
                'enhanced_file_id' => $paperMandateUploads[PaperMandateUploadEntity::ENHANCED_FILE_ID],
                'status'           => $paperMandateUploads[PaperMandateUploadEntity::STATUS],
                'canManual'        => true,
                'mismatch'         => $mismatchArr,
                'imageUri'         => $imageUri,
                'ideal'            => [
                    PaperMandateEntity::UTILITY_CODE         => $paperMandate->getUtilityCode(),
                    PaperMandateEntity::AMOUNT               => $paperMandate->getAmount(),
                    PaperMandateEntity::SPONSOR_BANK_CODE    => strtoupper($paperMandate->getSponsorBankCode()),
                    PaperMandateEntity::FORM_CHECKSUM        => $paperMandate->getFormChecksum(),
                    PaperMandateUploadEntity::ACCOUNT_NUMBER => $paperMandate->bankAccount->getAccountNumber(),
                    PaperMandateUploadEntity::IFSC_CODE      => $paperMandate->bankAccount->getIfscCode(),
                    PaperMandateUploadEntity::ACCOUNT_TYPE   => $paperMandate->bankAccount->getAccountType(),
                ]
            ];

        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_MANUAL_SUBMIT_FAILED,
                [
                    'invoice_id' => $input[Entity::AUTH_LINK_ID],
                ]
            );

            throw $ex;
        }

        return $data;
    }

    /*
     * Manually approve paper mandate upload and create payment
     * Only to be called from admin dashboard. Don't call from other without auth changes
     */
    public function approvePaperMandateIssues(array $input): array
    {
        try
        {
            $invoiceId        = $input[Entity::AUTH_LINK_ID];
            $enhancedImgId    = $input[PaperMandateUploadEntity::ENHANCED_FILE_ID];
            $approverEmail    = $input['approver_email_id'];
            $approverComments = $input['notes'];

            $invoice = $this->repo
                            ->invoice
                            ->findByPublicId($invoiceId);

            (new Validator)->validateInvoiceCreatedForTokenRegistration($invoice);

            $subscriptionRegistration = $invoice->tokenRegistration;

            $paperMandate = $subscriptionRegistration->paperMandate;

            // recheck again as UI may hold for minutes before submitting
            $paperMandateUploads =
                $this->repo->paper_mandate_upload->findLatestByMandateId($paperMandate->getId())->toArrayAdmin();

            $paperMandateUploads = $paperMandateUploads['items'][0];

            if ($paperMandateUploads[PaperMandateUploadEntity::STATUS] !== PaperMandateUploadStatus::REJECTED)
            {
                return [
                    'status'    => $paperMandateUploads[PaperMandateUploadEntity::STATUS],
                    'comments'  => $paperMandateUploads[PaperMandateUploadEntity::STATUS_REASON],
                    'canManual' => false
                ];
            }

            $paperMandateUpload = $this->repo
                                        ->paper_mandate_upload
                                        ->findByPublicId($paperMandateUploads[PaperMandateUploadEntity::ID]);

            $paperMandateUpload->setStatus(PaperMandateUploadStatus::ACCEPTED);
            $paperMandateUpload->setStatusReason('Approved by ' . $approverEmail . ' '  . $approverComments);
            $this->repo->paper_mandate_upload->saveOrFail($paperMandateUpload);

            // save paper mandate uploaded file id
            $paperMandate->setUploadedFileId($enhancedImgId);

            $this->repo->paper_mandate->saveOrFail($paperMandate);

            // this route is called from admin
            $this->merchant = $this->repo->merchant->findOrFail($paperMandateUpload->merchant->getId());;
            $this->app['basicauth']->setMerchant( $this->merchant);

            // create payment
            $this->createPaymentForPaperMandate($input);

        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_MANUAL_SUBMIT_FAILED,
                [
                    'invoice_id' => $input[Entity::AUTH_LINK_ID],
                ]
            );

            throw $ex;
        }

        return ['status' => 'success'];
    }

    public function paperMandateAuthenticate(array $input): array
    {
        $validator = new Validator;

        $validator->validatePaperMandateAuthenticateInput($input);

        $subscriptionRegistration = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            throw new Exception\LogicException(
                'should not have reached here'
            );
        }

        $validator->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);

        return $this->core->paperMandateAuthenticate($subscriptionRegistration, $input);
    }

    public function paperMandateAuthenticateProxy(array $input): array
    {
        $data = $this->paperMandateAuthenticate($input);

        if ($data[SubscriptionRegistrationConstants::SUCCESS] === true)
        {
            $data[SubscriptionRegistrationConstants::PAYMENT_RESPONSE] = $this->createPaymentForPaperMandate($input);
        }

        return $data;
    }

    protected function createPaymentForPaperMandate(array $input)
    {
        $this->trace->info(TraceCode::NACH_REGISTER_PAYMENT_CREATION,
            [
                'order_id'   => $input[Entity::ORDER_ID] ?? '',
                'invoice_id' => $input[Entity::AUTH_LINK_ID] ?? '',
                'mode'       => $this->mode
            ]);

        $paymentInput = [];

        $orderId = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $orderId = $input[Entity::ORDER_ID];
        }
        else
        {
            $invoiceId = $input[Entity::AUTH_LINK_ID];

            $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $this->merchant);

            $orderId = Order\Entity::getSignedId($invoice->getOrderId());
        }

        try
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($orderId);

            $paperMandate = $subscriptionRegistration->paperMandate;

            $customer = $paperMandate->customer;

            $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            $paymentInput[Payment\Entity::AMOUNT]      = $order->getAmount();

            $paymentInput[Payment\Entity::CURRENCY]    = $order->getCurrency();

            $paymentInput[Payment\Entity::METHOD]      = $order->getMethod();

            $paymentInput[Payment\Entity::RECURRING]   = true;

            $paymentInput[Payment\Entity::ORDER_ID]    = $orderId;

            $paymentInput[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();

            $paymentInput[Payment\Entity::CONTACT]     = $customer->getContact();

            $paymentInput[Payment\Entity::EMAIL]       = $customer->getEmail();

            $paymentInput[Payment\Entity::AUTH_TYPE]   = $subscriptionRegistration->getAuthType();

            $this->app['basicauth']->setBasicType(BasicAuth\Type::PUBLIC_AUTH);

            $key = $this->repo->key->getFirstActiveKeyForMerchant($this->merchant->getId());

            if (empty($key) === false)
            {
                $publicKey = $key->getPublicKey($this->mode);

                $this->app['basicauth']->setAuthDetailsUsingPublicKey($publicKey);
            }

            $paymentService = new Payment\Service();

            $payment = $paymentService->process($paymentInput);
        }
        catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_REGISTER_PAYMENT_CREATION_ERROR,
                [
                    'order_id' => $orderId,
                    'mode'     => $this->mode
                ]
            );
            throw $ex;
        }

        return $payment;
    }

    public function paperMandateValidate(array $input): array
    {
        $validator = new Validator;

        $validator->validatePaperMandateAuthenticateInput($input);

        $subscriptionRegistration = null;

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            throw new Exception\LogicException(
                'should not have reached here'
            );
        }

        $validator->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);

        return $this->core->paperMandateValidate($subscriptionRegistration, $input)->toArrayPublic();
    }

    public function getUploadedPaperMandateForm(array $input)
    {
        (new Validator)->validateGetUploadedPaperMandateForm($input);

        if (empty($input[Entity::ORDER_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForOrder($input[Entity::ORDER_ID]);
        }
        else if (empty($input[Entity::AUTH_LINK_ID]) === false)
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForInvoice($input[Entity::AUTH_LINK_ID]);
        }
        else
        {
            $subscriptionRegistration = $this->getSubscriptionRegistrationForToken($input[Entity::TOKEN_ID]);
        }

        $paperMandate = $subscriptionRegistration->paperMandate;

        return [
            SubscriptionRegistrationConstants::URL => $paperMandate->getUploadedFormUrl()
        ];
    }

    public function retryPaperMandateToken($tokenId)
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($tokenId, $this->merchant);

        $subscriptionRegistration = $this->getSubscriptionRegistrationForToken($tokenId);

        $subscriptionRegistration->getValidator()->validateTokenToRetry($token);

        $payments = $this->repo->payment->getPaymentCountByToken($token->getId())->get();

        if (count($payments) !== 1)
        {
            throw new Exception\LogicException(
                'exactly one payment should have been created for the given token',
                null,
                [
                    'token_id'       => $token->getId(),
                    'payments_count' => count($payments),
                ]
            );
        }

        $payment = $payments->get(0);

        $order = $payment->order;

        if ($order === null)
        {
            throw new Exception\LogicException(
                'order can\'t be null for nach method for payment',
                null,
                [
                    'token_id'       => $token->getId(),
                    'payment_id'     => $payment->getId(),
                ]
            );
        }

        return $this->createPaymentForPaperMandate([Entity::ORDER_ID => $order->getPublicId()]);
    }

    public function nachRegisterTestPaymentAuthorizeOrFail(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($id, $this->merchant);

        $subscriptionRegistration = $invoice->entity;

        if (($subscriptionRegistration === null) or
            ($invoice->getEntityType() !== Constants\Entity::SUBSCRIPTION_REGISTRATION))
        {
            throw new Exception\BadRequestValidationFailureException(
                'id provided does not exist'
            );
        }

        $subscriptionRegistration->getValidator()->validateNachRegisterTestPaymentAuthorizeOrFail();

        (new Validator)->validateInput('nach_register_test_payment', $input);

        return $this->core->nachRegisterTestPaymentAuthorizeOrFail($subscriptionRegistration, $input);
    }

    protected function getSubscriptionRegistrationForToken(string $tokenId)
    {
        $tokenId = Token\Entity::stripDefaultSign($tokenId);

        $subscriptionRegistration = $this->repo
                                         ->subscription_registration
                                         ->findByTokenIdAndMerchant($tokenId, $this->merchant->getId());

        if ($subscriptionRegistration === null)
        {
            throw new Exception\BadRequestValidationFailureException("The id provided does not exist");
        }

        return $subscriptionRegistration;
    }

    protected function getSubscriptionRegistrationForOrder(string $orderId)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        (new Validator)->validateOrderCreatedForTokenRegistration($order);

        $invoice = $order->invoice;

        return $this->getSubscriptionRegistrationForInvoice($invoice->getPublicId());
    }

    protected function getSubscriptionRegistrationForInvoice(string $invoiceId)
    {
        $invoice = $this->repo
                        ->invoice
                        ->findByPublicIdAndMerchant($invoiceId, $this->merchant);

        (new Validator)->validateInvoiceCreatedForTokenRegistration($invoice);

        $subscriptionRegistration = $invoice->tokenRegistration;

        return $subscriptionRegistration;
    }

    protected function authenticateToken(string $id)
    {
        $tokenRegistration = $this->repo->subscription_registration->findByPublicId($id);

        $tokenRegistration->getValidator()->validateTokenRegistrationToAuthenticate();

        $token = $tokenRegistration->token;

        $this->core->authenticate($tokenRegistration, $token);

        return $tokenRegistration->toArrayAdmin();
    }

    public function sendNotification(string $id, string $medium): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            [],
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $invoice->setRelation('entity', $invoice->entity);

        $order = $invoice->order;

        $order->getValidator()->validateOrderNotPaid();

        $data = (new Invoice\Core())->sendNotification($invoice, $medium);

        return $data;
    }

    public function cancelAuthLink(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            null,
            null,
            [],
            Constants\Entity::SUBSCRIPTION_REGISTRATION
        );

        $order = $invoice->order;

        $order->getValidator()->validateOrderNotPaid();

        $invoice = (new Invoice\Core())->cancelInvoice($invoice);

        return (new ViewDataSerializer($invoice))->serializeForApi();
    }

    protected function setOrderId(& $response)
    {
        $paymentId = $response['razorpay_payment_id'];

        if (empty($paymentId) === true)
        {
            return;
        }

        $paymentId = Payment\Entity::stripDefaultSign($paymentId);

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        $orderId = $payment->getApiOrderId();

        $response['order_id'] = Order\Entity::getSignedId($orderId);
    }

    public function downloadNach($invoiceId)
    {
        $invoice = $this->repo->invoice->findByPublicId($invoiceId);

        (new Validator)->validateInvoiceCreatedForTokenRegistration($invoice);

        $subscriptionRegistration = $invoice->tokenRegistration;

        $paperMandate = $subscriptionRegistration->paperMandate;

        $imageUri = $paperMandate->getGeneratedFormUrlTransient();

        $TEMPLATE_FILE_NAME = 'auth_link.pnach_form';

        return View::make($TEMPLATE_FILE_NAME, [ 'image_uri' => $imageUri, ]);
    }
}
