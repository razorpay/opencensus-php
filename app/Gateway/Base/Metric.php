<?php

namespace RZP\Gateway\Base;

use App;

use RZP\Gateway\Upi;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Gateway\Wallet;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Models\Payment\Processor\Netbanking;

class Metric
{
    // Gateway metrics are pushed to a different statsd_exporter instance.
    const DOGSTATSD_DRIVER               = 'dogstatsd_gateway';

    // Counter type metric names only for gateway api calls
    const GATEWAY_REQUEST_COUNT_V3       = 'gateway_request_count_v3';

    //Optimiser Downtime metric name
    const GATEWAY_REQUEST_COUNT_OPTIMISER_V1      = 'gateway_request_count_optimiser_v1';

    //histogram for gateway request time
    const GATEWAY_REQUEST_TIME           = 'gateway_request_total_time_v2_ms';

    // class constants for usage in the class
    const INITIATED                      = 'initiated';
    const SUCCESS                        = 'success';
    const FAILED                         = 'failed';
    const CURL_ERROR                     = 'curl_error';

    // Dimensions for gateway api calls to log
    const DIMENSION_GATEWAY              = 'gateway';
    const DIMENSION_ACTION               = 'action';
    const DIMENSION_PAYMENT_METHOD       = 'payment_method';
    const DIMENSION_PAYMENT_RECURRING    = 'payment_recurring';
    const DIMENSION_CARD_TYPE            = 'card_type';
    const DIMENSION_CARD_NETWORK         = 'card_network';
    const DIMENSION_CARD_COUNTRY         = 'card_country';
    const DIMENSION_CARD_INTERNATIONAL   = 'card_international';
    const DIMENSION_INSTRUMENT_TYPE      = 'instrument_type';
    const DIMENSION_TPV                  = 'tpv';
    const DIMENSION_ISSUER               = 'issuer';
    const DIMENSION_UPI_PSP              = 'upi_psp';
    const DIMENSION_BHARAT_QR            = 'bharat_qr';
    const DIMENSION_AUTH_TYPE            = 'auth_type';
    const DIMENSION_STATUS               = 'status';
    const DIMENSION_STATUS_CODE          = 'status_code';
    const DIMENSION_TERMINAL_ID          = 'terminal_id';
    const DIMENSION_MERCHANT_CATEGORY    = 'merchant_category';
    const DIMENSION_ERROR                = 'curl_error_no';
    const DIMENSION_PROCURER             = 'procurer';
    const DIMENSION_MERCHANT_ID          = 'merchant_id';

    // Actions array for which we need to push data to prometheus
    const ACTIONS_TO_ALLOW  = [
        Payment\Action::AUTHORIZE,
        Payment\Action::CALLBACK,
        Payment\Action::CAPTURE,
        Payment\Action::REFUND,
        Payment\Action::CHECK_ACCOUNT,
        Payment\Action::FETCH_TOKEN,
        Payment\Action::VERIFY,
        Payment\Action::VERIFY_REFUND,
        Payment\Action::CARD_MANDATE_CREATE,
        Payment\Action::CARD_MANDATE_PRE_DEBIT_NOTIFY,
        Payment\Action::REPORT_PAYMENT,
        Payment\Action::VALIDATE_VPA,
        // Payment\Action::OTP_GENERATE,
        // Payment\Action::REVERSE,
        // Payment\Action::AUTHORIZE_PUSH,
        // Payment\Action::DEBIT,
        // Payment\Action::OTP_RESEND,
    ];

    // Optimiser action list
    const ACTIONS_TO_ALLOW_FOR_OPTIMISER  = [
        Payment\Action::AUTHORIZE,
        Payment\Action::CALLBACK
    ];

