<?php

namespace RZP\Models\QrGatewayModule;

use App;
use Cache;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\QrCode\Entity as QrCodeEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Gateway\Upi\Base\IntentParams as IntentParams;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVaQrCodeEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\InvoiceDetails as InvoiceDetails;
use RZP\Trace\TraceCode;

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
        $payload = json_encode($callbackData, JSON_THROW_ON_ERROR);

        $input = [
            'payload' => $payload,
            'gateway' => $gateway,
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $gateway,
            action     : Action::QR_PRE_PROCESS,
            input      : $input,
            addEntities: false
        );

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
