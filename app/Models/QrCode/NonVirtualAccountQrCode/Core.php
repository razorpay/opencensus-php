<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;

use RZP\Constants\Entity as EntityConstants;
use RZP\Exception\ServerErrorException;
use RZP\Models\QrGatewayModule\QrGatewayModule;
use RZP\Trace\Tracer;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\QrStatusCheck;
use RZP\Models\EntityOrigin;
use RZP\Models\QrCode\Metric;
use RZP\Constants\HyperTrace;
use RZP\Constants\Environment;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\QrPaymentRequest\Type;
use RZP\Models\Order\Entity as Order;
use RZP\Exception\BadRequestException;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\QrPayment\Service as QrPaymentService;
use RZP\Models\Checkout\Order\Entity as CheckoutOrder;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCodeConfig\Service as QrCodeConfigService;

class Core extends QrCode\Core
{
    const QR_STATUS_CHECK_MUTEX_TIMEOUT  = 60; // 1 min timeout

    public function __construct()
    {
        parent::__construct();

        $this->generator = new Generator;
    }


    /**
     * @param array                    $input
     * @param Order|CheckoutOrder|null $order
     *
     *
     * @throws BadRequestException
     */
    public function buildQrCode(array $input, $order = null)
    {
        $qrCode = (new Entity())->build($input);

        if ($qrCode->getProvider() === Provider::BHARAT_QR and
            $qrCode->getRequestSource() !== RequestSource::EZETAP)
        {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT);
        }

        $this->checkFeatureEnabled($input);

        $customer = $this->getCustomerIfGiven($input);

        $terminal = $this->validateAndFetchTerminalIfAvailable($input);

