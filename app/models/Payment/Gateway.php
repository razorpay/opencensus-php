<?php

namespace Models\Payment;

use EE\Exception;
use Models\Bank\IFSC;
use Models\Card\Network;
use Models\Settlement;
use Models\Payment\Processor\Wallet;

class Gateway
{
    const AMEX              = 'amex';
    const ATOM              = 'atom';
    const AXIS_GENIUS       = 'axis_genius';
    const AXIS_MIGS         = 'axis_migs';
    const BILLDESK          = 'billdesk';
    const HDFC              = 'hdfc';
    const KOTAK             = 'kotak';
    const MOBIKWIK          = 'mobikwik';
    const OLAMONEY          = 'olamoney';
    const PAYTM             = 'paytm';
    const SBIEPAY           = 'sbiepay';
    const SHARP             = 'sharp';
    const NETBANKING_HDFC   = 'netbanking_hdfc';
    const NETBANKING_KOTAK  = 'netbanking_kotak';
    const WALLET_OLAMONEY   = 'wallet_olamoney';
    const WALLET_PAYZAPP    = 'wallet_payzapp';
    const WALLET_PAYUMONEY  = 'wallet_payumoney';

    const POWER_WALLETS = array(
        Wallet::MOBIKWIK,
        Wallet::PAYUMONEY,
        Wallet::OLAMONEY,
    );

    const TOPUP_GATEWAYS = array(
        self::MOBIKWIK,
        self::WALLET_PAYUMONEY,
        self::SHARP,
    );

    public static $channels = array(
        self::AMEX              => Settlement\Channel::KOTAK,
        self::ATOM              => Settlement\Channel::ATOM,
        self::AXIS_GENIUS       => Settlement\Channel::KOTAK,
        self::AXIS_MIGS         => Settlement\Channel::KOTAK,
        self::BILLDESK          => Settlement\Channel::KOTAK,
        self::HDFC              => Settlement\Channel::KOTAK,
        self::KOTAK             => Settlement\Channel::KOTAK,
        self::MOBIKWIK          => Settlement\Channel::KOTAK,
        self::PAYTM             => Settlement\Channel::KOTAK,
        self::SBIEPAY           => Settlement\Channel::KOTAK,
        self::SHARP             => Settlement\Channel::KOTAK,
        self::NETBANKING_HDFC   => Settlement\Channel::KOTAK,
        self::NETBANKING_KOTAK  => Settlement\Channel::KOTAK,
        self::WALLET_PAYZAPP    => Settlement\Channel::KOTAK,
        self::WALLET_PAYUMONEY  => Settlement\Channel::KOTAK,
    );

    /**
     * Mapping of method to gateways supporting that method
     * either in live or test mode.
     *
     * @var array
     */
    public static $methodMap = array(
        Method::CARD => array(
            self::HDFC,
            self::ATOM,
            self::AXIS_MIGS,
            self::AXIS_GENIUS,
            self::KOTAK,
            self::PAYTM,
            self::AMEX,
        ),

        Method::NETBANKING => array(
            self::PAYTM,
            self::BILLDESK,
            self::NETBANKING_HDFC,
            self::NETBANKING_KOTAK,
            self::SBIEPAY
        ),

        Method::WALLET => array(
            self::MOBIKWIK,
            self::PAYTM,
            self::WALLET_OLAMONEY,
            self::WALLET_PAYZAPP,
            self::WALLET_PAYUMONEY,
        ),

        Method::EMI => array(
            self::HDFC,
            self::KOTAK,
            self::AXIS_MIGS,
        ),
    );

    /**
     * Card gateways which support auth and capture mechanism for at
     * least one card network.
     *
     * @var array
     */
    public static $authAndCapture = array(
        self::HDFC,
        self::AMEX,
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
        self::KOTAK => array(
            Network::RUPAY),
        self::AMEX => array(
            Network::AMEX),
        self::PAYTM => array(
            Network::MC,
            Network::VISA),
    );

    public static $walletToGatewayMap = array(
        Wallet::OLAMONEY    => Gateway::WALLET_OLAMONEY,
        Wallet::PAYTM       => Gateway::PAYTM,
        Wallet::MOBIKWIK    => Gateway::MOBIKWIK,
        Wallet::PAYZAPP     => Gateway::WALLET_PAYZAPP,
        Wallet::PAYUMONEY   => Gateway::WALLET_PAYUMONEY,
    );

