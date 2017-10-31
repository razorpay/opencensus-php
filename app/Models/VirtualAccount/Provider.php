<?php

namespace RZP\Models\VirtualAccount;

use Config;
use Lib\CRC16;
use RZP\Base\Luhn;
use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\BharatQr;
use RZP\Models\Card\NetworkName;
use RZP\Models\BankAccount\Entity as BankAccount;

class Provider
{
    const YESBANK   = 'yesbank';
    const KOTAK     = 'kotak';

    // Qr Code Providers
    const BHARAT_QR = 'bharat_qr';

    // Dashboard acts as a mock provider bank,
    // and is used to run tests.
    // Also used when merchant makes a test
    // payment to a virtual account.
    const DASHBOARD = 'dashboard';

    const LIVE_PROVIDERS = [
        self::YESBANK,
        self::KOTAK,
    ];

    const TEST_PROVIDERS = [
        self::DASHBOARD,
    ];

    // Kotak's whitelisted IP
    const KOTAK_IP = '14.141.97.12';

    // Each provider gives us a range of bank accounts
    // by alloting an account number prefix/master/root
    //
    // We use the default root along with out own handle,
    // in cases where handle is unset.
    //
    // Standard root is used when handle is set.
    const ROOT = [
        self::YESBANK   => [
            // Todo
            'default'  => '',
            'standard' => '',
            'special'  => '',
            'reserved' => [],
        ],
        self::KOTAK     => [
            // Used for merchants who have not set handle
            'default'  => 'RAZO',
            // Used for merchants who have set a 4-char handle
            'standard' => 'RZRP',
            // Used for merchants who have set a 3-char handle
            'special'  => 'RAZR',
            // Used for our own nodal-to-nodal transfers
            'reserved' => [
                // DO NOT REFUND PAYMENTS MADE HERE
                'RZRN',
            ],
        ],
        self::DASHBOARD       => [
            'default'  => 'RAZO',
            'standard' => 'RZRP',
            'special'  => 'RAZR',
            'reserved' => [
                'RZRN',
            ],
        ],
    ];

    const DEFAULT_HANDLE_MAPPING = [
        'RAZO' => 'RPAY',
    ];

    // The default details are fixed by each provider, most specifically
    // the IFSC code where the virtual accounts are said to be located.
    // Further details can be derived from this IFSC, but are not required
    // for the virtual accounts use case
    //
    const DEFAULT_DETAILS = [
        self::YESBANK => [
            BankAccount::IFSC_CODE => 'YESB0CMSNOC',
        ],
        self::KOTAK => [
            BankAccount::IFSC_CODE => 'KKBK0000958',
        ],
        self::DASHBOARD => [
            BankAccount::IFSC_CODE => 'RAZR0000001',
        ],
    ];

    const IP = [
        self::YESBANK => [
            // Todo
            '*',
        ],
        self::KOTAK => [
            self::KOTAK_IP,
        ],
        self::DASHBOARD => [
            '*',
        ],
    ];

    public static function getBankCode(string $provider)
    {
        $ifsc = self::DEFAULT_DETAILS[$provider][BankAccount::IFSC_CODE];

        return substr($ifsc, 0, 4);
    }

    public static function validateLiveProvider(string $provider)
    {
        if (in_array($provider, self::LIVE_PROVIDERS, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid provider:'. $provider);
        }
    }

    // Checks if request is originating from known IP for the given provider
    public static function validateIp(string $provider, string $ip)
    {
        $providerIps = self::IP[$provider];

        if (in_array('*', $providerIps, true) === true)
        {
            return true;
        }

        if (in_array($ip, $providerIps, true) === true)
        {
            return true;
        }

        return false;
    }

    // Blocks test providers for making live requests
    //
    // Unused right now because Kotak is making changes in their
    // format, and IMPS testing is ongoing, so we need to use
    // Dashboard to make corrective requests occasionally.
    //
    // TODO: Use in validateProvider when changes are stable
    public static function validateMode(string $provider, string $mode)
    {
        $isLiveProvider = (in_array($provider, self::TEST_PROVIDERS, true) === false);

        return (($mode === Mode::TEST) or $isLiveProvider);
    }

    public static function isReservedAccount(string $accountNumber, string $provider)
    {
        $reservedRoots = self::ROOT[$provider]['reserved'];

        foreach ($reservedRoots as $root)
        {
            if (substr($accountNumber, 0, strlen($root)) === $root)
            {
                return true;
            }
        }

        return false;
    }

    public function generateQrString(QrCode\Entity $qrCode)
    {
        $provider = $qrCode->getProvider();

        switch ($provider)
        {
            case self::BHARAT_QR :
                return $this->getBharatQrCode($qrCode);

            default :
                return '';
        }
    }

    protected function getBharatQrCode($qrCode)
    {
        $visaIdentifier = $this->generateBharatQrMerchantIdentifier(NetworkName::VISA);

        $masterCardIdentifier =  $this->generateBharatQrMerchantIdentifier(NetworkName::MC);

        $visaTlv = BharatQr\Constants::VISA_TAG . strlen($visaIdentifier) . $visaIdentifier;

        $masterCardTlv = BharatQr\Constants::MASTERCARD_TAG . strlen($masterCardIdentifier) . $masterCardIdentifier;

        $tagArray = [
            BharatQr\Constants::VERSION_TLV,
            $visaTlv,
            $masterCardTlv,
            BharatQr\Constants::MERCHANT_CATEGORY_TLV,
            BharatQr\Constants::CURRENCY_CODE_TLV,
            $this->getBharatQrAmountTlv($qrCode),
            BharatQr\Constants::COUNTRY_CODE_TLV,
            BharatQr\Constants::MERCHANT_NAME_TLV,
            BharatQr\Constants::MERCHANT_CITY_TLV,
            $this->getBharatQrAdditionalDetailTlv($qrCode),
        ];

        $qrString =  implode('', $tagArray);

        // This is the CRC TL
        $qrString .= BharatQr\Constants::CRC_TL;

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    protected function getBharatQrAdditionalDetailTlv(QrCode\Entity $qrCode)
    {
        $idTlv = BharatQr\Constants::ID_TL . $qrCode->getId();

        $additionalDetailsString = $idTlv;

        return BharatQr\Constants::ADDITIONAL_DETAIL_TAG . strlen($additionalDetailsString) . $additionalDetailsString;
    }

    protected function getBharatQrAmountTlv(QrCode\Entity $qrCode)
    {
        $amount = (string) ($qrCode->getFormattedAmount());

        if (empty($amount) === true)
        {
            return '';
        }

        return BharatQr\Constants::AMOUNT_TAG . str_pad(strlen($amount), 2, '0', STR_PAD_LEFT) . $amount;
    }

    /**
     * This will generate merchant identifier using network
     * network could be Visa , MasterCard or Rupay
     *
     * @param string $network
     * @return string
     */
    protected function generateBharatQrMerchantIdentifier(string $network)
    {
        $acquirerCode = $this->getBharatQrAcquirerCode($network);

        $identifierPadding = Config::get('gateway.bharat_qr.identifier_padding');

        $identifier  = $acquirerCode . '0' . str_pad(strlen($identifierPadding), 8, '0', STR_PAD_LEFT);

        return $identifier . Luhn::computeCheckDigit($identifier);
    }

    protected function getBharatQrAcquirerCode(string $network)
    {
        return Config::get('gateway.bharat_qr.' . strtolower($network) . '_' . 'acquirer_code');
    }
}