        $qrCode->customer()->associate($customer);

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($order);

        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_BUILD_QR_CODE], function () use ($terminal, $qrCode) {
            return $this->build($qrCode, $terminal);
        });
        // Creates entity origin when QR code is created
        // QR code creation won't be failed even if origin is not set.
        (new EntityOrigin\Core)->createEntityOrigin($qrCode);

        return $qrCode;

    }

    public function buildQrCodeForMerchant(array $input, array $additionalData = null)
    {
        $qrCode = (new Entity())->build($input);

        $this->checkFeatureEnabled($input);

        $customer = $this->getCustomerIfGiven($input);

        $terminal = $this->validateAndFetchTerminalIfAvailable($input, $additionalData);

        $qrCode->customer()->associate($customer);

        $qrCode->merchant()->associate($this->merchant);


        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_BUILD_QR_CODE], function () use ($terminal, $qrCode, $additionalData) {
            return $this->build($qrCode, $terminal, $additionalData);
        });
        // Creates entity origin when QR code is created
        // QR code creation won't be failed even if origin is not set.
        if(isset($additionalData['oauth_application_id']) === true)
        {
            (new EntityOrigin\Core)->createEntityOriginByOauthAppId($qrCode, $additionalData['oauth_application_id']);
        }

        return $qrCode;

    }

    private function build(Entity $qrCode, $terminal = null, $additionalData = null)
    {
        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_GENERATE_QR_STRING], function() use ($terminal, $qrCode, $additionalData)
        {
            if (empty($additionalData['qrString']) === false or
                empty($qrCode->getQrString()) === false)
            {
                $this->setQrStringFromRequest($qrCode, $additionalData,$terminal);
            }
            else
            {
                $qrCode->generateQrString($terminal);
            }
        });

        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_SET_SHORT_URL], function () use ($qrCode)
        {
            $this->setShortUrl($qrCode);
        });

        Tracer::inspan(['name' => HyperTrace::QR_CODE_BUILD_SAVE_OR_FAIL], function () use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);
        });

        $this->addStaticQRinQRCodeConfig($qrCode, $terminal);

        return $qrCode;
    }

    public function setQrStringFromRequest(Entity $qrCode, $additionalData = null, $terminal=null)
    {
        $qrString = $additionalData['qrString'] ?? $qrCode->getQrString() ?? null;

        if (empty($qrString) === false)
        {
            $this->trace->info(TraceCode::QR_CODE_REQUEST_VPA_QR_STRING_AVAILABLE, [
                'message'  => 'QR_CODE_REQUEST_VPA_QR_STRING_AVAILABLE',
                'qrString' => $qrString,
            ]);

            $tr = $this->getTransactionReferenceFromQrString($qrString);

            $this->trace->info(
                TraceCode::QR_CODE_EXTRACTED_TR,
                [
                    'transaction_reference' => $tr
                ]
            );

            if ((empty($tr) === true) and
                ($terminal?->getGateway() !== 'upi_jkbank') )
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_REFERENCE_REQUIRED);
            }

            if(($terminal?->getGateway() === 'upi_jkbank') and (empty($tr) === true)) {
                $tr = $qrCode->getId() . 'qrv2';
                $qrString = $this->updateTrIdInQrString($tr, $additionalData['qrString']);
            }

            $qrCode->setQrString($qrString);
            $qrCode->setReference($tr);

            //register the qrcode in switch
            if ($terminal?->getGateway() === Gateway::UPI_RZPAPB)
            {
                $upiMode = (new Generator())->getQrCodeModeAccountingForOnlineAndOfflineRequestSource($qrCode, $terminal);
                (new QrGatewayModule($this->app))->generateIntentQrForUpiRzpApb($qrCode, $terminal, $upiMode);
            }

            return $qrString;
        }

        return null;
    }

    public function getTransactionReferenceFromQrString($qrString)
    {
        $queryString = parse_url($qrString, PHP_URL_QUERY);
        parse_str($queryString, $params);
        return $params['tr'] ?? null;
    }

    public function getPayeeVpaFromQrString(string $qrString) : string | null
    {
        $queryString = parse_url($qrString, PHP_URL_QUERY);
        parse_str($queryString, $params);
        return strtolower($params['pa']) ?? null;
    }

   /**
     * Updates the 'tr' parameter in the given QR string with the provided transaction ID.
     *
     * This method parses the query part of the provided QR string, updates or adds the 'tr' parameter
     * with the given transaction ID, and then rebuilds the URL with the updated query.
     *
     * @param string $trId The transaction ID to be set in the QR string.
     * @param string $qrString The original QR string to be updated.
     * @return string The updated QR string with the new 'tr' parameter.
     */
    protected function updateTrIdInQrString(string $trId, string $qrString) : string
    {
        // Parse the query part of the URL
        $parts = parse_url($qrString);
        parse_str($parts['query'] ?? '', $queryParams);

        // Update or add the 'tr' parameter
        $queryParams['tr'] = $trId;

        // Rebuild the URL with updated query
        $parts['query'] = http_build_query($queryParams);
        return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $parts['query'];
    }

    public function addStaticQRinQRCodeConfig($qrCode, $inputTerminal = null)
    {

        if ($qrCode->getUsageType() !== UsageType::MULTIPLE_USE)
        {
            return null;
        }

        $gateway = $qrCode->getGatewayFromQrString();
        if ($gateway === null)
        {
            return null;
        }

        $gatewayVariant = $this->app->razorx->getTreatment($gateway, RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS, $this->mode);

        if (strtolower($gatewayVariant) !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            return null;
        }

        if ($inputTerminal === null)
        {
            return null;
        }

        (new QrCodeConfigService())->createOrUpdateStaticQRCodeConfig($inputTerminal, $qrCode);

    }

    public function generateQrCodeFile($qrCode)
    {
        if (($qrCode->getRequestSource() === RequestSource::CHECKOUT) or
            ($qrCode->getRequestSource() === RequestSource::FALLBACK))
        {
            return;
        }

        parent::generateQrCodeFile($qrCode);
    }

    public function setShortUrl($qrCode)
    {
        if (($qrCode->getRequestSource() === RequestSource::CHECKOUT) or
            ($qrCode->getRequestSource() === RequestSource::FALLBACK))
        {
            return;
        }

        parent::setShortUrl($qrCode);
    }

    private function checkFeatureEnabled($input)
    {
        if ($input[Entity::REQ_PROVIDER] === Type::BHARAT_QR)
        {
            $isBqrEnabled = $this->merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR_V2);

            if ($isBqrEnabled === false)
            {
                $isBqrEnabled = $this->merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR);
            }

            if ($isBqrEnabled === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT);
            }
        }

        if ($input[Entity::REQ_PROVIDER] === Type::UPI_QR)
        {
            $methods = $this->merchant->getMethods();

            if ($methods->isUpiEnabled() === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT);
            }
        }
    }

    public function close($qrCode, $closeReason)
    {
        if (($this->generator->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->merchant->getId()) === true) and
            ($qrCode->getUsageType() === UsageType::MULTIPLE_USE))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CLOSE_STATIC_QR_CODE_FAILURE);
        }

        if($qrCode->getStatus() === Status::CLOSED)
        {
            $this->trace->info(TraceCode::QR_CODE_EXPIRE_REQUEST_SKIPPED,
                [
                    'qr_status' => $qrCode->getStatus(),
                    'id' => $qrCode->getId()
            ]);

            return;
        }

        $qrCode->setStatus(Status::CLOSED);

        $currentTime = Carbon::now()->getTimestamp();

        $qrCode->setClosedAt($currentTime);

        $qrCode->setCloseReason($closeReason);

        $vpaId = $this->repo->vpa->findVpaByEntityIdAndEntityType($qrCode->getId(), $qrCode->getEntityName());

        $this->repo->transaction(function() use ($qrCode, $vpaId)
        {
            $this->repo->saveOrFail($qrCode);

            if ($qrCode->bankAccount !== null)
            {
                $this->repo->deleteOrFail($qrCode->bankAccount);
            }

            if ($vpaId !== null)
            {
                $this->repo->vpa->deleteById($vpaId, $qrCode->merchant->getId());
            }
        });

        if ($closeReason !== CloseReason::PAID && $qrCode->isCheckoutQrCode()) {
            // Updating cache only for unpaid & closed QrCodes as we don't have
            // access to PaymentId here
            (new QrPaymentService())->setQrCodeStatusAndPaymentIdInCache($qrCode);
        }

        return $qrCode;
    }

    public function closeQrCodeAdmin($qrCode, $closeReason)
    {
        if ($qrCode->getUsageType() === UsageType::SINGLE_USE)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CLOSE_DYNAMIC_QR_CODE_FAILURE);
        }

        $qrCode->setStatus(Status::CLOSED);

        $currentTime = Carbon::now()->getTimestamp();

        $qrCode->setClosedAt($currentTime);

        $qrCode->setCloseReason($closeReason);

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    public function setDeviceIdForQr($qrCode, $device_id)
    {
        $qrCode->updateDeviceId($device_id);

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    public function createOrFetchSharedQrCode()
    {
        $fallbackQrCodeId = Entity::SHARED_ID;

        $fallbackQrCode = $this->repo->qr_code->find($fallbackQrCodeId);

        if ($fallbackQrCode === null)
        {
            $fallbackQrCode = $this->createFallbackQrCode();
        }

        return $fallbackQrCode;
    }

    private function createFallbackQrCode()
    {
        $sharedMerchantId = $this->getDefaultMerchantId();

        $this->merchant = $this->repo->merchant->find($sharedMerchantId);

        $input = [
            Entity::REQ_USAGE_TYPE => UsageType::MULTIPLE_USE,
            Entity::FIXED_AMOUNT   => false,
            Entity::REQ_PROVIDER   => Type::UPI_QR,
            Entity::REQUEST_SOURCE => RequestSource::FALLBACK,
        ];

        $qrCode = (new Entity)->build($input);

        $qrCode->setId(Entity::SHARED_ID);

        $qrCode->merchant()->associate($this->merchant);

        return $this->build($qrCode);
    }

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    protected function getDefaultMerchantId()
    {
        $defaultMerchantId = Account::DEMO_PAGE_ACCOUNT;

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        return $defaultMerchantId;
    }

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }

    /**
     * This function manages the dispatch of the QR Status Check job to its SQS queue.
     * This is a unique ID job. That is, it ensures that multiple messages are not pushed for the same QR Code ID.
     * On production, we only dispatch messages in live mode. Thus, we have NOT created a prod queue for this in test mode.
     * Also read, app\Jobs\QrStatusCheck
     *
     * @param string $id The ID of the QR code to be dispatched.
     * @return bool If the dispatch was successful or not.
     */
    public function dispatchQrCodeToStatusCheckQueue(string $id, string $requestSource = null): bool
    {
        $this->trace->info(TraceCode::QR_STATUS_CHECK_JOB_DISPATCH_INIT, ['id' => $id]);

        try
        {
            $mutexAcquired = false;
            if($requestSource !== RequestSource::EZETAP)
            {
                $mutex         = $this->app['api.mutex'];
                $mutexAcquired = $mutex->acquire("qr_status_check_" . $id,
                    self::QR_STATUS_CHECK_MUTEX_TIMEOUT, strict: true);
            }

            if ($requestSource !== RequestSource::EZETAP and $mutexAcquired === false)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                    null,
                    ['qr_code' => $id]
                );
            }
            else
            {
                // We are only dispatching in live mode on prod.
                if ((($this->isEnvironmentProduction() === true) and ($this->isLiveMode() === true)) or
                    ($this->isEnvironmentProduction() === false))
                {
                    QrStatusCheck::dispatch($this->mode, $id);

                    $this->trace->info(
                        TraceCode::QR_STATUS_CHECK_MESSAGE_DISPATCHED,
                        [
                            'id'  => $id,
                            'env' => $this->env,
                        ]
                    );
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::QR_STATUS_CHECK_DISPATCH_FAILED, ['id' => $id]);

            $dimensions = [];
            $this->trace->count(Metric::QR_STATUS_CHECK_SQS_MESSAGE_DISPATCH_FAILED, $dimensions);
        }

        return false;
    }

    protected function validateAndFetchTerminalIfAvailable(array $input, $additionalData = null)
    {

        if ((isset($input['vpa']) === false) or
            ($input['usage'] !== UsageType::MULTIPLE_USE))
        {
            $this->trace->info(TraceCode::QR_CODE_REQUEST_VPA_TERMINAL_NOT_AVAILABLE, [
                'message' => 'Terminal not available for the input',
                'usage' =>  $input['usage'],
            ]);
            return null;
        }

//        if (empty($additionalData['qrString']) === false)
//        {
//            $this->trace->info(TraceCode::QR_CODE_REQUEST_VPA_TERMINAL_NOT_FETCHED, [
//                'message'  => 'QR_CODE_REQUEST_VPA_TERMINAL_NOT_FETCHED',
//                'qrString' => $additionalData['qrString'],
//            ]);
//
//            return null;
//        }

        $vpa = $input['vpa'];
        $gateway = $this->fetchGatewayFromVpa($vpa);

        $terminalDetails = [
            TerminalEntity::MERCHANT_ID => $this->merchant->getId(),
        ];

        if (in_array($gateway, ['upi_airtel', 'upi_icici', 'upi_mindgate']))
        {
            $terminalDetails[TerminalEntity::GATEWAY_MERCHANT_ID2] = $vpa;
        }
        elseif (in_array($gateway, ['upi_jkbank', 'upi_rzpapb']))
        {
            $terminalDetails[TerminalEntity::VPA] = $vpa;
        }

        if ($gateway === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $terminalDetails);

        if (empty($terminal) === true || $terminal->isQrV2Terminal() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        }

        $this->trace->info(TraceCode::QR_CODE_REQUEST_VPA_TERMINAL, [
            'terminal_id' => $terminal->getId(),
        ]);

        return $terminal;
    }

    public function fetchGatewayFromVpa(string $vpa): ?string
    {
        $vpaSplit = explode("@", $vpa);
        $gateway = null;

        if (isset($vpaSplit[1])) {
            switch ($vpaSplit[1]) {
                case 'mairtel':
                    $gateway = 'upi_airtel';
                    break;
                case 'icici':
                    $gateway = 'upi_icici';
                    break;
                case 'hdfcbank':
                    $gateway = 'upi_mindgate';
                    break;
                case 'jkb':
                    $gateway = 'upi_jkbank';
                    break;
                case 'rxairtel':
                case 'rairtel':
                    $gateway = 'upi_rzpapb';
                    break;
                default:
                    $gateway = null;
            }
        }

        return $gateway;
    }
}
