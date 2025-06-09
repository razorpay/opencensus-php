<?php

namespace RZP\Models\QrGatewayModule;

use App;
use Cache;
use Carbon\Carbon;

use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr;
use RZP\Models\QrPayment;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\QrCode\Entity as QrCodeEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVaQrCodeEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\InvoiceDetails as InvoiceDetails;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;


/**
 * This class is to be used by all QR Code and QR Payment related operations to communicate with Mozart.
 */
class QrGatewayModule
{
    const QR_GATEWAY_CACHE_PREFIX = 'qr_gateway_';
    const QR_EXISTING_GATEWAY_CACHE_PREFIX = 'qr_existing_gateway_';
    const GST_KEY_VALUE_DELIMITER = ':';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        if (isset($this->app['rzp.mode'])) {
            $this->mode = $this->app['rzp.mode'];
        }
        else
        {
            $this->mode = Mode::LIVE;
            $this->app['rzp.mode'] = Mode::LIVE;
        }
    }

    public function checkForQrPaymentProcessing(array $input, $gatewayDriver, $paymentId)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_CALLBACK_CHECK_INIT,
            [
                'gateway'            => $gatewayDriver,
                'merchant_reference' => $paymentId,
            ]
        );

        $data = null;

        if (static::checkIfOldGatewayProcessedThroughNewQrPaymentProcessingFlow($gatewayDriver) === true)
        {
            $data = (new QrPayment\Service())
                ->processQrPaymentCallbackThroughNewGatewayAdapterForExistingGateways(
                    $gatewayDriver,
                    $input['data'],
                    $input['success']
                );
        }
        else
        {
            $data = $this->processExistingGatewayCallbackThroughOldFlow($input, $paymentId, $gatewayDriver);
        }

        if (empty($data) === false)
        {
            $this->trace->info(
                TraceCode::QR_PAYMENT_CALLBACK_CHECK_COMPLETE,
                [
                    'gateway'            => $gatewayDriver,
                    'merchant_reference' => $paymentId,
                    'response'           => $data,
                ]
            );
        }
        else
        {
            $this->trace->info(
                TraceCode::QR_PAYMENT_CALLBACK_CHECK_FAILED,
                [
                    'gateway'            => $gatewayDriver,
                    'merchant_reference' => $paymentId,
                ]
            );
        }

        return $data;
    }

    protected function processExistingGatewayCallbackThroughOldFlow($input, $paymentId, $gatewayDriver)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_CALLBACK_CHECK_FINDING_QR_CODE,
            [
                'merchant_reference' => $paymentId,
                'gateway'            => $gatewayDriver,
            ]
        );

        $data = null;

        // First if mode is not found from payment repo, we will check with QR repo
        $qrRepo = $this->app['repo']->qr_code;

        $suffixLength = strlen(QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        $gatewayClass = $this->app['gateway']->gateway($gatewayDriver);

        $isQrV2Payment = false;

        $terminal = null;

        // this checks will only be applicable for static QR code. For dynamic QR code,
        // bank will send the ref id generated during QR creation
        if ((strlen($paymentId) >= ($suffixLength + QrCode\Entity::ID_LENGTH)) and
            (str_ends_with($paymentId, QrCode\Constants::QR_CODE_V2_TR_SUFFIX)))
        {
            if (method_exists($gatewayClass, 'getQrPaymentMerchantReference') === true)
            {
                $paymentId = $gatewayClass->getQrPaymentMerchantReference($paymentId);
            }
            else
            {
                $paymentId = substr($paymentId, 0, QrCode\Entity::ID_LENGTH);
            }

            $isQrV2Payment = true;
        }
        else
        {
            $gatewayClass = $this->app['gateway']->gateway($gatewayDriver);
            if (method_exists($gatewayClass, 'getParsedDataFromUnexpectedCallback') === false)
            {
                return null;
            }

            $parsedData   = $gatewayClass->getParsedDataFromUnexpectedCallback($input);

            if (empty($parsedData['terminal']) === true)
            {
                return null;
            }

            $terminal = $this->app['repo']->terminal->findByGatewayAndTerminalData($gatewayDriver, $parsedData['terminal']);

            if (($terminal !== null) and ($terminal->isQrV2Terminal() === true) and
                ((new QrPayment\Core)->checkPaymentViaQRv1($terminal->merchant) === false))
            {
                $isQrV2Payment = true;

                $staticQrId = (new BharatQr\Service)->updateQrCodeInCallbackIfApplicable($input, $terminal);

                if ($staticQrId !== null)
                {
                    $paymentId = $staticQrId;
                }
            }
        }

        $mode = $qrRepo->determineLiveOrTestModeByMerchantReference($paymentId);

        if ($mode !== null)
        {
            $this->trace->info(
                TraceCode::QR_PAYMENT_CALLBACK_CHECK_FOUND_QR_CODE,
                [
                    'gateway'            => $gatewayDriver,
                    'merchant_reference' => $paymentId,
                    'is_qr_v2_payment'   => $isQrV2Payment,
                ]
            );

            $this->app['basicauth']->setModeAndDbConnection($mode);

            if ($isQrV2Payment === true)
            {
                $this->trace->info(TraceCode::QR_PAYMENT_GATEWAY_CALLBACK, $input);

                $data = (new BharatQr\Service)->processPayment($input, $gatewayDriver);
            }
            else
            {
                $data = (new QrCode\Upi\Service)->processPayment($input, $paymentId, $gatewayDriver);
            }
        }

        return $data;
    }

    public function generateIntentQr(QrCodeEntity $qrCode, TerminalEntity $terminal): array
    {
        $merchant = $qrCode->merchant;

        $input = [
            EntityConstants::QR_CODE         => $qrCode->toArray(),
            EntityConstants::TERMINAL        => $terminal->toArrayWithPassword(),
            EntityConstants::MERCHANT        => $merchant->toArray(),
            EntityConstants::MERCHANT_DETAIL => $merchant->merchantDetail->toArray(),
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::INTENT_QR,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function generateIntentQrForUpiRzpApb(QrCodeEntity $qrCode, TerminalEntity $terminal, string $upiMode): array
    {
        $merchant = $qrCode->merchant;
        $notes = $qrCode->getNotes();

        $qrReferenceId = empty($qrCode->getQrString()) === true ? $qrCode->getId().'qrv2' :  $qrCode->getReference();

        $input = [
            EntityConstants::PAYMENT  => [
                'id'       => $qrReferenceId,
                'amount'   => $qrCode->getAmount(),
                'currency' => 'INR',
            ],
            EntityConstants::TERMINAL => $terminal->toArray(),
            EntityConstants::MERCHANT => $merchant->toArray(),
            'metadata'                => [
                'flow'   => 'intent',
                'remark' => 'Payment To ' . $merchant->getFilteredDba(),
            ],
            EntityConstants::UPI      => [
                'merchant_reference' => $qrReferenceId,
                'mode'               => $upiMode,
            ],
            EntityConstants::QR_CODE  => [
                'id'          => $qrCode->getId(),
                'reference'   => $qrReferenceId,
                'close_by'    => $qrCode->getCloseBy(),
            ],
        ];

        $this->getTaxDetailsForRzpApb($qrCode, $input);

        if (empty($notes['payment_context']) === false)
        {
            $input['metadata']['payment_context'] = strtoupper($notes['payment_context']);
        }

        if (empty($input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE]) === false)
        {
            $input['metadata']['gst_enabled'] = true;
        }
        else
        {
            // if tax invoice array is empty, it means getTaxDetailsForRzpApb() returned as one of the conditions
            // described by upi_rzpapb spec turned out to be false
            // Read the implementation of getTaxDetailsForRzpApb() for details
            $input['metadata']['gst_enabled'] = false;
        }

        // Using the UPI Payments namespace here as we wanted to make the best of existing UPS integration for quick
        // shipping for GFF. Ideal approach would be a new Mozartv2 integration.
        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::UPI_PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::PAY_INIT,
            input      : $input,
            addEntities: false
        );

        // Added this brute-force handling as the code is not calling Mozartv2 as of now
        $response['data'][EntityConstants::QR_CODE][QrCodeEntity::REFERENCE] =
            $response['data'][EntityConstants::UPI][\RZP\Gateway\Upi\Base\Entity::MERCHANT_REFERENCE];

        // Added this brute-force handling as the code is not calling Mozartv2 as of now
        $response['data'][EntityConstants::QR_CODE][QrCodeEntity::QR_STRING] =
            $response['next']['intent_url'];

        return $response['data'];
    }

    public function getTaxDetailsForRzpApb($qrCode, &$input)
    {
        $invoiceDetails = $qrCode->getTaxInvoice();

        // Switch won't create a GST QR if the below condition holds true
        // Read- https://docs.google.com/document/d/1Nec4mgpijP5K7JN_cDgqp3b4JeqjUNondKttshPSwDo/edit#heading=h.t66228qh55aw
        if ((empty($invoiceDetails) === true) or
            (empty($invoiceDetails[InvoiceDetails::INVOICE_NUMBER]) === true) or
            (empty($invoiceDetails[InvoiceDetails::CUSTOMER_NAME]) === true) or
            (empty($invoiceDetails[InvoiceDetails::GST_AMOUNT]) === true))
        {
            return;
        }

        foreach ($invoiceDetails as $key => $value)
        {
            switch ($key)
            {
                case InvoiceDetails::BUSINESS_GSTIN:

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::BUSINESS_GSTIN]
                        = $invoiceDetails[InvoiceDetails::BUSINESS_GSTIN];

                    break;

                case InvoiceDetails::INVOICE_NUMBER:

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::INVOICE_NUMBER]
                        = $invoiceDetails[InvoiceDetails::INVOICE_NUMBER];

                    break;

                case InvoiceDetails::INVOICE_DATE:

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::INVOICE_DATE]
                        = $invoiceDetails[InvoiceDetails::INVOICE_DATE];

                    break;

                case InvoiceDetails::CUSTOMER_NAME:

                    $filteredCustomerName = preg_replace('/[^A-Za-z0-9]/',
                                                         '',
                                                         $invoiceDetails[InvoiceDetails::CUSTOMER_NAME]);

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::CUSTOMER_NAME]
                        = $filteredCustomerName;

                    break;

                case InvoiceDetails::GST_AMOUNT:
                    $gstAmount = $invoiceDetails[InvoiceDetails::GST_AMOUNT];

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::GST_AMOUNT]
                        = $gstAmount;

                    if (array_key_exists(InvoiceDetails::SUPPLY_TYPE, $invoiceDetails) and
                        $invoiceDetails[InvoiceDetails::SUPPLY_TYPE] === InvoiceDetails::SUPPLY_TYPE_INTERSTATE)
                    {
                        $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE]['igst_amount'] = $gstAmount;
                    }
                    else
                    {
                        // Tax in paise should always be even, as rupee amount of gst should always be integral
                        // Read- https://cleartax.in/s/rounding-off-tax-section-170-gst
                        // CGST = SGST = GST/2
                        $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE]['sgst_amount'] = $gstAmount / 2;
                        $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE]['cgst_amount'] = $gstAmount / 2;
                    }

                    break;

                case InvoiceDetails::CESS_AMOUNT:
                    if ($invoiceDetails[InvoiceDetails::CESS_AMOUNT] === 0)
                    {
                        break;
                    }

                    $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::CESS_AMOUNT]
                        = $invoiceDetails[InvoiceDetails::CESS_AMOUNT];

                    break;

                default:
                    break;
            }
        }

        if (empty($input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::INVOICE_DATE]) === true)
        {
            $input[EntityConstants::QR_CODE][NonVaQrCodeEntity::TAX_INVOICE][InvoiceDetails::INVOICE_DATE]
                = Carbon::now(Timezone::IST)->timestamp;
        }
    }

    public function checkQrPaymentStatus(QrCodeEntity $qrCode, TerminalEntity $terminal): array
    {
        $merchant = $qrCode->merchant;

        $input = [
            EntityConstants::QR_CODE         => $qrCode->toArray(),
            EntityConstants::TERMINAL        => $terminal->toArrayWithPassword(),
            EntityConstants::MERCHANT        => $merchant->toArray(),
            EntityConstants::MERCHANT_DETAIL => $merchant->merchantDetail->toArray(),
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::VERIFY_QR,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function checkQrPaymentStatusForUpiRzpapb(QrCodeEntity $qrCode, TerminalEntity $terminal): array
    {
        $merchant = $qrCode->merchant;

        $input = [
            EntityConstants::PAYMENT  => [
                'id'       => $qrCode->getId() . 'qrv2',
                'amount'   => $qrCode->getAmount(),
                'currency' => 'INR',
            ],
            EntityConstants::TERMINAL => $terminal->toArray(),
            EntityConstants::MERCHANT => $merchant->toArray(),
            EntityConstants::UPI      => [
                'merchant_reference' => $qrCode->getId() . 'qrv2',
            ],
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::UPI_PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::VERIFY,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function preProcessQrCallback($callbackData, string $gateway): array
    {
        $id = "";
        if ($gateway === Gateway::HDFC_MINTOAK)
        {
            if (empty($callbackData['terminalId']) === true)
            {
                throw new Exception\BadRequestValidationFailureException('terminalId missing in callback data.');
            }

            // Fetch terminal entity using terminalId from the payload
            $terminal = $this->app['repo']->terminal->findByGatewayMerchantId($callbackData['terminalId'], $gateway);

            if ($terminal === null)
            {
                throw new Exception\BadRequestValidationFailureException('Terminal not found for given terminalId and gateway.');
            }
            // Add internal terminal id to input
            $id = $terminal->getId();
            $payload = json_encode($callbackData, JSON_THROW_ON_ERROR);

            $input = [
                'payload' => $payload,
                'gateway' => $gateway,
                'terminal' => [
                    'id'     => $id,
                ],
            ];

        }
        else
        {
            $payload = json_encode($callbackData, JSON_THROW_ON_ERROR);

            $input = [
                'payload' => $payload,
                'gateway' => $gateway,
            ];

        }

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $gateway,
            action     : Action::QR_PRE_PROCESS,
            input      : $input,
            addEntities: false
        );
        // REF: https://docs.google.com/document/d/1mGep0btVixd-tcOU0F7Y0cCsDuGReUMgCXQDGQzGTEk/edit?tab=t.0#heading=h.itwx71h7nr3n
        if (($response['data']['payment']['method'] ?? '') === 'card')
        {
            $errorData = [
                'payment' => $response['data']['payment'] ?? [],
                'terminal' => [
                    'gateway'             => $gateway,
                    'gateway_merchant_id' => $callbackData['terminalId'],
                ],
            ];

            throw new Exception\GatewayErrorException(
                'Card payment not supported by QR',
                'CARD_PAYMENT_IN_CALLBACK',
                null,
                $errorData
            );
        }

        return $response['data'];
    }


    public static function checkIfNewQrPaymentGateway(string $gateway, $mode = Mode::LIVE)
    {
        // Fetch the variant from cache
        $qrVariant = Cache::get(self::QR_GATEWAY_CACHE_PREFIX . $gateway);

        // If there is no entry in cache
        if (empty($qrVariant) === true)
        {
            $app = App::getFacadeRoot();

            $properties = [
                'id'            => $gateway,
                'experiment_id' => $app->config->get('app.qr_payment_refactor_gateway'),
                'request_data'  => json_encode(['gateway' => $gateway]),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'gateway'   => $gateway,
                '$response'     => $response
            ]);

            $qrVariant='off';
            $variables = $response['response']['variant']['variables'] ?? [];
            foreach ($variables as $variable) {
                $key = $variable['key'] ?? '';
                $value = $variable['value'] ?? '';
                if($key=='result' && $value=='on') {
                    $qrVariant = 'on';
                    break;
                }
            }


            // Set the cache for a TTL of 3 mins
            Cache::set(self::QR_GATEWAY_CACHE_PREFIX . $gateway, strtolower($qrVariant), 180);
        }

        return strtolower($qrVariant) === 'on';
    }

    public static function checkIfOldGatewayProcessedThroughNewQrPaymentProcessingFlow(string $gateway, $mode = Mode::LIVE)
    {
        if ($gateway === Gateway::UPI_RZPAPB)
        {
            return true;
        }

        // Fetch the variant from cache
        $qrVariant = Cache::get(self::QR_EXISTING_GATEWAY_CACHE_PREFIX . $gateway);

        // If there is no entry in cache
        if (empty($qrVariant) === true)
        {
            $app = App::getFacadeRoot();

            $properties = [
                'id'            => $gateway,
                'experiment_id' => $app->config->get('app.qr_payment_refactor_existing_gateway'),
                'request_data'  => json_encode(['gateway' => $gateway]),
            ];
            $response   = $app['splitzService']->evaluateRequest($properties);

            $app->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'gateway'   => $gateway,
                '$response'     => $response
            ]);

            $qrVariant='off';
            $variables = $response['response']['variant']['variables'] ?? [];
            foreach ($variables as $variable) {
                $key = $variable['key'] ?? '';
                $value = $variable['value'] ?? '';
                if($key=='result' && $value=='on') {
                    $qrVariant = 'on';
                    break;
                }
            }

            // Set the cache for a TTL of 3 mins
            Cache::set(self::QR_EXISTING_GATEWAY_CACHE_PREFIX . $gateway, strtolower($qrVariant), 180);
        }

        return strtolower($qrVariant) === 'on';
    }
}
