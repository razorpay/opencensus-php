<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\HyperTrace;
use RZP\Models\Checkout\Order\Entity as CheckoutOrder;
use RZP\Models\Order\Entity as Order;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\QrPayment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\QrCode\Metric;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\QrCode\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Event as MerchantEvent;
use RZP\Exception\BadRequestException;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\QrPayment\Service as QrPaymentService;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVAQrCodeEntity;
use RZP\Trace\Tracer;

class Service extends QrCode\Service
{
    protected $mutex;
    /**
     * This static array maintains a list of all gateways enabled for status check and reminder service.
     * @var array A list of supported gateway on status check and reminder service.
     */
    public static $qrStatusCheckGateways  = [
        Gateway::UPI_ICICI,
        Gateway::UPI_YESBANK,
        Gateway::UPI_MINDGATE,
        Gateway::UPI_AIRTEL,
        Gateway::UPI_RZPAPB,
    ];

    public static $qrBharatQrStatusCheckGateways = [
        Gateway::UPI_HDFCMINTOAK,
        Gateway::UPI_MINDGATE
    ];

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create($input, $virtualAccount = null)
    {
        $startTimeMs = microtime(true) * 1000;

        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        $errorMessage = null;

        $gateway = null;

        $metric = new Metric();

        try
        {
            $input[Entity::REQUEST_SOURCE] = $input[Entity::REQUEST_SOURCE] ?? $this->getRequestSourceViaAuth();

            (new Validator)->validateQrOnDedicatedTerminal($input);

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE], function () use ($input) {
                return (new Core)->buildQrCode($input);
            });

            $this->publishQrCodeEvent($qrCode, Event::CREATED);

            $gateway = $qrCode->getGatewayFromQrString();