    /**
     * List of gateways for which we run verification checks for all
     * failed payments on a continuous basis.
     *
     * @var array
     */
    public static $verifyEnabled = array(
        self::AXIS_MIGS,
        self::BILLDESK,
        self::MOBIKWIK,
        self::PAYTM,
        self::HDFC,
        self::AMEX,
        self::NETBANKING_HDFC,
        self::WALLET_PAYZAPP);

    /**
     * Card gateways which support international payments
     *
     * @var array
     */
    public static $internationalCardGateways = array(
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX);

    /**
     * Card gateways which support domestic payments in live mode.
     *
     * @var array
     */
    public static $domesticCardGateways = array(
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX);

    /**
     * Card gateways which support domestic payments in test mode.
     *
     * @var array
     */
    public static $domesticCardGatewaysInTest = array(
        Gateway::KOTAK,
        Gateway::ATOM,
        Gateway::PAYTM,
        Gateway::AXIS_GENIUS);

    /**
     * These card gateways can be used live and can have direct
     * terminal assignments for the merchant.
     *
     * The order in which we specify them is important because
     * that denotes their preference in our system currently.
     *
     * @var array
     */
    public static $directCardGateways = array(
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX);

    /**
     * These gateways are only used in test and may or may not graduate to live
     * someday. Although, axis genius was live, we removed it from there
     * because of downtimes and really low success rates.
     * Paytm supports only cards in test mode. Although we are live on paytm
     * on netbanking, but it doesn't support that in test mode.
     *
     * @var array
     */
    public static $directCardGatewaysInTest = array(
        Gateway::AXIS_GENIUS,
        Gateway::SBIEPAY,
        Gateway::KOTAK,
        Gateway::PAYTM);

    /**
     * Some card networks are only supported partiall for one or two gateway.
     *
     * @var array
     */
    public static $partiallySupportedCardNetworks = array(
        Network::MAES,
        Network::RUPAY,
        Network::DICL);

    /**
     * Banks with which we have direct netbanking tie-ups.
     * @var array
     */
    public static $directNetbankingBankList = array(
        IFSC::HDFC,
        IFSC::KKBK);

    /**
     * For the banks we have direct tie-ups with,
     * here we list down the mapping from bank to netbanking gateway name.
     * There is no standardized bank gateway naming that we follow. IFSC
     * code option was discarded because it's not readable in general in code.
     *
     * @var array
     */
    public static $netbankingToGatewayMap = array(
        IFSC::HDFC => Gateway::NETBANKING_HDFC,
        IFSC::KKBK => Gateway::NETBANKING_KOTAK);

    /**
     * List of gateways which support netbanking, either in test or live mode.
     *
     * @var array
     */
    public static $netbankingGateways = array(
        Gateway::BILLDESK,
        Gateway::SBIEPAY,
        Gateway::PAYTM,
        Gateway::ATOM);

    /**
     * Gateways which support netbanking in live mode
     *
     * @var array
     */
    public static $directNetbankingGateways = array(
        Gateway::BILLDESK);

    /**
     * Gateways which support netbanking in test mode
     * Paytm can support live mode as well but we do not want to use
     * it in live for netbanking.
     *
     * @var array
     */
    public static $directNetbankingGatewaysInTest = array(
        Gateway::SBIEPAY,
        Gateway::PAYTM,
        Gateway::ATOM);

    public static $emiBanks = array(
        self::HDFC      => IFSC::HDFC,
        self::KOTAK     => IFSC::KKBK,
        self::AXIS_MIGS => IFSC::UTIB,
    );

    public static $emiFileBanks = array(
        self::KOTAK     => IFSC::KKBK,
        self::AXIS_MIGS => IFSC::UTIB,
    );

    public static $emiBankToGatewayMap = array(
        IFSC::HDFC      =>  Gateway::HDFC,
        IFSC::UTIB      =>  Gateway::AXIS_MIGS
    );

    public static function isNetbankingBankDirectlySupported($bank)
    {
        return in_array($bank, self::$directNetbankingBankList);
    }

    public static function getChannel($gateway)
    {
        return self::$channels[$gateway];
    }

    public static function isValidGateway($gateway)
    {
        return (defined(__CLASS__.'::'.strtoupper($gateway)));
    }

    public static function getWalletGateways()
    {
        return self::$method[Method::WALLET];
    }

    public static function getGatewayForWallet($wallet)
    {
        return self::$walletToGatewayMap[$wallet];
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

    public static function supportsAuthAndCapture($gateway)
    {
        return (in_array($gateway, self::$authAndCapture));
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
}
