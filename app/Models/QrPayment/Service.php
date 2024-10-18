<?php

namespace RZP\Models\QrPayment;

use Monolog\Level;
use Illuminate\Support\Facades\Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\BharatQr;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Models\BankTransfer;
use RZP\Constants\HyperTrace;
use RZP\Models\QrPaymentRequest;
use RZP\Exception\LogicException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\QrGatewayModule\QrGatewayModule;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrV2;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status as QrV2Status;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Service as QrV2Service;

class Service extends Base\Service
{
    const UNPROCESSED_RESPONSE = 'unprocessed';
    const RAZORPAY_PAYMENT_ID  = 'razorpay_payment_id';

    public static function getCacheKeyForQRCodeId(string $qrCodeId): string
    {
        QrV2::verifyIdAndSilentlyStripSign($qrCodeId);

        return 'payment:qr_code.polling.' . $qrCodeId . '.status';
    }

    public function fetchPaymentsForQrCode($input, $id)
    {
        $input[Entity::QR_CODE_ID] = $id;

        $payments = $this->fetchMultiplePayments($input);

        try
        {
            if (count($payments['items']) === 0)
            {
                (new QrV2Service())->triggerQrStatusCheckForPaymentFetch($id);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QR_STATUS_CHECK_EXCEPTION_IN_FETCH_PAYMENTS_FLOW,
                [
                    'input' => $input,
                    'id'    => $id,
                ]
            );
        }

        return $payments;
    }

    public function fetchMultiplePayments($input)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if (($routeName === 'qr_payment_fetch_for_qr_code') and
            ($this->merchant->getId() !== null))
        {
            $this->trace->info(TraceCode::QR_CODE_FETCH_PAYMENT_FROM_DB_REQUEST,
                               [
                                   'input'       => $input,
                                   'merchant_id' => $this->merchant->getId(),
                               ]);

            $this->repo->qr_payment->setMerchantIdRequiredForMultipleFetch(false);

            $paymentIds = $this->repo->qr_payment->fetch($input)->pluck(Entity::PAYMENT_ID)->toArray();

            return $this->repo->payment->getPaymentsSortedByCreatedAt($paymentIds)->toArrayPublic();
        }
        else
        {
            (new Fetch)->processFetchParams($input);

            $qrPaymentIds = (new EsRepository('qr_payment'))->buildQueryAndSearch($input, $this->merchant->getId());

            $qrPaymentIds = array_map(
                function($res) {
                    return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
                },
                $qrPaymentIds[ES::HITS][ES::HITS]);
        }

