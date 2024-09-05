<?php

namespace RZP\Models\QrPaymentRequest;

use Monolog\Level;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\QrCode\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\QrGatewayModule\QrGatewayModule;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Generator;

class Core extends Base\Core
{
    protected $repo;

    const FAILED_CALLBACK = 'failed callback';

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository();
    }

    public function create(array $input, $requestPayload, $isFailed = false)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_SAVE_REQUEST,
            [
                Entity::QR_CODE_ID            => $input[Entity::QR_CODE_ID],
                Entity::TRANSACTION_REFERENCE => $input[Entity::TRANSACTION_REFERENCE]
            ]
        );

        $qrPaymentRequest = new Entity();

        if ($isFailed === true)
        {
            $qrPaymentRequest->setFailureReasonIfNotSet(self::FAILED_CALLBACK);
        }

        $qrPaymentRequest->setRequestPayload($requestPayload);

        $qrPaymentRequest->findAndSetRequestSource($requestPayload);

        $qrPaymentRequest->setCreated(false);

        $qrPaymentRequest->build($input);

        $this->repo->saveOrFail($qrPaymentRequest);

        $this->trace->info(
            TraceCode::QR_PAYMENT_REQUEST_SAVED,
            [
                Entity::ID                    => $qrPaymentRequest->getPublicId(),
                Entity::TRANSACTION_REFERENCE => $qrPaymentRequest->getTransactionReference(),
            ]
        );

        //QR Payment creation counter metric source wise
        if (!empty($qrPaymentRequest->getAttribute(Entity::REQUEST_SOURCE)) === true)
        {
            $reqSource = (json_decode($qrPaymentRequest->getAttribute(Entity::REQUEST_SOURCE), true));
            if (isset($reqSource['source']) === true)
            {
                $dimensions = [
                    'source' => $reqSource['source'],
                ];
                $this->trace->count(Metric::QR_PAYMENT_CREATION_SOURCE, $dimensions);
            }
        }
        return $qrPaymentRequest;
    }

    public function update($qrPaymentRequest)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_UPDATE_REQUEST,
            [
                Entity::ID                    => $qrPaymentRequest->getPublicId(),
                Entity::QR_CODE_ID            => $qrPaymentRequest->getQrCodeId(),
                Entity::TRANSACTION_REFERENCE => $qrPaymentRequest->getTransactionReference(),
            ]
        );

        $this->repo->save($qrPaymentRequest);
    }

    public function qrPaymentStatusCheck($qrCode, &$qrVariant)
    {
        $resp = null;

        $terminal = (new Generator())->fetchDedicatedTerminalFromQrString($qrCode);

        if ($terminal->getGateway() === \RZP\Models\Payment\Gateway::UPI_RZPAPB)
        {
            return $this->checkQrPaymentStatusThroughGatewayModuleForUpiRzpapb($qrCode, $terminal);
        }

        $qrVariant = strtolower(
                $this->app->razorx->getTreatment(
                    $terminal->getGateway(),
                    RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY,
                    $this->mode
                )) === RazorxTreatment::RAZORX_VARIANT_ON;

        if ($qrVariant === true)
        {
            return $this->checkQrPaymentStatusThroughGatewayModule($qrCode, $terminal);
        }

        if (($terminal->getGateway() === \RZP\Models\Payment\Gateway::UPI_MINDGATE) or
            ($terminal->getGateway() === \RZP\Models\Payment\Gateway::UPI_AIRTEL))
        {
            $terminalArray = $terminal->toArrayWithPassword();
        }
        else
        {
            $terminalArray = $terminal->toArray();
        }

        $input = [
            EntityConstants::QR_CODE  => $qrCode->toArray(),
            EntityConstants::TERMINAL => $terminalArray,
            EntityConstants::MERCHANT => $qrCode->merchant,
        ];

        $gatewayClass = $this->app['gateway']->gateway($terminal->getGateway());

        if (method_exists($gatewayClass, 'getQrPaymentStatus') === true)
        {
            $startTimeMs = microtime(true) * 1000;
            try
            {
                $gatewayClass->setGatewayParams($input, $this->mode, $terminal);

                $resp = $gatewayClass->getQrPaymentStatus($input);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::QR_STATUS_CHECK_MOZART_SERVICE_UNEXPECTED_RESPONSE,
                    ['id' => $qrCode->getId()]
                );
            }
            finally
            {
                // Histogram metrics for status check gateway latency
                $gatewayProcessingTimeMs = (microtime(true) * 1000) - $startTimeMs;
                $gateway = '';
                if (isset($resp) === true and isset($resp['gateway']) === true)
                {
                    $gateway = $resp['gateway'];
                }
                $this->trace->histogram(Metric::QR_STATUS_CHECK_GATEWAY_LATENCY, $gatewayProcessingTimeMs,
                                        ['gateway' => $gateway]);
            }
        }

        return $resp;
    }

    public function getPayerNameBasedOnRefId($reference_id)
    {
        try
        {
            $qrPaymentRequest = $this->repo->fetchPaymentReference($reference_id);
            $reqPayload = $qrPaymentRequest->getRequestPayload();

            if (empty($reqPayload) === true)
            {
                $this->trace->traceException(
                    TraceCode::QR_REQUEST_PAYLOAD_EMPTY,
                    []);
                return null;
            }

            $jsonReqPayload = json_decode($reqPayload);

            if (empty($jsonReqPayload) === true)
            {
                $this->trace->traceException(
                    TraceCode::QR_JSON_REQUEST_PAYLOAD_EMPTY,
                    []);
                return null;
            }

            $jsonReqPayloadArray = get_object_vars($jsonReqPayload);

            if (array_key_exists('PayerName',$jsonReqPayloadArray) === false)
            {
                $this->trace->traceException(
                    TraceCode::QR_PAYER_NAME_EMPTY,
                    []);
                return null;
            }

            return $jsonReqPayloadArray['PayerName'];
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                TraceCode::QR_PAYER_NAME_EMPTY,
                []);
            return null;
        }
    }

    protected function checkQrPaymentStatusThroughGatewayModule($qrCode, \RZP\Models\Terminal\Entity $terminal): ?array
    {
        $startTimeMs = microtime(true) * 1000;
        $resp = null;

        try
        {
            $resp = (new QrGatewayModule($this->app))->checkQrPaymentStatus($qrCode, $terminal);
        }
        catch (GatewayErrorException $ge)
        {
            $gatewayData = $ge->getData();

            $this->trace->traceException(
                $ge,
                Level::Error,
                TraceCode::QR_STATUS_CHECK_GATEWAY_FAILURE,
                [
                    'gateway' => $terminal->getGateway(),
                    'gateway_data' => $gatewayData['data'],
                ]
            );

            if ($gatewayData["error"]["code"] === "FAILED")
            {
                (new \RZP\Models\QrPayment\Service())->createQrPaymentRequestForFailureInStatusCheck(
                    $qrCode, $gatewayData['data'], $terminal->getGateway());
            }
        }
        catch (IntegrationException $ie)
        {
            $exceptionResponseData = $ie->getData();
            $errorData = json_decode($exceptionResponseData['body'], true, flags:JSON_THROW_ON_ERROR);

            // If it was actually a gateway level failure, then error object and error code will be populated in data
            // So, we can record a qr payment request against it
            if (empty($errorData["data"]["error"]["code"]) === false and
                $errorData["data"]["error"]["code"] === "FAILED")
            {
                $this->trace->traceException(
                    $ie,
                    Level::Error,
                    TraceCode::QR_STATUS_CHECK_GATEWAY_FAILURE,
                    [
                        'gateway' => $terminal->getGateway(),
                    ]
                );

                (new \RZP\Models\QrPayment\Service())->createQrPaymentRequestForFailureInStatusCheck(
                    $qrCode, $errorData['data'], $terminal->getGateway());
            }

            // else, it is not a transaction related gateway failure, it is a platform related failure
            $this->trace->traceException(
                $ie,
                Level::Error,
                TraceCode::QR_STATUS_CHECK_MOZART_SERVICE_UNEXPECTED_RESPONSE,
                [
                    'gateway' => $terminal->getGateway(),
                ]
            );
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::QR_STATUS_CHECK_MOZART_SERVICE_UNEXPECTED_RESPONSE,
                ['id' => $qrCode->getId()]
            );

            throw $ex;
        }
        finally
        {
            // Histogram metrics for status check gateway latency
            $gatewayProcessingTimeMs = (microtime(true) * 1000) - $startTimeMs;

            $gateway = $response['terminal']['gateway'] ?? '';

            $this->trace->histogram(
                Metric::QR_STATUS_CHECK_GATEWAY_LATENCY, $gatewayProcessingTimeMs,
                [
                    'gateway' => $gateway,
                ]
            );
        }

        return $resp;
    }

    protected function checkQrPaymentStatusThroughGatewayModuleForUpiRzpapb($qrCode, \RZP\Models\Terminal\Entity $terminal): ?array
    {
        $startTimeMs = microtime(true) * 1000;
        $resp = null;

        try
        {
            $resp = (new QrGatewayModule($this->app))->checkQrPaymentStatusForUpiRzpapb($qrCode, $terminal);
        }
        catch (GatewayErrorException $ge)
        {
            $gatewayData = $ge->getData();

            $this->trace->traceException(
                $ge,
                Level::Error,
                TraceCode::QR_STATUS_CHECK_GATEWAY_FAILURE,
                [
                    'gateway' => $terminal->getGateway(),
                    'gateway_data' => $gatewayData['data'],
                ]
            );

            if ($gatewayData["error"]["code"] === "FAILED")
            {
                (new \RZP\Models\QrPayment\Service())->createQrPaymentRequestForFailureInStatusCheck(
                    $qrCode, $gatewayData['data'], $terminal->getGateway());
            }
        }
        catch (IntegrationException $ie)
        {
            $exceptionResponseData = $ie->getData();
            $errorData = json_decode($exceptionResponseData['body'], true, flags:JSON_THROW_ON_ERROR);

            // If it was actually a gateway level failure, then error object and error code will be populated in data
            // So, we can record a qr payment request against it
            if (empty($errorData["data"]["error"]["code"]) === false and
                $errorData["data"]["error"]["code"] === "FAILED")
            {
                $this->trace->traceException(
                    $ie,
                    Level::Error,
                    TraceCode::QR_STATUS_CHECK_GATEWAY_FAILURE,
                    [
                        'gateway' => $terminal->getGateway(),
                    ]
                );

                (new \RZP\Models\QrPayment\Service())->createQrPaymentRequestForFailureInStatusCheck(
                    $qrCode, $errorData['data'], $terminal->getGateway());
            }

            // else, it is not a transaction related gateway failure, it is a platform related failure
            $this->trace->traceException(
                $ie,
                Level::Error,
                TraceCode::QR_STATUS_CHECK_MOZART_SERVICE_UNEXPECTED_RESPONSE,
                [
                    'gateway' => $terminal->getGateway(),
                ]
            );
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::QR_STATUS_CHECK_MOZART_SERVICE_UNEXPECTED_RESPONSE,
                ['id' => $qrCode->getId()]
            );

            throw $ex;
        }
        finally
        {
            // Histogram metrics for status check gateway latency
            $gatewayProcessingTimeMs = (microtime(true) * 1000) - $startTimeMs;

            $gateway = $response['terminal']['gateway'] ?? '';

            $this->trace->histogram(
                Metric::QR_STATUS_CHECK_GATEWAY_LATENCY, $gatewayProcessingTimeMs,
                [
                    'gateway' => $gateway,
                ]
            );
        }

        return $resp;
    }

}