            $input[Entity::GATEWAY] = $gateway;
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }
        finally
        {
            $metric->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        // Since this is inside NonVirtualAccountQrCode/Service, it is safe to assume that only qrV2 are checked here

        if (
            ($qrCode->getUsageType() === UsageType::SINGLE_USE) and
            (($qrCode->getProvider() === Provider::UPI_QR) and
                (in_array($gateway, self::$qrStatusCheckGateways, true) === true)) or
            (($qrCode->getProvider() === Provider::BHARAT_QR) and
                (in_array($gateway, self::$qrBharatQrStatusCheckGateways, true) === true))
        )
        {
            $this->triggerQrStatusCheckPostCreate($qrCode);
        }

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        $metric->pushCreateLatencyMetrics($input, $startTimeMs, $qrCode->getGatewayLatencyForQrCreate());

        return $qrCode->toArrayPublic();
    }

    public function createQrForMerchant($input)
    {
        $startTimeMs = microtime(true) * 1000;

        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        $errorMessage = null;

        $metric = new Metric();

        try
        {
            $qrCreateReq = [];

            $this->setMerchantContextForQrCreate($input);

            $qrCreateReq = $this->getInputForPartnerSqrCreate($input);

            $this->validateQrStringCreateRequest($input);

            (new Validator)->validateQrOnDedicatedTerminal($qrCreateReq);

            $this->validateVpaInRequest($input);

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE], function () use ($qrCreateReq, $input) {
                return (new Core)->buildQrCodeForMerchant($qrCreateReq, $input);
            });

            $this->publishQrCodeEvent($qrCode, Event::CREATED);

            $gateway = $qrCode->getGatewayFromQrString();

            $qrCreateReq[Entity::GATEWAY] = $gateway;
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $qrCreateReq);

            throw $ex;
        }
        finally
        {
            $metric->pushCreateMetrics($qrCreateReq, $errorMessage);
        }

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        $metric->pushCreateLatencyMetrics($qrCreateReq, $startTimeMs, $qrCode->getGatewayLatencyForQrCreate());

        return $qrCode->toArrayPublic();
    }

    protected function setMerchantContextForQrCreate($input)
    {
        $mid = $input['merchant_id'];
        $this->merchant = $this->repo->merchant->find($mid);
        if (empty($this->merchant))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_FOUND,
                null,
                [
                    'merchant_id' => $mid,
                ],
            );
        }
        $this->auth->setMerchantById($input['merchant_id']);
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
        $this->mode            = Mode::LIVE;
    }

    public function validateQrStringCreateRequest($input)
    {
        if (isset($input['qrString']) === true) {
            $tr = (new Core)->getTransactionReferenceFromQrString($input['qrString']);
            $this->trace->info(TraceCode::QR_CODE_EXTRACTED_TR, [
                                                                  'message'           => 'QR_CODE_EXTRACTED_TR',
                                                                  'tr for validation' => $tr,
                                                              ]
            );

            $gateway = (new Core)->fetchGatewayFromVpa($input['vpa']);
            if ((empty($tr) === true) and
                ($gateway !== "upi_jkbank"))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_STRING_TR_EMPTY_ERROR);
            }
            if ($gateway !== 'upi_jkbank')
            {
                [$qrCode, $mode] = $this->app['repo']->qr_code->returnLiveOrTestModeQrCodeByMerchantReference($tr);

                if (empty($mode) === false)
                {
                    $this->trace->info(
                        TraceCode::QR_CODE_ALREADY_EXIST,
                        [
                            'message' => 'QR_CODE_ALREADY_EXIST',
                            'qrCode'  => $qrCode,
                            'mode'    => $mode,
                        ]
                    );

                    throw new BadRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_REQUEST);
                }
            }
        }
    }

    /**
     * Validates the request vpa with qrString pa[Payee Address]
     * @param array $input
     *
     * @throws BadRequestException
     * @return void
     */
    public function validateVpaInRequest(array $input): void
    {
        if (empty($input['qrString']) === true)
        {
            return;
        }

        //validated vpa from request and pa in qr string
        $pa=(new Core)->getPayeeVpaFromQrString($input['qrString']);

        if($pa !== strtolower($input['vpa']))
        {
            $this->trace->info(TraceCode::QR_PAYEE_VPA_VALIDATION_FAILED, [
                'message' => 'QR_PAYEE_VPA_VALIDATION_FAILED',
            ]);
            throw new BadRequestException(ErrorCode::QR_PAYEE_VPA_VALIDATION_FAILED);
        }
    }

    public function addPosQrCodeFeaturesOnPosActivation($input)
    {

        if ((empty($input[Constants::POS_ACTIVATION_STATUS]) === true) or
            ($input[Constants::POS_ACTIVATION_STATUS] !== 'activated'))
        {
            return false;
        }
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $input['merchant_id'],
            Feature\Entity::ENTITY_TYPE => ConstantEntity::MERCHANT,
            Feature\Entity::NAMES       => [Feature\Constants::QR_CODES,Feature\Constants::QR_IMAGE_CONTENT],
            Feature\Entity::SHOULD_SYNC => true,
            MerchantEvent\Entity::EVENT => 'pos_activated'
        ];

        $this->trace->info(TraceCode::POS_QR_CODE_FEATURE_ENABLE_PAYLOAD, [
            'message' => 'Payload for pos qr code feature enable request',
            'featureParams' =>  $featureParams,
        ]);

        $response = (new Feature\Service)->addFeatures($featureParams);

        $this->trace->info(TraceCode::POS_QR_CODE_FEATURE_ENABLE_RESPONSE, [
            'message' => 'response for pos qr code feature enable request',
            'response' =>  $response,
        ]);

        return $response;
    }


    protected function getInputForPartnerSqrCreate($input)
    {

        if (isset($input['vpa']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VPA_DOESNT_EXIST);
        }

        $qrCreateReq = [
                        'usage'        => 'multiple_use',
                        'type'         => 'upi_qr',
                        'fixed_amount' => false,
                        'vpa'          => $input['vpa']
         ];

        $qrCreateReq[NonVAQrCodeEntity::REQUEST_SOURCE] = RequestSource::API;

        if (isset($input[NonVAQrCodeEntity::REQUEST_SOURCE]) === true)
        {
            $qrCreateReq[NonVAQrCodeEntity::REQUEST_SOURCE] = $input[NonVAQrCodeEntity::REQUEST_SOURCE];
        }
        if (isset($input[NonVAQrCodeEntity::DEVICE_ID]) === true)
        {
            $qrCreateReq[NonVAQrCodeEntity::DEVICE_ID] = $input[NonVAQrCodeEntity::DEVICE_ID];
        }

        return $qrCreateReq;
    }

    public function setDeviceIdForQr(array $input)
    {
        $this->trace->info(TraceCode::QR_CODES_SET_DEVICE_REQUEST,
                           [
                               'input' => $input
                           ]);

        $errorMessage = null;
        $id           = $input['identifier']['qr_code_id'] ?? null;
        $referenceID  = $input['identifier']['trId'] ?? null;
        $deviceId     = $input['device_id'];
        $merchantId   = $input['identifier']['merchant_id'] ?? null;
        try
        {
            $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
            $this->mode            = Mode::LIVE;

            //check if identifier is present if not then throw exception
            if((empty($id) and empty($referenceID)) or empty($merchantId))
            {
                $this->trace->info(TraceCode::BAD_REQUEST_IDENTIFIER_NOT_FOUND, [
                    'message' => 'BAD_REQUEST_IDENTIFIER_NOT_FOUND',
                    'id' => $id,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_IDENTIFIER_NOT_FOUND,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "No Identifier Found in the Request Body"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_IDENTIFIER_NOT_FOUND,
                       "description"=>"No Identifier Found in the Request Body",
                       "source"=>"ezetap",
                        "step"=>"qr_code_device_id_mapping",
                        "reason"=>"input_validation_failed",
                        "metadata"=>$input
                   ]
                ];
            }

           $qrCode = empty($id)
                    ? (new Repository())->findByTrId($referenceID) ?? null
                    : $this->repo->qr_code->find(Entity::silentlyStripSign($id));

            if (empty($qrCode) === true)
            {
                $this->trace->info(TraceCode::QR_CODE_NOT_FOUND, [
                    'message' => 'QR_CODE_NOT_FOUND',
                    '$id'  => $id,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_QR_CODE_NOT_FOUND,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "QR Entity Not Found"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_QR_CODE_NOT_FOUND,
                        "description"=>"QR Entity Not Found",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_mapping",
                        "reason"=>"entity_not_found",
                        "metadata"=>$input
                    ]
                ];
            }

            //check if the device id is already mapped to any qr code
            //$qrCodeExists = (new Repository())->findByDeviceId($deviceId);
            $qrCodeExists = (new Repository())->findByMerchantAndDeviceId($merchantId,$deviceId);
            if ($qrCodeExists->count()>=1)
            {
                $this->trace->info(TraceCode::BAD_REQUEST_DEVICE_ID_ALREADY_MAPPED, [
                    'message' => 'BAD_REQUEST_DEVICE_ID_ALREADY_MAPPED',
                    'id' => $id,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_DEVICE_ID_ALREADY_MAPPED,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "Device Already Mapped to another QR"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_DEVICE_ID_ALREADY_MAPPED,
                        "description"=>"Device Already Mapped to another QR",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_mapping",
                        "reason"=>"device_already_mapped_to_qr",
                        "metadata"=>$input
                    ]
                ];
            }

            //check for the QR if the device id is already set if set then return exception
            if ($qrCode->getDeviceId() !== null)
            {
                $this->trace->info(TraceCode::BAD_REQUEST_QR_ALREADY_MAPPED, [
                    'message' => 'BAD_REQUEST_QR_ALREADY_MAPPED',
                    'id'  => $id,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_QR_ALREADY_MAPPED,
//                    data       : [
//                                   'error' => [
//                                     'metadata' => $input +  [ 'oldDeviceId' => $qrCode->getDeviceId() ]
//                                   ]
//                                 ],
//                    description: "QR Entity Already Mapped to a Device"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_QR_ALREADY_MAPPED,
                        "description"=>"QR Entity Already Mapped to a Device",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_mapping",
                        "reason"=>"entity_already_mapped",
                        "metadata"=>$input+['oldDeviceId'=>$qrCode->getDeviceId()]
                    ]
                ];
            }

            //check if the qr code is mapped to any device id and again trying to map to the same device id
            if($qrCode->getDeviceId() === $deviceId)
            {
                $this->trace->info(TraceCode::BAD_REQUEST_DUPLICATE_REQUEST, [
                    'message' => 'BAD_REQUEST_DUPLICATE_REQUEST',
                    'id'  => $id,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_DUPLICATE_REQUEST,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "QR Entity Mapping Device Duplicate Request"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_DUPLICATE_REQUEST,
                        "description"=>"QR Entity Mapping Device Duplicate Request",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_mapping",
                        "reason"=>"entity_duplicate_request",
                        "metadata"=>$input
                    ]
                ];

            }


            $this->trace->info(TraceCode::QR_CODES_SET_DEVICE_DETAILS,
                               [
                                   'newDeviceId' => $deviceId,
                                   'qrCodeId' => $qrCode->getId()
                               ]);

            (new Core)->setDeviceIdForQr($qrCode, $deviceId);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::QR_CODE_SET_DEVICE_REQUEST_FAILED,
                [
                    'id' => $id,
                ]);

            $errorMessage = $ex->getMessage();

            throw $ex;
        }
        finally
        {
            $requestSource = $qrCode ? $qrCode->getRequestSource() : null;

            (new Metric())->pushDeviceIdUpdateMetrics($errorMessage, $requestSource);
        }

        $response  = $qrCode->toArrayPublic();
        $response['device_id'] =  $qrCode->getDeviceId();

        $this->trace->info(TraceCode::QR_CODES_SET_DEVICE_PROCESSED,
                           [
                               'response' => $response,
                               'oldDeviceId' => null
                           ]);

        return $response;
    }


    public function unMapDeviceIdForQr(array $input)
    {
        $this->trace->info(TraceCode::QR_CODE_UNMAP_DEVICE_REQUEST,
            [
                'input' => $input
            ]);
        $deviceId = $input['device_id'];
        $merchantId  = $input['merchant_id'];
        $exception=null;
        try{

            //check if device id is present else throw an exception
//            if(empty($deviceId))
//            {
//                $this->trace->info(TraceCode::BAD_REQUEST_DEVICE_ID_NOT_FOUND, [
//                    'message' => 'BAD_REQUEST_DEVICE_ID_NOT_FOUND',
//                    'device_id' => $deviceId,
//                ]);
//                throw new BadRequestException(ErrorCode::BAD_REQUEST_DEVICE_ID_NOT_FOUND);
//            }

            $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);
            $this->mode            = Mode::LIVE;

            //            $qrCodes =(new Repository())->findByDeviceId($deviceId);
            $qrCodes = (new Repository())->findByMerchantAndDeviceId($merchantId,$deviceId);

            //check if device_id is mapped to any qr code
            if($qrCodes->isEmpty())
            {
                $this->trace->info(TraceCode::QR_CODE_NOT_FOUND, [
                    'message' => 'QR_CODE_NOT_FOUND',
                    'device_id'  => $deviceId,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::BAD_REQUEST_QR_CODE_NOT_FOUND,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "QR Entity Not Found"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::BAD_REQUEST_QR_CODE_NOT_FOUND,
                        "description"=>"QR Entity Not Found",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_unmapping",
                        "reason"=>"entity_not_found",
                        "metadata"=>$input
                    ]
                ];
            }


            // get the count of qr codes mapped to the device id, if >1 then throw an exception for unmapping failed
            if ($qrCodes->count() > 1) {
                $this->trace->info(TraceCode::MULTIPLE_QR_CODES_MAPPED_ERROR, [
                    'message' => 'MULTIPLE_QR_CODES_MAPPED_ERROR',
                    'device_id' => $deviceId,
                ]);
//                throw new BadRequestException(
//                    code       : ErrorCode::MULTIPLE_QR_CODES_MAPPED_ERROR,
//                    data       : ['error'=>['metadata' => $input]],
//                    description: "QR Entity Mapped to Multiple QR Codes"
//                );
                return [
                    "error"=>[
                        "code"=>ErrorCode::MULTIPLE_QR_CODES_MAPPED_ERROR,
                        "description"=>"QR Entity Mapped to Multiple QR Codes",
                        "source"=>"ezetap",
                        "step"=>"qr_code_device_id_unmapping",
                        "reason"=>"entity_multiple_mapped",
                        "metadata"=>$input
                    ]
                ];
            }


              //get the qr code and unmap the device id
               $qrCode=$qrCodes->first();

                (new Core)->setDeviceIdForQr($qrCode, null);

                $this->trace->info(TraceCode::QR_CODES_UNMAP_DEVICE_PROCESSED,
                [
                    'device_id' => $deviceId,
                    'qr_code_id' =>$qrCode->getId()
                ]);

        }catch (\Exception $ex) {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::QR_CODE_UNMAP_DEVICE_REQUEST_FAILED,
                [
                    'qr_id'=>$qrCode->getId(),
                    'device_id' => $deviceId,
                ]);

            $exception = $ex->getMessage();

            throw $ex;
        }

        $response  = [
            'status' => $qrCode->getStatus(),
            'id' => $qrCode->getId(),
            'entity'=>"qr_code",
            'device_id'=>$deviceId,
        ];

        $this->trace->info(TraceCode::QR_CODES_SET_DEVICE_PROCESSED,
            [
                'response' => $response,
                'oldDeviceId' => $deviceId
            ]);

        return $response;

    }

    public function createForCheckout($input)
    {
        $startTimeMs = microtime(true) * 1000;

        $this->trace->info(TraceCode::QR_CODE_CHECKOUT_CREATE_REQUEST, $input);

        (new Validator())->validateInput('createForCheckout', $input);

        $errorMessage = null;

        $metric = new Metric();

        try
        {
            if (array_key_exists(Entity::ENTITY_TYPE, $input))
            {
                switch ($input[Entity::ENTITY_TYPE])
                {
                    case ConstantEntity::ORDER:
                        $order = $this->repo->order->findByPublicIdAndMerchant($input[Entity::ENTITY_ID], $this->merchant);

                        if ($order->isPaid() === true)
                        {
                            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER);
                        }

                        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT_SERVICE], function () use ($input, $order) {
                            return $this->createForOrder($input, $order);
                        });

                        break;
                    case ConstantEntity::CHECKOUT_ORDER:
                        $checkoutOrder = $this->repo->checkout_order->findByPublicIdAndMerchant(
                            $input[Entity::ENTITY_ID], $this->merchant
                        );

                        $qrCode = Tracer::inspan(
                            ['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT_SERVICE],
                            function () use ($input, $checkoutOrder) {
                                return $this->createForCheckoutOrder($input, $checkoutOrder);
                        });
                }
            }
            else
            {
                $createArray = $this->computeInputForQrOnCheckout($input);

                $qrCode = (new Core)->buildQrCode($createArray);
            }
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        } finally {
            $input = array_merge($input, [
                Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
                Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
                Entity::REQUEST_SOURCE  => RequestSource::CHECKOUT,
            ]);

            (new Metric())->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        // TODO: Add status check here, use a separate config for checkout QRs maybe?

        if ($qrCode->isCheckoutQrCode()) {
            (new QrPayment\Service())->setQrCodeStatusAndPaymentIdInCache($qrCode);
        }

        $this->trace->info(TraceCode::QR_CODE_CHECKOUT_CREATED, $qrCode->toArrayPublic());

        $createInput = $this->computeInputForQrOnCheckout($input);

        $createInput[Entity::GATEWAY] = $qrCode->getGatewayFromQrString();

        $metric->pushCreateLatencyMetrics($createInput, $startTimeMs, $qrCode->getGatewayLatencyForQrCreate());

        return $qrCode->toArrayPublic();
    }

    public function createForPaymentLinks($input)
    {
        $startTimeMs = microtime(true) * 1000;

        $this->trace->info(TraceCode::QR_CODE_PAYMENT_LINKS_CREATE_REQUEST, $input);

        (new Validator())->validateInput('createForPaymentLinks', $input);

        $errorMessage = null;

        $metric = new Metric();

        try
        {
            $order = $this->repo->order->findByPublicIdAndMerchant($input[Entity::ENTITY_ID], $this->merchant);

            if ($order === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_INVALID_ORDER_ID);
            }

            if ($order->isPaid() === true)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER);
            }

            $qrCode = Tracer::inspan(
                ['name' => HyperTrace::QR_CODE_CREATE_FOR_PAYMENT_LINKS_SERVICE],
                function () use ($input, $order) {
                    return $this->createForPaymentLinksOrder($input, $order);
            });

        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        } finally {
            $input = array_merge($input, [
                Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
                Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
                Entity::REQUEST_SOURCE  => RequestSource::PAYMENT_LINKS,
            ]);

            (new Metric())->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        $this->trace->info(TraceCode::QR_CODE_PAYMENT_LINKS_CREATED, $qrCode->toArrayPublic());

        $createInput = $this->computeInputForQrOnPaymentLinksMetrics($input);

        $createInput[Entity::GATEWAY] = $qrCode->getGatewayFromQrString();

        $metric->pushCreateLatencyMetrics($createInput, $startTimeMs, $qrCode->getGatewayLatencyForQrCreate());

        return $this->getCreateForPaymentLinksResponse($qrCode);
    }


    /**
     * Create a QrCode entity for a checkout order.
     *
     * @param array $input
     * @param CheckoutOrder $checkoutOrder
     *
     * @return Entity
     *
     * @throws BadRequestException
     */
    private function createForCheckoutOrder(array $input, CheckoutOrder $checkoutOrder): Entity
    {
        if ($checkoutOrder->isClosed())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER);
        }

        $qrCode = $this->repo->qr_code->findActiveQrCodeByCheckoutOrder($checkoutOrder);

        if ($qrCode !== null)
        {
            return $qrCode;
        }

        $createArray = $this->computeInputForQrOnCheckout($input, $checkoutOrder);

        return (new Core())->buildQrCode($createArray, $checkoutOrder);
    }

    private function createForOrder($input, $order)
    {
        return $this->mutex->acquireAndRelease(
            $order->getId(),
            function() use ($order, $input)
            {
                $qrCode = $this->repo->qr_code->findActiveQrCodeByOrder($order);

                if ($qrCode !== null)
                {
                    return $qrCode;
                }

                $createArray = $this->computeInputForQrOnCheckout($input, $order);

                return (new Core)->buildQrCode($createArray, $order);
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);
    }

    private function createForPaymentLinksOrder($input, $order)
    {
        return $this->mutex->acquireAndRelease(
            $order->getId(),
            function() use ($order, $input)
            {
                $qrCode = $this->repo->qr_code->findActiveQrCodeByOrder($order); // fetches latest active qr

                if ($qrCode !== null)
                {
                    // if a QR already exists, make sure the user has enough buffer to finish the payment
                    // and only then reuse the same QR.
                    $buffer_time = $qrCode->getAttribute(Entity::CLOSE_BY) - Carbon::now()->getTimestamp(); // in seconds
                    if ($buffer_time >= 10*60)
                    {
                        return $qrCode;
                    }
                }

                $createArray = $this->computeInputForQrOnPaymentLinks($input, $order);

                return (new Core)->buildQrCode($createArray, $order);
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);
    }


    private function getCreateForPaymentLinksResponse($qrCode) : array
    {
        $response = $qrCode->toArrayPublic();

        $response[Entity::QR_STRING] = $qrCode[Entity::QR_STRING];

        return $response;
    }

    /**
     * @param array $input
     * @return array
     */
    private function computeInputForQrOnPaymentLinksMetrics(array $input): array
    {
        $createArray = [
            Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
            Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
            Entity::FIXED_AMOUNT    => true,
            Entity::REQUEST_SOURCE  => RequestSource::PAYMENT_LINKS,
        ];

        $createArray[Entity::CLOSE_BY] = $input[Entity::CLOSE_BY];

        $additionalAttributes = [Entity::CUSTOMER_ID, Entity::DESCRIPTION, Entity::NAME, Entity::NOTES];

        foreach ($additionalAttributes as $attribute) {
            if (!empty($input[$attribute])) {
                $createArray[$attribute] = $input[$attribute];
            }
        }

        return $createArray;
    }


    /**
     * @param array $input
     * @param Order $order
     *
     * @return array
     */
    private function computeInputForQrOnPaymentLinks(array $input, Order $order): array
    {
        $createArray = [
            Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
            Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
            Entity::FIXED_AMOUNT    => true,
            Entity::REQUEST_SOURCE  => RequestSource::PAYMENT_LINKS,
        ];

        $createArray[Entity::REQ_AMOUNT] = $order->getAmount();

        $createArray[Entity::CLOSE_BY] = $input[Entity::CLOSE_BY];

        $additionalAttributes = [Entity::CUSTOMER_ID, Entity::DESCRIPTION, Entity::NAME, Entity::NOTES];

        foreach ($additionalAttributes as $attribute) {
            if (!empty($input[$attribute])) {
                $createArray[$attribute] = $input[$attribute];
            }
        }

        return $createArray;
    }

    /**
     * @param array $input
     * @param CheckoutOrder|Order|null $order
     *
     * @return array
     */
    private function computeInputForQrOnCheckout(array $input, $order = null): array
    {
        $createArray = [
            Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
            Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
            Entity::FIXED_AMOUNT    => true,
            Entity::REQUEST_SOURCE  => RequestSource::CHECKOUT,
        ];

        if ($order !== null)
        {
            if ($order instanceof Order) {
                $createArray[Entity::REQ_AMOUNT] = $order->getAmountDue();
            }

            if ($order instanceof CheckoutOrder) {
                $createArray[Entity::REQ_AMOUNT] = $order->getFinalAmount();
                $createArray[Entity::CLOSE_BY] = $order->getExpireAt();
            }
        }
        else
        {
            $createArray[Entity::REQ_AMOUNT] = $input[Entity::REQ_AMOUNT];
            $createArray[Entity::CLOSE_BY]   = Carbon::now(Timezone::IST)
                                                     ->addSeconds(Constants::NO_ORDER_CHECKOUT_QR_DEFAULT_EXPIRY_WINDOW)
                                                     ->getTimestamp();
        }

        $additionalAttributes = [Entity::CUSTOMER_ID, Entity::DESCRIPTION, Entity::NAME, Entity::NOTES];

        foreach ($additionalAttributes as $attribute) {
            if (!empty($input[$attribute])) {
                $createArray[$attribute] = $input[$attribute];
            }
        }

        return $createArray;
    }

    public function closeQrCode(string $id, $closeReason = CloseReason::ON_DEMAND)
    {
        $this->trace->info(TraceCode::QR_CODE_CLOSE_REQUEST, ['id' => $id]);

        $errorMessage = null;

        $variant = $this->app->razorx->getTreatment($this->merchant->getId(), RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE, $this->mode);

        if ((strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON)
            and ($this->merchant->isFeatureEnabled(FeatureConstants::CLOSE_QR_ON_DEMAND) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);
        }

        try
        {
            $qrCode = (new Repository())->findByPublicIdAndMerchant($id, $this->merchant);

            if ($qrCode->isClosed() === true)
            {
                return $qrCode->toArrayPublic();
            }

            if ((strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON) and
                (((str_contains($qrCode['qr_string'], '@icici') === true) or
                  ($qrCode->isRazorpayPosQrCode()===true))===false))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);
            }

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_CLOSE_QR_CODE], function () use ($qrCode, $closeReason) {
                return (new Core)->close($qrCode, $closeReason);
            });

            $this->publishQrCodeEvent($qrCode, Event::CLOSED);

            return $qrCode->toArrayPublic();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CLOSE_REQUEST_FAILED, [
                'id' => $id
            ]);

            $errorMessage = $ex->getMessage();

            throw $ex;
        }
        finally
        {
            $requestSource = $qrCode ? $qrCode->getRequestSource() : null;

            (new Metric())->pushCloseMetrics($closeReason, $errorMessage, $requestSource);
        }
    }
    public function closeQrCodesBulk(array $input, $closeReason = CloseReason::COMPLIANCE)
    {
        $this->trace->info(TraceCode::QR_CODES_CLOSE_BULK_REQUEST,
            [
                'count' => count($input[Constants::IDS])
            ]);

        $errorMessage = null;

        (new Validator())->validateInput('closeQrCodesBulk', $input);

        $failed_ids = [];
        $failure_details = [];
        $success = 0;
        $failure = 0;

        foreach($input[Constants::IDS] as $id)
        {
            try
            {
                $qrCode = $this->repo->qr_code->findOrFailPublic(Entity::silentlyStripSign($id));

                if($qrCode->getStatus() === Status::CLOSED)
                {
                    $success++;
                    continue;
                }

                (new Core)->closeQrCodeAdmin($qrCode, $closeReason);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::QR_CODE_CLOSE_REQUEST_FAILED,
                    [
                        'id' => $id,
                    ]);

                $errorMessage = $ex->getMessage();

                $failed_ids[] = $id;

                $failure_details[$id] = $errorMessage;
                $failure++;
            }
            finally
            {
                $requestSource = $qrCode ? $qrCode->getRequestSource() : null;

                (new Metric())->pushCloseMetrics($closeReason, $errorMessage, $requestSource);
            }
        }

        return [
            'failed_ids' => $failed_ids,
            'failure_details' => $failure_details,
            'success' => $success,
            'failure' => $failure
        ];

    }

    public function fetchMultiple($input)
    {
        if (array_key_exists(QrPayment\Entity::PAYMENT_ID, $input))
        {
            if (count($input) > 1)
            {
                $this->trace->info(TraceCode::QR_CODE_FETCH_MULTIPLE_KEYS_SUPPLIED_WITH_PAYMENT_ID, $input);
            }

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH_MULTIPLE_PAYMENT_ID], function () use ($input) {
                return (new Repository())->fetchQrCodeForPaymentId($input[QrPayment\Entity::PAYMENT_ID], $this->merchant->getId());
            });

            return $qrCode->toArrayPublic();
        }

        $input[Entity::ENTITY_TYPE] = 'qr_code';

        $qrCodes = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH_MULTIPLE_FETCH_ALL], function () use ($input) {
            return (new Repository)->fetch($input, $this->merchant->getId());
        });

        return $qrCodes->toArrayPublic();
    }

    public function fetch($id)
    {
        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH], function () use ($id) {
            return (new Repository)->findByPublicIdAndMerchant($id, $this->merchant);
        });

        if ($this->merchant->isFeatureEnabled(FeatureConstants::UPIQR_V1_HDFC) !== true
            and $qrCode->source !== null )
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NON_EXISTING_QR_CODE_ID, Entity::ID, [$id]);
        }

        return $qrCode->toArrayPublic();
    }

    public function publishQrCodeEvent($entity, $event)
    {
        try
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN => $entity
            ];

            Event::checkEvent($event);

            $event = 'api.qr_code.' . $event;

            $this->app['events']->dispatch($event, $eventPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::QR_CODE_WEBHOOK_PUBLISH_FAILED, [
                'entity' => $entity->toArrayPublic(),
                'event'  => $event
            ]);
        }
    }

    public function handleReminderForQrCode($qrCode)
    {
        $startTimeMs = microtime(true) * 1000;
        if (empty($qrCode->getCloseBy()))
        {
            return;
        }

        try
        {
            $request = [
                'entity_id'     => $qrCode->getId(),
                'namespace'     => Constants::REMINDER_NAMESPACE,
                'entity_type'   => Constants::REMINDER_ENTITY_NAME,
                'reminder_data' => [ENTITY::CLOSE_BY => $qrCode->getCloseBy()],
                'callback_url'  => $this->getCallbackUrlForReminder($qrCode),
            ];

            $merchantId = Account::SHARED_ACCOUNT;

            $response = $this->app['reminders']->createReminder($request, $merchantId);

            $this->trace->info(TraceCode::QR_CODE_REMINDER_RESPONSE, $response);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_REMINDER_CREATION_FAILED, $request);
        }
        finally
        {
            // Histogram metrics for Reminder registration latency
            $processingTimeMs = (microtime(true) * 1000) - $startTimeMs;

            $this->trace->histogram(Metric::QR_CODE_REMINDER_REGISTRATION_LATENCY, $processingTimeMs,
                                    ['namespace' => Constants::REMINDER_NAMESPACE]);

            //Counter metrics for reminders response status codes
            $status = 500;

            if (isset($response) === true and
                isset($response['status_code']) === true)
            {
                $status = $response['status_code'];
            }

            $dimensions = [
                'namespace'   => Constants::REMINDER_NAMESPACE,
                'status_code' => $status
            ];

            $this->trace->count(Metric::QR_CODE_REMINDER_RESPONSE_STATUS_CODE, $dimensions);

        }
    }

    public function getCallbackUrlForReminder($qrCode)
    {
        $baseUrl     = Constants::REMINDER_BASE_URL;

        $mode        = $this->mode;

        $entity      = Constants::REMINDER_ENTITY_NAME;

        $namespace   = Constants::REMINDER_NAMESPACE;

        $qrCodeId    = $qrCode->getPublicId();

        return sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $qrCodeId);
    }

    public function getStatusCheckCallbackUrlForReminder($qrCode)
    {
        $baseUrl     = Constants::REMINDER_BASE_URL;

        $mode        = $this->mode;

        $entity      = Constants::REMINDER_ENTITY_NAME;

        $namespace   = Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK;

        $qrCodeId    = $qrCode->getPublicId();

        return sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $qrCodeId);
    }

    private function getRequestSourceViaAuth()
    {
        if ($this->auth->isPublicAuth())
        {
            return RequestSource::CHECKOUT;
        }
        elseif ($this->auth->isProxyAuth())
        {
            return RequestSource::DASHBOARD;
        }
        else
        {
            return RequestSource::API;
        }
    }

    public function triggerQrStatusCheckPostCreate(Entity $qrCode): void
    {
        try
        {
            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_INIT, ['id' => $qrCode->getId()]);

            // Find the env variable QR_CODE_STATUS_CHECK_SPLITZ_EXPERIMENT_ID to find experiment IDs for different envs

            $request = [
                'entity_id'     => $qrCode->getId(),
                'namespace'     => Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK,
                'entity_type'   => Constants::REMINDER_ENTITY_NAME,
                // Add 180 secs to signify sending reminder after 3 mins of create
                'reminder_data' => [ENTITY::CREATED_AT => $qrCode->getCreatedAt()],
                'callback_url'  => $this->getStatusCheckCallbackUrlForReminder($qrCode),
            ];

            $merchantId = Account::SHARED_ACCOUNT;
            $startTimeMs = microtime(true) * 1000;

            $response = $this->app['reminders']->createReminder($request, $merchantId);

            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_REMINDER_RESPONSE, $response);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex,
                Trace::CRITICAL,
                TraceCode::QR_CODE_STATUS_CHECK_REMINDER_CREATION_FAILED,
                $request);
        }
        finally
        {
            // Histogram metrics for Reminder registration latency
            $processingTimeMs = (microtime(true) * 1000) - $startTimeMs;
            $this->trace->histogram(Metric::QR_CODE_REMINDER_REGISTRATION_LATENCY, $processingTimeMs,
                                    ['namespace' => Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK]);

            //Counter metrics for reminders response status codes
            $status = 500;

            if (isset($response) === true and
                isset($response['status_code']) === true)
            {
                $status = $response['status_code'];
            }

            $dimensions = [
                'namespace'   => Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK,
                'status_code' => $status
            ];

            $this->trace->count(Metric::QR_CODE_REMINDER_RESPONSE_STATUS_CODE, $dimensions);
        }
    }

    /**
     * This method is used to actively delete the reminder for a QR code after a payment against it has been created.
     * This method does not check if a reminder was actually created for that QR or not.
     * What this means is that we will be getting 404 for quite a few delete reminders requests.
     * It should be fine for now.
     *
     * @param string $qrCodeId The ID of the QR code whose status check reminder needs to be deleted
     *
     * @return void
     */
    public function deleteActiveReminderForStatusCheck(string $qrCodeId)
    {
        try
        {
            if ($qrCodeId === Entity::SHARED_ID)
            {
                return;
            }

            $this->trace->info(
                TraceCode::QR_CODE_STATUS_CHECK_REMINDER_DELETE,
                [
                    'id' => $qrCodeId,
                ]
            );

            Entity::silentlyStripSign($qrCodeId);

            $this->app['reminders']
                ->disableReminderUsingEntityIdAndNamespace(
                    $qrCodeId,
                    Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK,
                    Account::SHARED_ACCOUNT
                );
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::QR_CODE_STATUS_CHECK_REMINDER_DELETION_FAILED,
            );
        }
    }

    public function evaluateQrCodeEligibilityViaSplitzForStatusCheck(Entity $qrCode): bool
    {
        try
        {
            $properties = [
                'id'            => $qrCode->getMerchantId(),
                'experiment_id' => $this->app['config']->get('app.qr_code_status_check_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $qrCode->getMerchantId()]),
            ];
            $response   = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'merchant_id'   => $qrCode->getMerchantId(),
                '$response'     => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if (($key == "result") and
                        ($value == "on"))
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::QR_CODE_STATUS_CHECK_SPLITZ_EVALUATE_ERROR
            );
        }

        return false;
    }

    /**
     * Check if it has been 12 hours since QR code creation, we will not do QR Code status check post this.
     * We expect the recon flow to now bring in any payment data later if needed.
     *
     * @param Entity $qrCode The QR Code entity to check
     * @return bool Returns true if the time has exceeded
     */
    protected function checkIfQrCodeStatusCheckTimeHasExceeded(Entity $qrCode): bool
    {
        $qrCodeCreatedAt     = Carbon::createFromTimestamp($qrCode->getCreatedAt());
        $currentTime         = Carbon::now();

        // if the current time and time of creation are more than 12 hours apart, we stop status check.
        if ($currentTime->diffInHours($qrCodeCreatedAt) > 12)
        {
            return true;
        }

        return false;
    }

    /**
     * When we receive a callback from Reminders, we check if we actually need to perform a status check on the QR or
     * not. We perform the following checks-
     * 1. If a payment already exists against the QR or not.
     * 2. If the QR has expired or not.
     * 3. If it has been too long since the creation of the QR or not.
     *
     * @param string $qrCodeId The ID of the QR code to be validated
     * @return bool Returns true if the QR needs to be dispatched for Status Check
     */
    public function validateQrForStatusCheckInit(string $qrCodeId, $input = null): bool
    {
        /**
         * @var $qrCode Entity
         */
        $qrCode = $this->repo->qr_code->find($qrCodeId);

        if (empty($qrCode) === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_NOT_FOUND,
                [
                    'id' => $qrCodeId,
                ]
            );

            return false;
        }

        //Counter metrics for Reminder callback latency SLA breach cases
        if (isset($input) === true and isset($input['reminder_count']) === true)
        {
            $qrCreationTime = $qrCode->getCreatedAt();
            $reminderCount  = $input['reminder_count']; //indicates which reminder is this

            //calculate difference between qr creation and reminder callback time (latency)
            $difference = microtime(true) - $qrCreationTime;
            $dimensions = [
                'reminder_count'    => $reminderCount,
                'reminder_callback' => $difference
            ];
            $this->trace->count(Metric::QR_STATUS_CHECK_REMINDER_CALLBACK_LATENCY, $dimensions);
        }

        $this->app['basicauth']->setMerchantById($qrCode->getMerchantId());

        // Check if there are no payments associated
        if ($qrCode->getPaymentsCountReceived() > 0) {

            $this->trace->info(
                TraceCode::PAYMENT_ALREADY_EXISTS_FOR_QR_CODE,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        if ($qrCode->isClosed() === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_CLOSED,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        if ($this->checkIfQrCodeStatusCheckTimeHasExceeded($qrCode) === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_STATUS_CHECK_TIME_EXCEEDED,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $id The ID of the QR code to be checked
     * @param array $input The input received in the Reminders service callback request
     * @return bool To be consumed by the Reminders service. True indicates no reminders are needed further
     */
    public function initQrStatusCheck(string $id, array $input): bool
    {
        Entity::silentlyStripSign($id);

        $response = false;

        // If the validations fail, we return true to reminders to stop sending further reminders
        if ($this->validateQrForStatusCheckInit($id, $input) === false)
        {
            $response = true;
        }
        else
        {
            $response = (new Core())->dispatchQrCodeToStatusCheckQueue($id);
        }

        $this->trace->info(TraceCode::QR_STATUS_CHECK_RESPONSE, ['id' => $id, 'response' => $response]);

        return $response;
    }

    public function triggerQrStatusCheckForPaymentFetch(string $id): void
    {
        $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_INIT_IN_PAYMENT_FETCH, ['id' => $id]);

        Entity::silentlyStripSign($id);

        /**
         * @var $qrCode Entity
         */
        $qrCode = $this->repo->qr_code->find($id);

        // If the QR code is not found, no point of dispatching it for status check
        if ($qrCode === null)
        {
            $this->trace->info(TraceCode::QR_CODE_NOT_FOUND, ['id' => $id]);
            return;
        }

        // If it has not yet been 3 minutes between QR Code create and now, don't dispatch for status check
        // for ezetap Request, we will skip below restriction, as ezetap will call fetch api after around 30 sec of QR Generation
        if (($qrCode->getRequestSource() !== RequestSource::EZETAP) and
            (abs((Carbon::now(Timezone::IST)->timestamp) - $qrCode->getCreatedAt()) <= 180))
        {
            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_TIME_TOO_EARLY, ['id' => $id]);
            return;
        }

        if (
                ($qrCode->getUsageType() === UsageType::SINGLE_USE) and
                (($qrCode->getProvider() === Provider::UPI_QR) and
                    (in_array($qrCode->getGatewayFromQrString(), self::$qrStatusCheckGateways, true) === true)) or
                (($qrCode->getProvider() === Provider::BHARAT_QR) and
                    (in_array($qrCode->getGatewayFromQrString(), self::$qrBharatQrStatusCheckGateways, true) === true))
            )
        {
            // After dispatch, when the worker picks the message up, the worker performs other validations too
            // Since the dispatch step has a unique job check, we won't be dispatching multiple messages for the same
            // QR code at once.
            (new Core())->dispatchQrCodeToStatusCheckQueue($id, $qrCode->getRequestSource());
        }
    }
}