        return Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_MULTIPLE_PAYMENTS], function () use ($qrPaymentIds) {
            return $this->fetchPaymentsForQrPaymentIds($qrPaymentIds);
        });

    }

    public function fetchPaymentsForQrPaymentIds(array $qrPaymentIds)
    {
        $paymentIds = $this->repo->qr_payment->getPaymentIdsForQrPaymentIds($qrPaymentIds);

        return $this->repo->payment->getPaymentsSortedByCreatedAt($paymentIds)->toArrayPublic();
    }

    /**
     * @param array       $input
     * @param string|null $provider
     * @param             $requestPayload
     * @param             $bankAccount
     */
    public function processBankTransfer(array $input, $provider, $requestPayload, $bankAccount)
    {
        try
        {
            (new BankTransfer\Validator())->validateInput('create' ,$input);

            $gatewayResponse = $this->modifyBankTransferInput($input, $bankAccount, $provider, $requestPayload);

            $qrPaymentRequest = (new QrPaymentRequest\Service())->create($gatewayResponse, Type::BHARAT_QR);

            $terminal = (new Payment\Processor\TerminalProcessor())->getTerminalForQrBankTransfer($bankAccount, $provider);

            $valid = (new Core)->processPayment($gatewayResponse, $terminal, $qrPaymentRequest);
        }
        catch (\Exception $ex)
        {
            $valid = false;

            $this->trace->traceException($ex);
        }

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $input['transaction_id'] ?? '',
        ];
    }

    private function modifyBankTransferInput(array $modifiedInput, $bankAccount, $provider, $requestPayload)
    {
        $qrCodeId = $bankAccount->qrCode->getId();

        $gatewayResponse['callback_data'] = $modifiedInput;

        $gatewayResponse['original_callback_data'] = $requestPayload;

        $amount = (int) number_format(($modifiedInput['amount'] * 100), 0, '.', '');

        $gatewayResponse['qr_data'] = [
            BharatQr\GatewayResponseParams::AMOUNT                => $amount,
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::BANK_TRANSFER,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $qrCodeId,
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $modifiedInput[BankTransfer\Entity::REQ_UTR],
            BharatQr\GatewayResponseParams::GATEWAY               => Payment\Gateway::$bankTransferProviderGateway[$provider],
        ];

        return $gatewayResponse;
    }

    public function fetchPaymentStatusByQrCodeId(string $qrCodeId)
    {
        [$qrCodeStatus, $paymentId] = $this->getQrCodeStatusAndPaymentIdFromQRCodeId($qrCodeId);

        if ($paymentId === '')
        {
            if ($qrCodeStatus === QrV2Status::CLOSED) {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED);
            }

            return [Payment\Entity::STATUS => self::UNPROCESSED_RESPONSE];
        }

        return (new Payment\Service())->fetchStatus(Payment\Entity::getSignedId($paymentId));
    }

    public function isPaymentSuccessful(?Payment\Entity $payment = null): bool
    {
        return $payment &&
            in_array(
                $payment->getStatus(),
                [Payment\Status::CAPTURED, Payment\Status::AUTHORIZED],
                true
            );
    }

    public function setQrCodeStatusAndPaymentIdInCache(QrV2 $qrCode, ?Payment\Entity $payment = null): void
    {
        $paymentId = $payment ? $payment->getId() : '';

        $paymentStatus = '';

        $ttl = Constants::CREATED_STATUS_TTL;

        if ($this->isPaymentSuccessful($payment)) {
            $paymentStatus = $payment->getStatus();

            $ttl = Constants::SUCCESS_STATUS_TTL;
        }

        $this->putQrCodeAndPaymentStatusInCache($qrCode->getId(), $qrCode->getStatus(), $paymentId, $paymentStatus, $ttl);
    }

    /**
     * @param string $cacheValue
     *
     * @return string[]
     */
    private function getQrCodeStatusAndPaymentIdFromCacheValue(string $cacheValue): array
    {
        [$qrCodeStatus, $paymentId, $paymentStatus] = explode(Constants::CACHE_VALUE_SEPARATOR, $cacheValue, 1000);

        return [$qrCodeStatus, $paymentId];
    }

    /**
     * @param string $qrCodeId
     * @return string[]
     */
    private function getQrCodeStatusAndPaymentIdFromQRCodeId(string $qrCodeId): array
    {
        $key = self::getCacheKeyForQRCodeId($qrCodeId);

        $cacheValue = Cache::get($key);

        if ($cacheValue === null) {
            return $this->handleIfCacheExpired($qrCodeId, $this->merchant->getId());
        }

        return $this->getQrCodeStatusAndPaymentIdFromCacheValue($cacheValue);
    }

    protected function handleIfCacheExpired(string $qrCodeId, string $merchantId): array
    {
        $paymentId = $this->getPaymentIdFromDataBase($qrCodeId);

        /** @var QrV2 $qrCode */
        $qrCode = $this->repo->qr_code->findByIdAndMerchantId(QrV2::silentlyStripSign($qrCodeId), $merchantId);

        if (!empty($paymentId)) {
            $payment = $this->repo->payment->findByIdAndMerchantId($paymentId, $merchantId);

            if ($this->isPaymentSuccessful($payment)) {
                $this->setQrCodeStatusAndPaymentIdInCache($qrCode, $payment);

                return [$qrCode->getStatus(), $payment->getId()];
            }
        }

        $this->setQrCodeStatusAndPaymentIdInCache($qrCode);

        return [$qrCode->getStatus(), $paymentId];
    }

    protected function getPaymentIdFromDataBase(string $qrCodeId): string
    {
        try
        {
            return $this->repo->qr_payment->getLatestExpectedPaymentIdForQrCodeId($qrCodeId) ?? '';
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DB_CALL_FAILED
            );
        }

        return '';
    }

    /**
     * @param string $qrCodeId
     * @param string $qrCodeStatus
     * @param string|null $paymentId
     * @param string|null $paymentStatus
     * @param int $ttl
     * @return void
     */
    protected function putQrCodeAndPaymentStatusInCache(
        string  $qrCodeId,
        string  $qrCodeStatus,
        ?string $paymentId = null,
        ?string $paymentStatus = null,
        int     $ttl = Constants::DEFAULT_CACHE_TTL
    ): void
    {
        $cacheValue = implode(Constants::CACHE_VALUE_SEPARATOR, [
           $qrCodeStatus,
           $paymentId,
           $paymentStatus,
        ]);

        try
        {
            Cache::put(
                self::getCacheKeyForQRCodeId($qrCodeId),
                $cacheValue,
                $ttl
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::QR_PAYMENT_CACHE_UPDATE_FAILED
            );
        }
    }

    public function processQrPaymentCallbackThroughNewGatewayAdapter(string $gateway, $input)
    {
        // pre-process callback
        try
        {
            $resp = (new QrGatewayModule($this->app))->preProcessQrCallback($input, $gateway);
        }
        catch (GatewayErrorException $ge)
        {
            $gatewayData = $ge->getData();

            $this->trace->traceException(
                $ge,
                Level::Error,
                TraceCode::QR_PAYMENT_CALLBACK_GATEWAY_FAILURE,
                [
                    'gateway' => $gateway,
                ]
            );

            $this->createQrPaymentRequestForFailureCallback($gatewayData, $gateway);

            // Since the gateway itself sent a failure message in callback, we shall return success as true
            // This indicates to the gateway that we have successfully consumed the callback
            return [
                'status' => 'SUCCESS',
                'error_message' => null,
            ];
        }
        catch (IntegrationException $ie)
        {
            $exceptionResponseData = $ie->getData();
            $errorData = json_decode($exceptionResponseData['body'], true, flags:JSON_THROW_ON_ERROR);

            // If it was actually a gateway level failure, then error object and error code will be populated in data
            // So, we can record a qr payment request against it, assuming that merchant reference (tr) is present
            if (empty($errorData["data"]["error"]["code"]) === false)
            {
                $this->trace->traceException(
                    $ie,
                    Level::Error,
                    TraceCode::QR_PAYMENT_CALLBACK_GATEWAY_FAILURE,
                    [
                        'gateway' => $gateway,
                    ]
                );

                $this->createQrPaymentRequestForFailureCallback($errorData["data"], $gateway);

                // Since the gateway itself sent a failure message in callback, we shall return success as true
                // This indicates to the gateway that we have successfully consumed the callback
                return [
                    'status' => 'SUCCESS',
                    'error_message' => null,
                ];
            }

            // else, it is not a transaction related gateway failure, it is a platform related failure
            $this->trace->traceException(
                $ie,
                Level::Error,
                TraceCode::QR_PAYMENT_CALLBACK_PRE_PROCESS_FAILURE,
                [
                    'gateway' => $gateway,
                ]
            );

            // There was some failure during pre-processing, we shall return a failure
            return [
                'status' => 'FAILURE',
                'error_message' => $ie->getMessage(),
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Level::Error,
                TraceCode::QR_PAYMENT_CALLBACK_PRE_PROCESS_FAILURE,
                [
                    'gateway' => $gateway,
                ]
            );

            // There was some failure during pre-processing, we shall return a failure
            return [
                'status' => 'FAILURE',
                'error_message' => $e->getMessage(),
            ];
        }


        // get QR CodeID
        [$qrCode, $mode] = $this->findQrCodeForQrPayment($resp, $gateway);

        // process non-existing payment callback
        if (empty($mode) === false)
        {
            // We are passing $success as true as failure cases are handled in the exceptions above already
            // We assume that the integration with Mozart v2 throws an exception when the status is a failure
            return $this->processQrPaymentForNewGatewayFlow($qrCode, $resp, $gateway, true);
        }

        return $this->processNonQrCallback($resp, $gateway);
    }

    // This function assumes that we are getting the response in the mozart holy grail format
    public function processQrPaymentCallbackThroughNewGatewayAdapterForExistingGateways(string $gateway, array $resp, $success)
    {
        // get QR CodeID
        [$qrCode, $mode] = $this->findQrCodeForQrPayment($resp, $gateway);

        // process non-existing payment callback
        if (empty($mode) === false)
        {
            return $this->processQrPaymentForNewGatewayFlow($qrCode, $resp, $gateway, $success);
        }

        return $this->processNonQrCallback($resp, $gateway);
    }

    public function processQrPaymentForNewGatewayFlow($qrCode, $input, $gateway, $success, $isQrStatusCheck = false)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $input,
                'gateway' => $gateway,
            ]
        );

        $qrPaymentRequest = null;

        try
        {
            if (boolval($success) === false)
            {
                $this->createQrPaymentRequestForFailureCallback($input, $gateway);

                $this->trace->error(
                    TraceCode::QR_PAYMENT_FAILED_TRANSACTION_CALLBACK,
                    [
                        'notification_request' => $input,
                        'gateway'              => $gateway,
                    ]);

                return [
                    'status' => 'FAILURE',
                    'error_message' => 'received failure status in callback',
                ];
            }

            $terminal = $this->findTerminalByGatewayAndTerminalData($gateway, $input['terminal']);

            if (empty($terminal) === true)
            {
                throw new LogicException(
                    TraceCode::QR_CODE_UPI_QR_TERMINAL_NOT_FOUND_FOR_MERCHANT,
                    ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                    [
                        'merchant_id' => $qrCode->merchant->getId(),
                    ]);
            }

            $qrData = $this->getQrPaymentDataFromInput($qrCode, $input, $gateway, $terminal);

            $qrPaymentRequestInput = [
                QrPaymentRequest\Entity::QR_CODE_ID => $qrCode->getId(),
                QrPaymentRequest\Entity::TRANSACTION_REFERENCE => $qrData['provider_reference_id'],
            ];

            if ($isQrStatusCheck === true)
            {
                $input['qr_status_check'] = true;
            }

            $qrPaymentRequest = (new QrPaymentRequest\Service())->createForQrPaymentTriggeredViaNewGatewayAdapter(
                $qrPaymentRequestInput,
                $input
            );

            $valid = (new Core())->processPayment(
                [
                    'qr_data' => $qrData,
                    'callback_data' => ['data' => $input],
                ],
                $terminal,
                $qrPaymentRequest
            );

            return [
                'status' => $valid === true ? 'SUCCESS' : 'FAILURE',
                'error_message' => null,
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Level::Error,
                TraceCode::QR_PAYMENT_PROCESSING_FAILED,
                [
                    'qr_code_id' => $qrCode->getId() ?? null,
                    'input' => $input,
                    'gateway' => $gateway,
                ]
            );

            (new QrPaymentRequest\Service())->update($qrPaymentRequest, null, null,
                                                     $e->getMessage(), QrPaymentRequest\Type::BHARAT_QR);

            return [
                'status' => 'FAILURE',
                'error_message' => $e->getMessage(),
            ];
        }
    }

    // 1. Check qr code table by merchant reference
    // 2. Check using qr code config
    protected function findQrCodeForQrPayment(array $gatewayResponse, string $gateway)
    {
        $merchantReference = $gatewayResponse['upi']['merchant_reference'];

        [$qrCode, $mode] = $this->app['repo']->qr_code->returnLiveOrTestModeQrCodeByMerchantReference($merchantReference);

        if (empty($mode) === false)
        {
            $this->app['basicauth']->setModeAndDbConnection($mode);

            return [$qrCode, $mode];
        }
        else
        {
            $terminal = $this->findTerminalByGatewayAndTerminalData($gateway, $gatewayResponse['terminal']);

            if (($terminal !== null) and ($terminal->isQrV2Terminal() === true))
            {
                $qrCodeId = (new BharatQr\Service())->findQrCodeIdFromQrCodeConfig($terminal);

                if (empty($qrCodeId) === true)
                {
                    return [null, null];
                }

                $qrCode = $this->app['repo']->qr_code->find($qrCodeId);

                if (empty($qrCode) === true)
                {
                    return [null, null];
                }

                // Assuming that we shall be doing this only for live mode. Internal test mode will not need this by design.
                return [$qrCode, Mode::LIVE];
            }
        }

        return [null, null];
    }

    public function findQrCodeForQrPaymentRearch(array $gatewayResponse, string $gateway)
    {
        if (empty($gatewayResponse['upi']['merchant_reference']) === true)
        {
            return [null, null];
        }

        $qrVariant = strtolower(
                $this->app->razorx->getTreatment(
                    $gateway,
                    RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY,
                    $this->mode ?? Mode::LIVE
                )) === RazorxTreatment::RAZORX_VARIANT_ON;

        if ($qrVariant === false)
        {
            $gatewayClass = $this->app['gateway']->gateway($gateway);

            $gatewayResponse['upi']['merchant_reference'] =
                $gatewayClass->getQrPaymentMerchantReference($gatewayResponse['upi']['merchant_reference']);
        }

        return $this->findQrCodeForQrPayment($gatewayResponse, $gateway);
    }

    protected function findTerminalByGatewayAndTerminalData($gateway, $terminalData)
    {
        //TODO: Check how mode is handled here if not set by default
        return $this->app['repo']->terminal->findByGatewayAndTerminalData($gateway, $terminalData);
    }

    public function getQrPaymentDataFromInput($qrCode, $inputFields, $gateway, $terminal)
    {
        $output = [
            BharatQr\GatewayResponseParams::AMOUNT                => $inputFields['payment'][Payment\Entity::AMOUNT_AUTHORIZED],
            BharatQr\GatewayResponseParams::VPA                   => $inputFields['upi'][UpiEntity::VPA],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::UPI,
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $inputFields['terminal'][\RZP\Models\Terminal\Entity::GATEWAY_MERCHANT_ID],
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $inputFields['upi'][Entity::MERCHANT_REFERENCE],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $inputFields['upi'][UpiEntity::NPCI_REFERENCE_ID],
            BharatQr\GatewayResponseParams::PAYEE_VPA             => $terminal->getVpa() ?? $terminal->getGatewayMerchantId2(),
            BharatQr\GatewayResponseParams::GATEWAY               => $gateway,
        ];

        if (empty($inputFields['upi']['gateway_timestamp']) === false)
        {
            $output[BharatQr\GatewayResponseParams::TRANSACTION_TIME] = $inputFields['upi']['gateway_timestamp'];
        }
        else if (empty($inputFields['gateway_timestamp']) === false)
        {
            $output[BharatQr\GatewayResponseParams::TRANSACTION_TIME] = $inputFields['gateway_timestamp'];
        }

        if (empty($inputFields['payment']['payer_account_type']) === false)
        {
            $output[BharatQr\GatewayResponseParams::PAYER_ACCOUNT_TYPE] = $inputFields['payment']['payer_account_type'];
        }

        return $output;
    }

    public function createQrPaymentRequestForFailureCallback($gatewayData, $gateway)
    {
        [$qrCode, $mode] = $this->findQrCodeForQrPayment($gatewayData, $gateway);

        if (empty($mode) === true)
        {
            return;
        }

        return (new QrPaymentRequest\Service())->createForQrPaymentTriggeredViaNewGatewayAdapter(
            [
                QrPaymentRequest\Entity::QR_CODE_ID => $qrCode->getId(),
                QrPaymentRequest\Entity::TRANSACTION_REFERENCE => $gatewayData['upi']['npci_reference_id'],
            ],
            $gatewayData,
            true
        );
    }

    public function createQrPaymentRequestForFailureInStatusCheck($qrCode, $gatewayData, $gateway)
    {
        $gatewayData['qr_status_check'] = true;

        return (new QrPaymentRequest\Service())->createForQrPaymentTriggeredViaNewGatewayAdapter(
            [
                QrPaymentRequest\Entity::QR_CODE_ID => $qrCode->getId(),
                QrPaymentRequest\Entity::TRANSACTION_REFERENCE => $gatewayData['upi']['npci_reference_id'],
            ],
            $gatewayData,
            true
        );
    }

    protected function processNonQrCallback($data, $gateway)
    {
        // process non QR Payment callback for refund
        if (UniqueIdEntity::verifyUniqueId($data['upi']['merchant_reference'], false) === true)
        {
            $this->trace->info(TraceCode::UPI_UNEXPECTED_PAYMENT_CREATION_SKIPPED, [
                'payment_id'    => $data['upi']['merchant_reference'],
                'message'       => 'unexpected payment creation skipped due to length being 14'
            ]);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_CREATION_SKIPPED);

            return [];
        }

        $data['payment']['amount'] = $data['payment']['amount_authorized'];
        $data['payment']['contact'] = '+919999999999';
        $data['payment']['email'] = 'void@razorpay.com';
        unset($data['payment']['amount_authorized']);

        return (new Payment\Service)->unexpectedCallback($data, $data['upi']['merchant_reference'], $gateway, true);
    }

    /**
     * createUpiEntityForQrPayment() is used to save the UPI entity against a QR payment in the database. In the QR
     * payment flow, this is only triggered during payment authorise action.
     * @param $input - The values to be used to fill the UPI entity
     * @param $action - The action that triggers this
     *
     * @return array[] - Returns acquirer map (mapped to rrn/reference 16)
     */
    public function createUpiEntityForQrPayment($input, $action): array
    {
        $entity = new UpiEntity;

        $entity->setAmount($input['payment']['amount']);

        $entity->setPaymentId($input['payment']['id']);

        $entity->setAction($action);

        // Acquirer is set to mozart similar to all other UPI payment gateways on Mozart
        $entity->setAcquirer('mozart');

        // Since this gateway name is used in the query to fetch relevant details for UPI payment refund, we need to set
        // the proper gateway here.
        // Question- Is it better to do it using the payment entity in the input or the terminal entity??
        $entity->setGateway($input['terminal']->getGateway());

        $entity->generate($input);

        $entity->fill($input);

        $this->repo->upi->saveOrFail($entity);

        return [
            'acquirer' => [
                Payment\Entity::REFERENCE16 => $entity->getNpciReferenceId(),
            ],
        ];
    }
}
