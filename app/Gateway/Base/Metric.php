<?php

namespace RZP\Gateway\Base;

use App;

use RZP\Gateway\Upi;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Gateway\Wallet;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Models\Payment\Processor\Netbanking;

class Metric
{
    // Counter type metric names only for gateway api calls
    const GATEWAY_REQUEST_COUNT          = 'gateway_request_count';

    // class constants for usage in the class
    const SUCCESS                        = 'success';
    const FAILED                         = 'failed';

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
    const DIMENSION_TERMINAL_ID          = 'terminal_id';
    const DIMENSION_MERCHANT_CATEGORY    = 'merchant_category';

    // Actions array for which we need not push data to prometheus
    const EXCLUDED_ACTIONS = [
        Payment\Action::VERIFY,
        Payment\Action::GENERATE_REFUNDS,
        Payment\Action::GENERATE_CLAIMS
    ];

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function getDimensions($action, $input)
    {
        $gateway = $this->getGateway($input);

        $method = $this->getMethod($input);

        $isRecurringPayment = $this->isRecurringPayment($input);

        $isInternationalPayment = $this->isInternationalPayment($input);

        $authType = $this->getAuthType($input);

        $instrumentType = $this->getInstrumentType($input, $method);

        $tpv = $this->getTpv($method, $input);

        $cardType = $this->getCardType($method, $input);

        $cardNetwork = $this->getCardNetwork($method, $input);

        $cardCountry = $this->getCardCountry($method, $input);

        $issuer = $this->getIssuer($method, $input);

        $upiPsp = $this->getUpiPsp($input);

        $isBharatQr = $this->isBharatQrPayment($input);

        $terminalId = $this->getTerminalId($input);

        $merchantCategory = $this->getMerchantCategory($input);

        return [
            Metric::DIMENSION_GATEWAY              => $gateway,
            Metric::DIMENSION_PAYMENT_METHOD       => $method,
            Metric::DIMENSION_ACTION               => $action,
            Metric::DIMENSION_CARD_TYPE            => $cardType,
            Metric::DIMENSION_CARD_NETWORK         => $cardNetwork,
            Metric::DIMENSION_CARD_COUNTRY         => $cardCountry,
            Metric::DIMENSION_PAYMENT_RECURRING    => $isRecurringPayment,
            Metric::DIMENSION_INSTRUMENT_TYPE      => $instrumentType,
            Metric::DIMENSION_TPV                  => $tpv,
            Metric::DIMENSION_ISSUER               => $issuer,
            Metric::DIMENSION_UPI_PSP              => $upiPsp,
            Metric::DIMENSION_CARD_INTERNATIONAL   => $isInternationalPayment,
            Metric::DIMENSION_BHARAT_QR            => $isBharatQr,
            Metric::DIMENSION_AUTH_TYPE            => $authType,
            Metric::DIMENSION_TERMINAL_ID          => $terminalId,
            Metric::DIMENSION_MERCHANT_CATEGORY    => $merchantCategory
        ];
    }

    protected function getInstrumentType($input, $method)
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
                if (isset($input[Payment\Method::UPI]['flow']))
                {
                    $instrumentType = $input[Payment\Method::UPI]['flow'];
                    $instrumentType = $instrumentType === Upi\Base\Type::INTENT ? $instrumentType :
                        Upi\Base\Type::COLLECT;
                }
                else
                {
                    $instrumentType = Upi\Base\Type::COLLECT;
                }
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
            if ((isset($input[Entity::ORDER][Payment\Entity::ACCOUNT_NUMBER]) === true) and
                ($input[Entity::MERCHANT]->isTPVRequired() === true))
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
        $network = ($method === Payment\Method::CARD) ? $input[Payment\Method::CARD][Card\Entity::NETWORK] : 'none';

        return $network;
    }

    protected function getCardCountry($method, $input)
    {
        $country = ($method === Payment\Method::CARD) ? $input[Payment\Method::CARD][Card\Entity::COUNTRY] :
            'none';

        return $country;
    }

    protected function getIssuer($method, $input)
    {
        $issuer = 'none';

        switch ($method)
        {
            case Payment\Method::CARD:
                $issuer = $input[Payment\Method::CARD][Card\Entity::ISSUER];
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
        return $input[Entity::PAYMENT][Payment\Entity::RECURRING] ? '1' : '0';
    }

    protected function getAuthType($input)
    {
        $authType = $input[Entity::PAYMENT][Payment\Entity::AUTH_TYPE];

        return $authType ?: 'none';
    }

    protected function getGateway($input)
    {
        return $input[Entity::PAYMENT][Payment\Entity::GATEWAY];
    }

    protected function getMethod($input)
    {
        return $input[Entity::PAYMENT][Payment\Entity::METHOD];
    }

    protected function getUpiPsp($input)
    {
        $upiPsp = 'none';

        $method = $this->getMethod($input);

        if (($method === Payment\Method::UPI) and
            (isset($input[Entity::PAYMENT][Upi\Base\Entity::VPA]) === true))
        {
            $array = explode('@', $input[Entity::PAYMENT][Upi\Base\Entity::VPA]);

            if (count($array) > 1)
            {
                $upiPsp = $array[1];
            }
        }

        return $upiPsp;
    }

    protected function getMerchant($input)
    {
        return $input[Entity::PAYMENT][Payment\Entity::MERCHANT_ID];
    }

    protected function getTerminalid($input)
    {
        return $input[Entity::TERMINAL][Terminal\Entity::ID];
    }

    protected function getMerchantCategory($input)
    {
        return $input[Entity::MERCHANT]->getCategory();
    }

    public function pushGatewayDimensions($action, $input, $status)
    {
        try
        {
            if (in_array($action, self::EXCLUDED_ACTIONS, true) === false)
            {
                $dimensions = $this->getDimensions($action, $input);
                $dimensions[Metric::DIMENSION_STATUS] = $status;

                app('trace')->count(Metric::GATEWAY_REQUEST_COUNT, $dimensions);
            }
        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::GATEWAY_ERROR_METRIC_DIMENSION_FETCH,
                $input,
                [$action]);
        }
    }
}