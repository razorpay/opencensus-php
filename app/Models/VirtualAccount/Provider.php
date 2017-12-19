<?php

namespace RZP\Models\VirtualAccount;

use Config;
use Lib\CRC16;
use RZP\Base\Luhn;
use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\BharatQr\Tags;
use RZP\Models\BharatQr\Lengths;
use RZP\Models\BharatQr\Constants;
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
        self::YESBANK => [
            // Todo
            'numeric_default'       => '',
            'alpha_numeric_default' => '',
            'alpha_numeric_handle'  => '',
            'alpha_numeric_special' => '',
            'reserved'              => [],
        ],
        self::KOTAK     => [
            // Numeric used for merchants who have not set handle
            'numeric_default'       => '139913',
            // Alphanumeric used for merchants who have not set handle
            'alpha_numeric_default' => 'RAZO',
            // Alphanumeric used for merchants who have set a 4-char handle
            'alpha_numeric_handle'  => 'RZRP',
            // Alphanumeric used for merchants who have set a 3-char handle
            'alpha_numeric_special' => 'RAZR',
            // Used for our own nodal-to-nodal transfers
            'reserved'              => [
                // DO NOT REFUND PAYMENTS MADE HERE
                'RZRN',
            ],
        ],
        self::DASHBOARD       => [
            'numeric_default'       => '111111',
            'alpha_numeric_default' => 'RAZO',
            'alpha_numeric_handle'  => 'RZRP',
            'alpha_numeric_special' => 'RAZR',
            'reserved'              => [
                'RZRN',
            ],
        ],
    ];

    const DEFAULT_HANDLE_MAPPING = [
        'RAZO'   => 'RPAY',
        '111111' => '00',
        '139913' => '00',
    ];

    const IFSC = [
        self::YESBANK   => 'YESB0CMSNOC',
        self::KOTAK     => 'KKBK0000958',
        self::DASHBOARD => 'RAZR0000001',
    ];

    // The default details are fixed by each provider, most specifically
    // the IFSC code where the virtual accounts are said to be located.
    // Further details can be derived from this IFSC, but are not required
    // for the virtual accounts use case
    //
    const DEFAULT_DETAILS = [
        self::YESBANK => [
            BankAccount::IFSC_CODE => self::IFSC[self::YESBANK],
        ],
        self::KOTAK => [
            BankAccount::IFSC_CODE => self::IFSC[self::KOTAK],
        ],
        self::DASHBOARD => [
            BankAccount::IFSC_CODE => self::IFSC[self::DASHBOARD],
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

        $visaTlv = Tags::VISA . $this->getLengthAndValue($visaIdentifier);

        $masterCardTlv = Tags::MASTERCARD . $this->getLengthAndValue($masterCardIdentifier);

        $tagArray = [
            Tags::VERSION . $this->getLengthAndValue(Constants::VERSION),
            $visaTlv,
            $masterCardTlv,
            Tags::MERCHANT_CATEGORY .$this->getLengthAndValue(Constants::MERCHANT_CATEGORY),
            Tags::CURRENCY_CODE . $this->getLengthAndValue(Constants::CURRENCY_CODE),
            $this->getBharatQrAmountTlv($qrCode),
            Tags::COUNTRY_CODE . $this->getLengthAndValue(Constants::COUNTRY_CODE),
            Tags::MERCHANT_NAME . $this->getLengthAndValue(Constants::MERCHANT_NAME),
            Tags::MERCHANT_CITY . $this->getLengthAndValue(Constants::MERCHANT_CITY),
            $this->getBharatQrAdditionalDetailTlv($qrCode),
        ];

        $qrString =  implode('', $tagArray);

        // This is the CRC TL. Length of CRC is always 2
        $qrString .= Tags::CRC . '02';

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    protected function getBharatQrAdditionalDetailTlv(QrCode\Entity $qrCode)
    {
        $idTlv = Tags::ID . $this->getLengthAndValue($qrCode->getId());

        $additionalDetailsString = $idTlv;

        return Tags::ADDITIONAL_DETAIL . strlen($additionalDetailsString) . $additionalDetailsString;
    }

    protected function getBharatQrAmountTlv(QrCode\Entity $qrCode)
    {
        $amount = (string) ($qrCode->getFormattedAmount());

        if (empty($amount) === true)
        {
            return '';
        }

        return Tags::AMOUNT . $this->getLengthAndValue($amount);
    }

    protected function getLengthAndValue(string $str)
    {
        return str_pad(strlen($str), 2, '0', STR_PAD_LEFT) . $str;
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