    // Optimiser procurer
    const OPTIMISER_PROCURER    = 'merchant';

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }

    public function getDimensions($action, $input, $gateway = 'none')
    {
        if ($action === Payment\Action::VALIDATE_VPA)
        {
            return $this->getValidateVpaDimensions($action, $input, $gateway);
        }

        $method = $this->getMethod($input);

        $isRecurringPayment = $this->isRecurringPayment($input);

        $isInternationalPayment = $this->isInternationalPayment($input);

        $authType = $this->getAuthType($input);

        $instrumentType = $this->getInstrumentType($input, $method, $gateway);

        $tpv = $this->getTpv($method, $input);

        $cardType = $this->getCardType($method, $input);

        $cardNetwork = $this->getCardNetwork($method, $input);

        $issuer = $this->getIssuer($method, $input);

        $upiPsp = $this->getUpiPsp($input);

        $isBharatQr = $this->isBharatQrPayment($input);

        $gateway_acquirer = $input[Entity::TERMINAL][\RZP\Models\Terminal\Entity::GATEWAY_ACQUIRER];

        return [
            Metric::DIMENSION_GATEWAY              => $gateway,
            Metric::DIMENSION_PAYMENT_METHOD       => $method,
            Metric::DIMENSION_ACTION               => $action,
            Metric::DIMENSION_CARD_TYPE            => $cardType,
            Metric::DIMENSION_CARD_NETWORK         => $cardNetwork,
            Metric::DIMENSION_CARD_COUNTRY         => 'none',
            Metric::DIMENSION_PAYMENT_RECURRING    => $isRecurringPayment,
            Metric::DIMENSION_INSTRUMENT_TYPE      => $instrumentType,
            Metric::DIMENSION_TPV                  => $tpv,
            Metric::DIMENSION_ISSUER               => $issuer,
            Metric::DIMENSION_UPI_PSP              => $upiPsp,
            Metric::DIMENSION_CARD_INTERNATIONAL   => $isInternationalPayment,
            Metric::DIMENSION_BHARAT_QR            => $isBharatQr,
            Metric::DIMENSION_AUTH_TYPE            => $authType,
            Metric::DIMENSION_TERMINAL_ID          => $gateway_acquirer,
            Metric::DIMENSION_MERCHANT_CATEGORY    => 'none',
        ];
    }

    public function getV2Dimensions($action, $input, $gateway = 'none', $excData = 'none')
    {
        $dimensions = $this->getDimensions($action, $input, $gateway);

        $dimensions[Metric::DIMENSION_ERROR] = $excData;

        return $dimensions;
    }

    public function getOptimiserDimensions($action, $input, $gateway = 'none', $excData = 'none')
    {
        $dimensions = $this->getDimensions($action, $input, $gateway);

        $dimensions[Metric::DIMENSION_PROCURER] = $this->getProcurer($input) ;

        $dimensions[Metric::DIMENSION_MERCHANT_ID] = $this->getMerchantId($input) ;

        $dimensions[Metric::DIMENSION_ERROR] = $excData;

        return $dimensions;
    }

    protected function getProcurer($input)
    {
        $procurer = 'none';

        if ( empty($input[Entity::TERMINAL][\RZP\Models\Terminal\Entity::PROCURER]) === false )
        {
             $procurer = $input[Entity::TERMINAL][\RZP\Models\Terminal\Entity::PROCURER] ;
        }

        return $procurer;
    }

    protected function getMerchantId($input)
    {
        $merchantId = 'none';

        if (empty($input[Entity::TERMINAL][\RZP\Models\Terminal\Entity::MERCHANT_ID]) === false)
        {
            $merchantId = $input[Entity::TERMINAL][\RZP\Models\Terminal\Entity::MERCHANT_ID];
        }

        return $merchantId;
    }

    protected function getInstrumentType($input, $method, $gateway)
    {
        $instrumentType = 'none';
        switch ($method)
        {
            case Payment\Method::NETBANKING:
                $bank = $input[Entity::PAYMENT][Payment\Entity::BANK];
                $instrumentType = (Netbanking::isCorporateBank($bank) === true) ? BankingType::CORPORATE :
                    BankingType::RETAIL;
                break;

            case Payment\Method::WALLET:
                $wallet = $input[Entity::PAYMENT][Payment\Method::WALLET];
                $isPowerWallet = Payment\Gateway::isPowerWallet($wallet);
                $instrumentType = $isPowerWallet ? Wallet\Base\Type::POWER : Wallet\Base\Type::NORMAL;
                break;

            case Payment\Method::UPI:
                $gatewayEntity = $this->repo->$gateway->
                                findByPaymentIdAndAction($input[Entity::PAYMENT][Payment\Entity::ID], Action::AUTHORIZE);

                $instrumentType = $gatewayEntity['type'] ?? 'collect';
                break;
        }

        return $instrumentType;
    }

    protected function isBharatQrPayment($input)
    {
        $isBharatQr = 'none';

        if (isset($input[Entity::PAYMENT][Payment\Entity::RECEIVER_TYPE]) === true)
        {
            if ($input[Entity::PAYMENT][Payment\Entity::RECEIVER_TYPE] === Receiver::QR_CODE)
            {
                $isBharatQr = '1';
            }
        }

        return $isBharatQr;
    }

    protected function getTpv($method, $input)
    {
        $tpv = 'none';

        if (($method === Payment\Method::NETBANKING) or
            ($method === Payment\Method::UPI))
        {
            if ($input[Entity::MERCHANT]->isTPVRequired() === true)
            {
                $tpv = '1';
            }
            else
            {
                $tpv = '0';
            }
        }

        return $tpv;
    }

    protected function getCardType($method, $input)
    {
        $cardType = ($method === Payment\Method::CARD) ? $input[Payment\Entity::CARD][Card\Entity::TYPE] : 'none';

        return $cardType;
    }

    protected function getCardNetwork($method, $input)
    {
        $network = ($method === Payment\Method::CARD) ? $input[Payment\Method::CARD][Card\Entity::NETWORK_CODE] :
            'none';

        return $network;
    }

    protected function getIssuer($method, $input)
    {
        $issuer = 'none';

        switch ($method)
        {
            case Payment\Method::CARD:
                $issuer = $input[Entity::CARD][Card\Entity::ISSUER] ?? 'none';
                break;

            case Payment\Method::WALLET:
                $issuer = $input[Entity::PAYMENT][Payment\Method::WALLET];
                break;

            case Payment\Method::NETBANKING:
                $issuer = $input[Entity::PAYMENT][Payment\Entity::BANK];
                break;
        }

        return $issuer;
    }

    protected function isInternationalPayment($input)
    {
        return $input[Entity::PAYMENT][Payment\Entity::INTERNATIONAL] ? '1' : '0';
    }

    protected function isRecurringPayment($input)
    {
        $recurringType = $input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] ?? 'none';

        return $recurringType;
    }

    protected function getAuthType($input)
    {
        $authType = $input[Entity::PAYMENT][Payment\Entity::AUTH_TYPE];

        return $authType ?: 'none';
    }

    protected function getMethod($input)
    {
        return $input[Entity::PAYMENT][Payment\Entity::METHOD];
    }

    protected function getUpiPsp($input)
    {
        // For Payments
        $vpa = $input[Entity::PAYMENT][Entity::VPA] ?? null;

        if (empty($vpa))
        {
            // For Validate VPA
            $vpa = $input[Entity::VPA] ?? null;
        }

        $upiPsp = Upi\Base\ProviderCode::getPspForVpa($vpa);

        switch ($upiPsp)
        {
            case Upi\Base\ProviderPsp::GOOGLE_PAY:
            case Upi\Base\ProviderPsp::PHONEPE:
            case Upi\Base\ProviderPsp::PAYTM:
            case Upi\Base\ProviderPsp::BHIM:
                return $upiPsp;
        }

        return 'none';
    }

    public function pushGatewayDimensions($action, $input, $status, $gateway = null, $excData = null, $statusCode = null)
    {
        try
        {
            $action = snake_case($action);

            if (in_array($action, self::ACTIONS_TO_ALLOW, true) === true)
            {
                $dimensions2 = $this->getV2Dimensions($action, $input, $gateway, $excData);

                $dimensions2[Metric::DIMENSION_STATUS] = $status;

                $dimensions2[Metric::DIMENSION_STATUS_CODE] = $statusCode;

                $gatewayMetrics = app('trace')->metricsDriver(self::DOGSTATSD_DRIVER);

                $gatewayMetrics->count(Metric::GATEWAY_REQUEST_COUNT_V3, 1, $dimensions2);
            }
        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::GATEWAY_METRIC_DIMENSION_PUSH_FAILED,
                [
                    'action'    => $action ?? 'none',
                    'gateway'   => $gateway ?? 'none',
                ]);
        }
    }
    public function pushOptimiserGatewayDimensions($action, $input, $status, $gateway = 'none', $excData = 'none', $statusCode = 'none')
    {
        try {
                $procurer = $this->getProcurer($input);

                if (in_array($action, self::ACTIONS_TO_ALLOW_FOR_OPTIMISER, true) === true && ($procurer === Metric::OPTIMISER_PROCURER))
                {
                    $metricDimension = $this->getOptimiserDimensions($action, $input, $gateway, $excData);

                    $metricDimension[Metric::DIMENSION_STATUS] = $status;

                    $metricDimension[Metric::DIMENSION_STATUS_CODE] = $statusCode;

                    $gatewayMetrics = app('trace')->metricsDriver(self::DOGSTATSD_DRIVER);

                    $gatewayMetrics->count(Metric::GATEWAY_REQUEST_COUNT_OPTIMISER_V1, 1, $metricDimension);

                    $this->trace->info(TraceCode::OPTIMISER_GATEWAY_METRIC_DIMENSION_PUSHED,
                        [
                            'merchant_id'   => $this->getMerchantId($input),
                            'payment_id'    => $input[Entity::PAYMENT][Payment\Entity::ID],
                            'gateway'       => $gateway,
                            'procurer'      => $procurer,
                            'action'        => $action,
                            'excData'       => $excData,
                            'statusCode'    => $statusCode
                        ]);
                }
        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::OPTIMISER_GATEWAY_METRIC_DIMENSION_PUSH_FAILED,
                [
                    'action'    => $action ?? 'none',
                    'gateway'   => $gateway ?? 'none',
                ]);
        }
    }

    public function getValidateVpaDimensions($action, $input, $gateway)
    {
        $upiPsp = $this->getUpiPsp($input);

        return [
            Metric::DIMENSION_GATEWAY              => $gateway,
            Metric::DIMENSION_PAYMENT_METHOD       => 'none',
            Metric::DIMENSION_ACTION               => $action,
            Metric::DIMENSION_CARD_TYPE            => 'none',
            Metric::DIMENSION_CARD_NETWORK         => 'none',
            Metric::DIMENSION_CARD_COUNTRY         => 'none',
            Metric::DIMENSION_PAYMENT_RECURRING    => 'none',
            Metric::DIMENSION_INSTRUMENT_TYPE      => 'none',
            Metric::DIMENSION_TPV                  => 'none',
            Metric::DIMENSION_ISSUER               => 'none',
            Metric::DIMENSION_UPI_PSP              => $upiPsp,
            Metric::DIMENSION_CARD_INTERNATIONAL   => 'none',
            Metric::DIMENSION_BHARAT_QR            => 'none',
            Metric::DIMENSION_AUTH_TYPE            => 'none',
            Metric::DIMENSION_TERMINAL_ID          => 'none',
            Metric::DIMENSION_MERCHANT_CATEGORY    => 'none'
        ];
    }
}
