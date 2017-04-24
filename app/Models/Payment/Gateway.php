<?php

namespace RZP\Models\Payment;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\Upi;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Settlement;
use RZP\Models\Payment;

class Gateway
{
    const AMEX               = 'amex';
    const ATOM               = 'atom';
    const AXIS_GENIUS        = 'axis_genius';
    const AXIS_MIGS          = 'axis_migs';
    const BILLDESK           = 'billdesk';
    const CYBERSOURCE        = 'cybersource';
    const EBS                = 'ebs';
    const FIRST_DATA         = 'first_data';
    const HDFC               = 'hdfc';
    const MOBIKWIK           = 'mobikwik';
    const NETBANKING_HDFC    = 'netbanking_hdfc';
    const NETBANKING_KOTAK   = 'netbanking_kotak';
    const NETBANKING_ICICI   = 'netbanking_icici';
    const NETBANKING_AIRTEL  = 'netbanking_airtel';
    const NETBANKING_AXIS    = 'netbanking_axis';
    const NETBANKING_FEDERAL = 'netbanking_federal';
    const PAYTM              = 'paytm';
    const SHARP              = 'sharp';
    const UPI_ICICI          = 'upi_icici';
    const UPI_IDFC           = 'upi_idfc';
    const WALLET_AIRTELMONEY = 'wallet_airtelmoney';
    const WALLET_FREECHARGE  = 'wallet_freecharge';
    const WALLET_JIOMONEY    = 'wallet_jiomoney';
    const WALLET_OPENWALLET  = 'wallet_openwallet';
    const WALLET_OLAMONEY    = 'wallet_olamoney';
    const WALLET_PAYUMONEY   = 'wallet_payumoney';
    const WALLET_PAYZAPP     = 'wallet_payzapp';

    const ACQUIRER_HDFC      = 'hdfc';
    const ACQUIRER_ICIC      = 'icic';
    const ACQUIRER_AXIS      = 'axis';
    const ACQUIRER_AMEX      = 'amex';

    const NOT_SUPPORTED      = 'not_supported';
    const SUPPORTED          = 'supported';

