<?php

namespace RZP\Models\VirtualAccount;

use Lib\CRC16;
use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Card\Network;
use RZP\Models\BharatQr\Tags;
use RZP\Models\Merchant\Account;
use RZP\Models\BharatQr\Constants;
use RZP\Models\Merchant\Preferences;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

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
            'numeric' => [
                'default' => '222333',
                'handle'  => '222333',
                'special' => '222333',
            ],
            'alpha_numeric' => [
                'default' => null,
                'handle'  => null,
                'special' => null,
            ],
            'reserved' => [],
        ],
        self::KOTAK     => [
            'numeric' => [
                // Numeric used for merchants who have not set handle
                'default' => '139914',
                // Numeric used for merchants who have set a 4-char handle
                'handle'  => '139914',
                // Numeric used for merchants who have set a 3-char handle
                'special' => '139913',
            ],
            'alpha_numeric' => [
                // Alphanumeric used for merchants who have not set handle
                'default' => 'RAZO',
                // Alphanumeric used for merchants who have set a 4-char handle
                'handle'  => 'RZRP',
                // Alphanumeric used for merchants who have set a 3-char handle
                'special' => 'RAZR',
            ],
            // Used for our own nodal-to-nodal transfers
            'reserved' => [
                // DO NOT REFUND PAYMENTS MADE HERE
                'RZRN',
            ],
        ],
        self::DASHBOARD => [
            'numeric' => [
                'default' => '111222',
                'handle'  => '111222',
                'special' => '111222',
            ],
            'alpha_numeric' => [
                'default' => 'RAZO',
                'handle'  => 'RZRP',
                'special' => 'RAZR',
            ],
            'reserved' => [
                'RZRN',
            ],
        ],
    ];

    const DEFAULT_HANDLE_MAPPING = [
        // Default
        'RAZO'   => 'RPAY',
        // Test mode
        '111222' => '00',
        // Kotak
        '139913' => '00',
        '139914' => '0',
        // YesBank
        '222333' => '00',
    ];

    const PRIVILEGED_NUMERIC_HANDLE_MAPPING = [
        // BPCL gets 2223339
        Preferences::MID_BPCL => '9',
        // Tests
        Account::TEST_ACCOUNT => '9',
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

    protected $rupayTerminalId;

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

    //
    // Blocks test providers for making live requests
    //
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
        $pointOfInitiation = $this->getPointOfInitiation($qrCode);

        $merchantIdentifiers = $this->generateBharatQrMerchantIdentifier($qrCode);

        $tagArray = [
            Tags::VERSION . $this->getLengthAndValue(Constants::VERSION),
            Tags::POINT_OF_INITIATION . $this->getLengthAndValue($pointOfInitiation),
            $this->getIdentifierTlv(Tags::VISA, Terminal\Entity::VISA_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::MASTERCARD, Terminal\Entity::MC_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::RUPAY, Terminal\Entity::RUPAY_MPAN, $merchantIdentifiers),
            Tags::MERCHANT_ACCOUNT . $this->getLengthAndValue(Constants::MERCHANT_ACCOUNT),
            $this->getBharatQrUpiTlv($merchantIdentifiers),
            $this->getBharatQrDynamicUpiTlv($qrCode, $merchantIdentifiers),
            Tags::MERCHANT_CATEGORY .$this->getLengthAndValue(Constants::MERCHANT_CATEGORY),
            Tags::CURRENCY_CODE . $this->getLengthAndValue(Constants::CURRENCY_CODE),
            $this->getBharatQrAmountTlv($qrCode),
            Tags::COUNTRY_CODE . $this->getLengthAndValue(Constants::COUNTRY_CODE),
            Tags::MERCHANT_NAME . $this->getLengthAndValue(Constants::MERCHANT_NAME),
            Tags::MERCHANT_CITY . $this->getLengthAndValue(Constants::MERCHANT_CITY),
            Tags::MERCHANT_PIN_CODE . $this->getLengthAndValue(Constants::MERCHANT_PINCODE),
            $this->getBharatQrAdditionalDetailTlv($qrCode),
        ];

        $qrString =  implode('', $tagArray);

        // This is the CRC TL. Length of CRC is always 4
        $qrString .= Tags::CRC . '04';

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    protected function getIdentifierTlv(string $tag, string $networkMpan, array $merchantIdentifiers)
    {
        if (empty($merchantIdentifiers[$networkMpan]) === false)
        {
            return $tag . $this->getLengthAndValue($merchantIdentifiers[$networkMpan]);
        }

        return null;
    }

    protected function getPointOfInitiation($qrCode)
    {
        if (empty($qrCode->getAmount()) === true)
        {
            return Constants::STATIC_POI;
        }

        // Dynamic code always have amount tag
        return Constants::DYNAMIC_POI;
    }

    protected function getBharatQrUpiTlv(array $merchantIdentifiers)
    {
        $merchantVpa = $merchantIdentifiers[Terminal\Entity::VPA] ?? null;

        // This happens when no terminal of upi
        // bqr is assigned to the merchant.
        if (empty($merchantVpa) === true)
        {
            return null;
        }

        $rupayRidTlv = Tags::UPI_VPA_RUPAY_RID . $this->getLengthAndValue(Constants::RUPAY_RID);
        $merchantVpaTlv = Tags::UPI_VPA_MERCHANT_VPA . $this->getLengthAndValue($merchantVpa);

        $upiString = $rupayRidTlv . $merchantVpaTlv;

        return Tags::UPI_VPA . strlen($upiString) . $upiString;
    }

    protected function getBharatQrDynamicUpiTlv(QrCode\Entity $qrCode, array $merchantIdentifiers)
    {
        $merchantVpa = $merchantIdentifiers[Terminal\Entity::VPA] ?? null;

        // This happens when no terminal of upi
        // bqr is assigned to the merchant.
        if (empty($merchantVpa) === true)
        {
            return null;
        }

        $rupayRidTlv = Tags::UPI_VPA_RUPAY_RID . $this->getLengthAndValue(Constants::RUPAY_RID);

        //
        // In case of upi payments we need to send reference with
        // prefix. This is how they identify our payments
        //
        $transactionReferenceTlv = Tags::UPI_VPA_REFERENCE_TR . $this->getLengthAndValue(Constants::UPI_PREFIX . $qrCode->getId());

        $upiString = $rupayRidTlv . $transactionReferenceTlv;

        return Tags::UPI_VPA_REFERENCE . strlen($upiString) . $upiString;
    }

    protected function getBharatQrAdditionalDetailTlv(QrCode\Entity $qrCode)
    {
        $idTlv = Tags::ADDITIONAL_DETAIL_ID . $this->getLengthAndValue($qrCode->getId());

        if (empty($this->rupayTerminalId) === false)
        {
            $terminalIdTlv = Tags::TERMINAL_ID . $this->getLengthAndValue($this->rupayTerminalId);

            $idTlv .= $terminalIdTlv;
        }

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
     * @param QrCode\Entity $qrCode
     *
     * @return array
     * @throws Exception\LogicException
     */
    protected function generateBharatQrMerchantIdentifier(QrCode\Entity $qrCode)
    {
        $cardIdentifiers = array_filter($this->getCardIdentifiers($qrCode));

        $upiIdentifier = array_filter($this->getUpiIdentifier($qrCode));

        $allIdentifiers = array_merge($cardIdentifiers, $upiIdentifier);

        //
        // This is important to be here for the calling function.
        //
        if (count(array_filter($allIdentifiers)) === 0)
        {
            throw new Exception\LogicException(
                'No identifiers found for the merchant',
                null,
                [
                    'qr_code' => $qrCode->toArray()
                ]);
        }

        return $allIdentifiers;
    }

    protected function getCardIdentifiers(QrCode\Entity $qrCode): array
    {
        $identifiers = [];

        $bharatQrNetworks = Payment\Gateway::getBharatQrCardNetworks();

        foreach ($bharatQrNetworks as $bharatQrNetwork)
        {
            $mpanAttr = strtolower($bharatQrNetwork) . '_mpan';

            $terminal = $this->getTerminalForMethod(Payment\Method::CARD, $qrCode, $bharatQrNetwork);

            //
            // For a given network, we may not get any terminal at all. This is okay.
            // If we don't, we just search for the next network's terminal
            //
            if ($terminal === null)
            {
                continue;
            }

            if ($bharatQrNetwork === Network::RUPAY)
            {
                $this->rupayTerminalId = $terminal->getGatewayTerminalId();
            }

            $terminal = $terminal->toArray();

            $identifiers[$mpanAttr] = $terminal[$mpanAttr];
        }

        return $identifiers;
    }

    protected function getUpiIdentifier(QrCode\Entity $qrCode): array
    {
        $terminal = $this->getTerminalForMethod(Payment\Method::UPI, $qrCode);

        if ($terminal !== null)
        {
            $vpa = $terminal->getVpa();
        }

        $identifier[Terminal\Entity::VPA] = $vpa ?? null;

        return $identifier;
    }

    /**
     * This method will select the terminals using a dummy payment
     * The terminals will have all the mpans which will be used to
     * generate qr codes
     *
     * @param string        $method
     * @param QrCode\Entity $qrCode
     *
     * @param string|null   $network
     *
     * @return mixed
     */
    protected function getTerminalForMethod(string $method, QrCode\Entity $qrCode, string $network = null)
    {
        $paymentArray = (new Payment\Entity)->getDummyPaymentArray($method, $network);

        $paymentArray[Payment\Entity::RECEIVER] = $qrCode;

        $paymentProcessor = new PaymentProcessor($qrCode->merchant);

        return $paymentProcessor->processAndReturnTerminal($paymentArray);
    }
}