    const GATEWAY_ACQUIRERS = [
        self::AXIS_MIGS   => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC],
        self::HDFC        => [self::ACQUIRER_HDFC],
        self::CYBERSOURCE => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC],
        self::FIRST_DATA  => [self::ACQUIRER_ICIC],
        self::AMEX        => [self::ACQUIRER_AMEX],
    ];

    const POWER_WALLETS = array(
        Wallet::MOBIKWIK,
        Wallet::PAYUMONEY,
        Wallet::OLAMONEY,
        Wallet::FREECHARGE,
    );

    const TOPUP_GATEWAYS = array(
        self::MOBIKWIK,
        self::WALLET_PAYUMONEY,
        self::WALLET_OLAMONEY,
        self::WALLET_FREECHARGE,
        self::SHARP,
    );

    const REFUND_TIMEOUT_HANDLED_GATEWAYS = [
        self::WALLET_FREECHARGE,
        self::BILLDESK,
    ];

    /**
    * Gateways for which we can validate the refunds
    * if they are successful after they are 'initiated'
    */
    const UNKNOWN_REFUNDS_VALIDATION_GATEWAYS = [
        self::WALLET_FREECHARGE
    ];

    /**
    * Gateways for which we may need to force authorize payments
    * since their verify API's stop working after a certain time
    */
    const FORCE_AUTHORIZE_GATEWAYS = [
        self::AXIS_MIGS,
        self::WALLET_JIOMONEY
    ];

    public static $channels = [
        self::AMEX               => Settlement\Channel::KOTAK,
        self::ATOM               => Settlement\Channel::ATOM,
        self::AXIS_GENIUS        => Settlement\Channel::KOTAK,
        self::AXIS_MIGS          => Settlement\Channel::KOTAK,
        self::BILLDESK           => Settlement\Channel::KOTAK,
        self::EBS                => Settlement\Channel::KOTAK,
        self::HDFC               => Settlement\Channel::KOTAK,
        self::MOBIKWIK           => Settlement\Channel::KOTAK,
        self::PAYTM              => Settlement\Channel::KOTAK,
        self::SHARP              => Settlement\Channel::KOTAK,
        self::NETBANKING_HDFC    => Settlement\Channel::KOTAK,
        self::NETBANKING_KOTAK   => Settlement\Channel::KOTAK,
        self::NETBANKING_ICICI   => Settlement\Channel::KOTAK,
        self::NETBANKING_AIRTEL  => Settlement\Channel::KOTAK,
        self::NETBANKING_AXIS    => Settlement\Channel::KOTAK,
        self::NETBANKING_FEDERAL => Settlement\Channel::KOTAK,
        self::WALLET_PAYZAPP     => Settlement\Channel::KOTAK,
        self::WALLET_PAYUMONEY   => Settlement\Channel::KOTAK,
        self::WALLET_OLAMONEY    => Settlement\Channel::KOTAK,
        self::WALLET_FREECHARGE  => Settlement\Channel::KOTAK,
        self::WALLET_AIRTELMONEY => Settlement\Channel::KOTAK,
        self::WALLET_JIOMONEY    => Settlement\Channel::KOTAK,
        self::WALLET_OPENWALLET  => Settlement\Channel::KOTAK,
        self::FIRST_DATA         => Settlement\Channel::KOTAK,
        self::UPI_ICICI          => Settlement\Channel::KOTAK,
        self::CYBERSOURCE        => Settlement\Channel::KOTAK,
    ];

    /**
     * Mapping of method to gateways supporting that method
     * either in live or test mode.
     *
     * @var array
     */
    public static $methodMap = [
        Method::CARD => [
            self::HDFC,
            self::ATOM,
            self::AXIS_MIGS,
            self::AXIS_GENIUS,
            self::PAYTM,
            self::AMEX,
            self::CYBERSOURCE,
            self::FIRST_DATA,
        ],

        Method::NETBANKING => [
            self::PAYTM,
            self::BILLDESK,
            self::EBS,
            self::NETBANKING_ICICI,
            self::NETBANKING_HDFC,
            self::NETBANKING_KOTAK,
            self::NETBANKING_AIRTEL,
            self::NETBANKING_AXIS,
            self::NETBANKING_FEDERAL,
        ],

        Method::WALLET => [
            self::MOBIKWIK,
            self::PAYTM,
            self::WALLET_OLAMONEY,
            self::WALLET_PAYZAPP,
            self::WALLET_PAYUMONEY,
            self::WALLET_AIRTELMONEY,
            self::WALLET_FREECHARGE,
            self::WALLET_JIOMONEY,
            self::WALLET_OPENWALLET,
        ],

        Method::EMI => [
            self::AMEX,
            self::HDFC,
            self::FIRST_DATA,
        ],

        Method::UPI => [
            self::UPI_ICICI,
            self::UPI_IDFC,
        ],
    ];

    /**
     * Card gateways which support auth and capture mechanism for at
     * least one card network.
     *
     * @var array
     */
    public static $authAndCapture = [
        self::HDFC                  => [
            self::NOT_SUPPORTED => [Network::MAES, Network::RUPAY, Network::DICL]
        ],
        self::AXIS_MIGS             => [],
        self::AMEX                  => [],
        self::CYBERSOURCE           => [],
        self::FIRST_DATA            => [
            self::NOT_SUPPORTED => [Network::MAES, Network::RUPAY]
        ],
        self::WALLET_OPENWALLET     => [],
    ];

    /**
     * Card gateways which support full auth reversal
     *
     * @var array
     */
    public static $reverse = [
        self::CYBERSOURCE,
        self::FIRST_DATA,
        self::AXIS_MIGS,
        self::WALLET_OPENWALLET,
    ];


    /**
     * For async gateways, we mark the payment as created and return
     * the response immediately. The payment is authorized over a webhook
     * or some other async medium. Checkout currently long-polls for
     * the payment to be authorized.
     * @var array
     */
    public static $asynchronous = array(
        self::UPI_ICICI,
        self::UPI_IDFC,
        self::SHARP,
    );

    /**
     * Each card gateway only support specific card networks.
     * This maintains a map of gateway to card network which
     * is used in gateway and terminal selection logic
     *
     * @var array
     */
    public static $cardNetworkMap = array(
        self::HDFC => array(
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::DICL,
            Network::RUPAY,
            Network::UNKNOWN),
        self::AXIS_MIGS => array(
            Network::MC,
            Network::VISA),
        self::AXIS_GENIUS => array(
            Network::MC,
            Network::VISA),
        self::ATOM => array(
            Network::MC,
            Network::VISA),
        self::AMEX => array(
            Network::AMEX),
        self::PAYTM => array(
            Network::MC,
            Network::VISA),
        self::SHARP => array(
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::AMEX,
            Network::DICL,
            Network::RUPAY,
            Network::UNKNOWN),
        self::CYBERSOURCE => array(
            Network::MC,
            Network::VISA),
        self::FIRST_DATA => array(
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::RUPAY,
        ),
    );

    public static $walletToGatewayMap = array(
        Wallet::OLAMONEY    => Gateway::WALLET_OLAMONEY,
        Wallet::PAYTM       => Gateway::PAYTM,
        Wallet::MOBIKWIK    => Gateway::MOBIKWIK,
        Wallet::PAYZAPP     => Gateway::WALLET_PAYZAPP,
        Wallet::PAYUMONEY   => Gateway::WALLET_PAYUMONEY,
        Wallet::AIRTELMONEY => Gateway::WALLET_AIRTELMONEY,
        Wallet::FREECHARGE  => Gateway::WALLET_FREECHARGE,
        Wallet::JIOMONEY    => Gateway::WALLET_JIOMONEY,
        Wallet::OPENWALLET  => Gateway::WALLET_OPENWALLET
    );

    public static $upiToGatewayMap = array(
        Upi::ICICI  => Gateway::UPI_ICICI,
        Upi::IDFC   => Gateway::UPI_IDFC,
    );

    /**
     * @deprecated
     * List of gateways for which we run verification checks for all
     * failed payments on a continuous basis.
     *
     * @var array
     */
    public static $verifyEnabled = [
        self::AXIS_MIGS,
        self::BILLDESK,
        self::EBS,
        self::MOBIKWIK,
        self::PAYTM,
        self::HDFC,
        self::AMEX,
        self::NETBANKING_HDFC,
        self::NETBANKING_KOTAK,
        self::NETBANKING_ICICI,
        self::NETBANKING_AIRTEL,
        self::NETBANKING_AXIS,
        self::NETBANKING_FEDERAL,
        self::WALLET_PAYZAPP,
        self::FIRST_DATA,
        self::CYBERSOURCE,
        self::WALLET_PAYUMONEY,
        self::WALLET_AIRTELMONEY,
        self::WALLET_OLAMONEY,
        self::WALLET_FREECHARGE,
        self::WALLET_JIOMONEY,
        self::UPI_ICICI,
        self::UPI_IDFC,
    ];

    public static $verifyDisabled = [
        self::WALLET_OPENWALLET
    ];

    /**
     * List of gateways that support recurring payments
     *
     * @var array
     */
    public static $recurringGateways = [
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
    ];

    /**
     * List of gateways which give s2s callback where we do not validate
     * payment callback hash
     *
     * @var array
     */
    public static $s2sCallbackGateways = array(
        Gateway::BILLDESK,
        Gateway::UPI_ICICI,
        Gateway::WALLET_OLAMONEY,
        Gateway::SHARP
    );

    /**
     * Card gateways which support international payments
     *
     * @var array
     */
    public static $internationalCardGateways = array(
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX,
        Gateway::CYBERSOURCE,
    );

    /**
     * For the banks that need a claims file to be generated,
     * we have a list of banks that support this feature
     * @var array
     */
    public static $claimsFileToBank = [
        IFSC::KKBK,
        IFSC::UTIB,
    ];

    /**
     * Some card networks are only supported partially for one or two gateway.
     *
     * @var array
     */
    public static $partiallySupportedCardNetworks = array(
        Network::MAES,
        Network::RUPAY,
        Network::DICL);

    /**
     * For the banks we have direct tie-ups with,
     * here we list down the mapping from bank to netbanking gateway name.
     * There is no standardized bank gateway naming that we follow. IFSC
     * code option was discarded because it's not readable in general in code.
     *
     * @var array
     */
    public static $netbankingToGatewayMap = array(
        IFSC::ICIC => Gateway::NETBANKING_ICICI,
        IFSC::HDFC => Gateway::NETBANKING_HDFC,
        IFSC::AIRP => Gateway::NETBANKING_AIRTEL,
        IFSC::FDRL => Gateway::NETBANKING_FEDERAL,
        IFSC::KKBK => Gateway::NETBANKING_KOTAK,
        IFSC::UTIB => Gateway::NETBANKING_AXIS);

    /**
     * For the banks that require a refundfile generated everyday,
     * we map IFSC codes to Gateways
     *
     * @var array
     */
    public static $refundFileNetbankingGateways = array(
        IFSC::ICIC => Gateway::NETBANKING_ICICI,
        IFSC::HDFC => Gateway::NETBANKING_HDFC,
        IFSC::KKBK => Gateway::NETBANKING_KOTAK,
        IFSC::UTIB => Gateway::NETBANKING_AXIS,
        IFSC::FDRL => Gateway::NETBANKING_FEDERAL);

    /**
     * List of gateways which support netbanking, either in test or live mode.
     *
     * @var array
     */
    public static $netbankingGateways = array(
        Gateway::BILLDESK,
        Gateway::EBS,
        Gateway::PAYTM,
        Gateway::ATOM);

    public static $emiBanks = array(
        IFSC::HDFC,
        IFSC::HSBC,
        IFSC::ICIC,
        IFSC::INDB,
        IFSC::KKBK,
        IFSC::RATN,
        IFSC::SCBL,
        IFSC::UTIB,
    );

    public static $emiBanksUsingCardTerminals = array(
        IFSC::INDB,
        IFSC::KKBK,
        IFSC::RATN,
        IFSC::UTIB,
    );

    public static $emiBankToGatewayMap = array(
        IFSC::HDFC => Gateway::HDFC,
        IFSC::ICIC => Gateway::FIRST_DATA,
        IFSC::HSBC => Gateway::FIRST_DATA,
        IFSC::SCBL => Gateway::FIRST_DATA,
    );

    public static function isNetbankingBankDirectlySupported($bank)
    {
        return in_array($bank, Netbanking::getDirectlyNetbankingBanks());
    }

    public static function isRecurringGateway($gateway)
    {
        return in_array($gateway, self::$recurringGateways, true);
    }

    public static function getChannel($gateway)
    {
        return self::$channels[$gateway];
    }

    public static function isValidGateway($gateway)
    {
        return (defined(__CLASS__ . '::' . strtoupper($gateway)));
    }

    public static function getGatewayForWallet($wallet)
    {
        return self::$walletToGatewayMap[$wallet];
    }

    public static function getWalletForGateway($gateway)
    {
        if (in_array($gateway, self::$methodMap[Method::WALLET]) === false)
        {
            throw new Exception\LogicException(
                'Unknown wallet gateway. Gateway: ' . $gateway);
        }

        return array_flip(self::$walletToGatewayMap)[$gateway];
    }

    public static function validateGateway($gateway)
    {
        if (self::isValidGateway($gateway) === false)
        {
            throw new Exception\LogicException(
                'Unknown gateway. Gateway: ' . $gateway);
        }
    }

    public static function isMethodSupported($method, $gateway)
    {
        return (in_array($gateway, self::$methodMap[$method]));
    }

    /**
     * If network code is null, the function returns back whether the
     * given gateway has support for authAndCapture or not.
     * If network code is not null, the functions returns back whether
     * the given gateway has support for authAndCapture for the given
     * network.
     *
     * @param string $gateway
     * @param string $networkCode
     * @return bool
     */
    public static function supportsAuthAndCapture($gateway, $networkCode = null): bool
    {
        $arrayKeys = array_keys(self::$authAndCapture);

        $supportsAuthAndCapture = in_array($gateway, $arrayKeys, true);

        if ($supportsAuthAndCapture === false)
        {
            return false;
        }
        else
        {
            if ($networkCode === null)
            {
                return $supportsAuthAndCapture;
            }
            else
            {
                return self::supportsAuthAndCaptureForNetwork($gateway, $networkCode);
            }
        }
    }

    public static function supportsReverse($gateway)
    {
        return in_array($gateway, self::$reverse, true);
    }

    /**
     * Whether the gateway supports async payments
     * @param  string $gateway
     * @return boolean
     */
    public static function supportsAsync($gateway)
    {
        return in_array($gateway, self::$asynchronous, true);
    }

    public static function supportsAuthAndCaptureForNetwork($gateway, $networkCode)
    {
        // This means that all the networks are supported by the gateway for authAndCapture.
        if (isset(self::$authAndCapture[$gateway][self::NOT_SUPPORTED]) === false)
        {
            return true;
        }

        // Get all the networks which are NOT supported by the gateway for authAndCapture.
        $notSupportedNetworks = self::$authAndCapture[$gateway][self::NOT_SUPPORTED];

        // If a given network is in the list of notSupportedNetworks, it means that the network
        // is not supported by the gateway for authAndCapture.
        if (in_array($networkCode, $notSupportedNetworks))
        {
            return false;
        }

        return true;
    }

    public static function isPowerWallet($wallet)
    {
        return (in_array($wallet, self::POWER_WALLETS));
    }

    public static function canGatewayTopup($gateway)
    {
        return (in_array($gateway, self::TOPUP_GATEWAYS));
    }

    public static function isCardNetworkSupported($network, $gateway)
    {
        return ((array_key_exists($gateway, self::$cardNetworkMap)) and
                (in_array($network, self::$cardNetworkMap[$gateway])));
    }

    public static function getGatewaysForNetbankingBank($bank, $isTPV = false)
    {
        $gateways = [];

        // Check for direct netbanking gateway
        if (self::isNetbankingBankDirectlySupported($bank))
        {
            $gateways[] = self::$netbankingToGatewayMap[$bank];
        }

        // Add netbanking gateways that support bank
        foreach (self::$netbankingGateways as $netbankingGateway)
        {
            if (Netbanking::isBankSupportedByGateway($bank, $netbankingGateway, $isTPV))
            {
                $gateways[] = $netbankingGateway;
            }
        }

        return $gateways;
    }

    public static function getGatewaysForNetbankingBankIndexed($bank)
    {
        $gateways = [];

        // Check for direct netbanking gateway
        if (self::isNetbankingBankDirectlySupported($bank))
        {
            $gateways['direct'] = self::$netbankingToGatewayMap[$bank];
        }

        // Add netbanking gateways that support bank
        foreach (self::$netbankingGateways as $netbankingGateway)
        {
            if (Netbanking::isBankSupportedByGateway($bank, $netbankingGateway))
            {
                $gateways['gateway'][] = $netbankingGateway;
            }
        }

        return $gateways;
    }
}
