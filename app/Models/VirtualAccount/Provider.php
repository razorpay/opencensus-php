<?php

namespace RZP\Models\VirtualAccount;

use App;
use Lib\CRC16;
use RZP\Error;
use RZP\Exception;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Network;
use RZP\Models\BharatQr\Tags;
use RZP\Models\Base\PublicEntity;
use RZP\Models\BharatQr\Constants;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Provider
{
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    // Bank Account Providers
    const YESBANK   = 'yesbank';
    const KOTAK     = 'kotak';
    const ICICI     = 'icici';
    const RBL       = 'rbl';
    const HDFC_ECMS = 'hdfc_ecms';

    const UPI_ICICI = 'upi_icici';

    /*
     * Dashboard acts as a mock bank account
     * provider, and it's been used to run test.
     *
     * Also used when merchant makes a test
     * payment to a virtual account.
     */
    const DASHBOARD = 'dashboard';
    const AUTOMATION = 'automation';
    // Qr Code Providers
    const BHARAT_QR = 'bharat_qr';
    const UPI_QR    = 'upi_qr';

    // Qr Code Tag Values constants
    const MERCHANT_CATEGORY = 'merchant_category';
    const MERCHANT_NAME     = 'merchant_name';
    const MERCHANT_CITY     = 'merchant_city';
    const MERCHANT_PINCODE  = 'merchant_pincode';

    const IFSC = [
        self::YESBANK   => 'YESB0CMSNOC',
        self::KOTAK     => 'KKBK0000958',
        self::DASHBOARD => 'RAZR0000001',
        self::ICICI     => 'ICIC0000104',
        self::RBL       => 'RATN0VAAPIS',
        self::HDFC_ECMS => 'HDFC0000113',
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
        self::ICICI => [
            BankAccount::IFSC_CODE => self::IFSC[self::ICICI],
        ],
        self::RBL => [
            BankAccount::IFSC_CODE => self::IFSC[self::RBL],
        ],
        self::HDFC_ECMS => [
            BankAccount::IFSC_CODE => self::IFSC[self::HDFC_ECMS],
        ],
    ];

    const LIVE_PROVIDERS = [
        self::YESBANK,
        self::KOTAK,
        self::ICICI,
        self::RBL,
    ];

    const TEST_PROVIDERS = [
        self::DASHBOARD,
    ];

    // Kotak's whitelisted IP
    const KOTAK_IP = '14.141.97.12';

    const IP = [
        self::YESBANK => [
            // Todo
            '*',
        ],
        self::KOTAK => [
            '*',
        ],
        self::DASHBOARD => [
            '*',
        ],
        self::AUTOMATION => [
            '*',
        ],
        self::ICICI => [
            '*'
        ],
        self::RBL => [
            '*',
        ],
        self::HDFC_ECMS => [
            '*',
        ]
    ];

    const VPA_HANDLE = [
        self::UPI_ICICI => 'icici',
    ];

    public static function getBankCode(string $provider)
    {
        $ifsc = self::DEFAULT_DETAILS[$provider][BankAccount::IFSC_CODE];

        return substr($ifsc, 0, 4);
    }

    public static function getRoot(Terminal\Entity $terminal): string
    {
        return $terminal->getGatewayMerchantId();
    }

    public static function getHandle(Terminal\Entity $terminal): string
    {
        return $terminal->getGatewayMerchantId2() ?: '';
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

    public function generateQrString(QrCode\Entity $qrCode)
    {
        $provider = $qrCode->getProvider();

        switch ($provider)
        {
            case self::BHARAT_QR :
                return $this->getBharatQrCode($qrCode);

            case self::UPI_QR:
                return $this->getUpiQrCode($qrCode);

            default :
                return '';
        }
    }

    protected function getBharatQrCode($qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_BHARAT_QR_CODE, $qrCode->toArrayPublic());

        $pointOfInitiation = $this->getPointOfInitiation($qrCode);

        $merchantIdentifiers = $this->generateBharatQrMerchantIdentifier($qrCode);

        $merchantDetails = $this->getMerchantDetailsToPopulate($qrCode);

        $tagArray = [
            Tags::VERSION . $this->getLengthAndValue(Constants::VERSION),
            Tags::POINT_OF_INITIATION . $this->getLengthAndValue($pointOfInitiation),
            $this->getIdentifierTlv(Tags::VISA, Terminal\Entity::VISA_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::MASTERCARD, Terminal\Entity::MC_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::RUPAY, Terminal\Entity::RUPAY_MPAN, $merchantIdentifiers),
            $this->getMerchantAccountIdentifier(),
            $this->getBharatQrUpiTlv($qrCode, $merchantIdentifiers),
            $this->getBharatQrDynamicUpiTlv($qrCode, $merchantIdentifiers),
            Tags::MERCHANT_CATEGORY .$this->getLengthAndValue($merchantDetails[self::MERCHANT_CATEGORY]),
            Tags::CURRENCY_CODE . $this->getLengthAndValue(Constants::CURRENCY_CODE),
            $this->getBharatQrAmountTlv($qrCode),
            Tags::COUNTRY_CODE . $this->getLengthAndValue(Constants::COUNTRY_CODE),
            Tags::MERCHANT_NAME . $this->getLengthAndValue($merchantDetails[self::MERCHANT_NAME]),
            Tags::MERCHANT_CITY . $this->getLengthAndValue($merchantDetails[self::MERCHANT_CITY]),
            Tags::MERCHANT_PIN_CODE . $this->getLengthAndValue($merchantDetails[self::MERCHANT_PINCODE]),
            $this->getBharatQrAdditionalDetailTlv($qrCode, $merchantIdentifiers),
        ];

        $qrString =  implode('', $tagArray);

        // This is the CRC TL. Length of CRC is always 4
        $qrString .= Tags::CRC . '04';

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    protected function getMerchantDetailsToPopulate($qrCode): array
    {
        $defaultAttributes = $this->getDefaultMerchantDetails();

        $merchant = $qrCode->merchant;

        $merchantAttributes = [
            self::MERCHANT_CATEGORY => $merchant->getCategory(),
            self::MERCHANT_NAME     => $merchant->getDbaName(),
        ];

        $attributes = array_merge($defaultAttributes, array_filter($merchantAttributes));

        if ($merchant->merchantDetail !== null)
        {
            $merchantDetail = $merchant->merchantDetail;

            $merchantDetailAttributes = [
                self::MERCHANT_CITY    => $merchantDetail->getBusinessRegisteredCity(),
                self::MERCHANT_PINCODE => $merchantDetail->getBusinessRegisteredPin(),
            ];

            $attributes = array_merge($attributes, array_filter($merchantDetailAttributes));
        }

        return $attributes;
    }

    protected function getDefaultMerchantDetails(): array
    {
        return [
            self::MERCHANT_CATEGORY => Constants::MERCHANT_CATEGORY,
            self::MERCHANT_NAME     => Constants::MERCHANT_NAME,
            self::MERCHANT_CITY     => Constants::MERCHANT_CITY,
            self::MERCHANT_PINCODE  => Constants::MERCHANT_PINCODE,
        ];
    }

    protected function getIdentifierTlv(string $tag, string $networkMpan, array $merchantIdentifiers)
    {
        if (empty($merchantIdentifiers[$networkMpan]) === false)
        {
            return $tag . $this->getLengthAndValue($merchantIdentifiers[$networkMpan]);
        }

        return null;
    }

    protected function getMerchantAccountIdentifier()
    {
        $value = Constants::IFSC_CODE . Constants::ACCOUNT_NUMBER;

        return Tags::MERCHANT_ACCOUNT . $this->getLengthAndValue($value);
    }

    protected function getPointOfInitiation(QrCode\Entity $qrCode)
    {
        if (empty($qrCode->getAmount()) === true)
        {
            return Constants::STATIC_POI;
        }

        // Dynamic code always have amount tag
        return Constants::DYNAMIC_POI;
    }

    protected function getBharatQrUpiTlv(QrCode\Entity $qrCode, array $merchantIdentifiers)
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

        $amountTlv = '';

        $amount = (string) ($qrCode->getFormattedAmount());

        if (empty($amount) === false)
        {
            $amountTlv = Tags::UPI_VPA_AMOUNT . $this->getLengthAndValue($amount);
        }

        $upiString = $rupayRidTlv . $merchantVpaTlv . $amountTlv;

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
        $transactionReferenceTlv = Tags::UPI_VPA_REFERENCE_TR .
                                   $this->getLengthAndValue(Constants::UPI_PREFIX . $qrCode->getId());

        $upiString = $rupayRidTlv . $transactionReferenceTlv;

        return Tags::UPI_VPA_REFERENCE . strlen($upiString) . $upiString;
    }

    protected function getBharatQrAdditionalDetailTlv(QrCode\Entity $qrCode, array $merchantIdentifiers)
    {
        $idTlv = Tags::ADDITIONAL_DETAIL_ID . $this->getLengthAndValue($qrCode->getId());

        if (isset($merchantIdentifiers['rupay_tid']) === true)
        {
            $terminalIdTlv = Tags::TERMINAL_ID . $this->getLengthAndValue($merchantIdentifiers['rupay_tid']);

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
        $app = App::getFacadeRoot();

        // If cards aren't enabled at all, we skip addition of card identifiers
        if ($this->isMethodEnabledForMerchant(Payment\Method::CARD, $qrCode->merchant) === false)
        {
            return [];
        }

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
                $identifiers['rupay_tid'] = $terminal->getGatewayTerminalId();
            }

            $terminal = $terminal->toArray();

            // Note: terminal at this point will have tokenized mpans, we are storing tokenized mpans in qr_string,
            // We will be detokenizing them as and when required (to generate actual qr_string for qr_code)
            $identifiers[$mpanAttr] = $terminal[$mpanAttr];

            // If the mpan is tokenized, detokenize it
            if ((empty($identifiers[$mpanAttr]) === false) and (strlen($identifiers[$mpanAttr]) !== 16))
            {
                $identifiers[$mpanAttr] = $app['mpan.cardVault']->detokenize($identifiers[$mpanAttr]);
            }
            /*
             * Masterpass specifications indicate only 15 digits of mastercard mpan be populated in the qr
             * string. The last digit is generated by the bank/app at the time of scanning and validated using
             * luhn formula. Since many apps follows Masterpass specifications, we are making this change at
             * our end as well.
             */

            if ($bharatQrNetwork === Network::MC)
            {
                $identifiers[$mpanAttr] = substr($identifiers[$mpanAttr], 0, 15);
            }
        }

        $traceIdentifiers = $identifiers;
        unset($traceIdentifiers[Terminal\Entity::VISA_MPAN]);
        unset($traceIdentifiers[Terminal\Entity::MC_MPAN]);
        unset($traceIdentifiers[Terminal\Entity::RUPAY_MPAN]);

        $this->trace->info(TraceCode::BHARAT_QR_CARD_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifiers' => $traceIdentifiers,
                           ]);

        return $identifiers;
    }

    protected function getUpiIdentifier(QrCode\Entity $qrCode): array
    {
        // If UPI isn't enabled at all, we skip addition of UPI identifiers
        if ($this->isMethodEnabledForMerchant(Payment\Method::UPI, $qrCode->merchant) === false)
        {
            return [];
        }

        $terminal = $this->getTerminalForMethod(Payment\Method::UPI, $qrCode);

        if ($terminal !== null)
        {
            $vpa = $terminal->getVpa();
        }

        $identifier[Terminal\Entity::VPA] = $vpa ?? null;

        $this->trace->info(TraceCode::BHARAT_QR_UPI_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifier' => $identifier,
                           ]);

        return $identifier;
    }

    protected function isMethodEnabledForMerchant(string $method, Merchant\Entity $merchant)
    {
        $methods = $merchant->getMethods();

        return $methods->isMethodEnabled($method);
    }

    protected function getUpiQrCode(QrCode\Entity $qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_UPI_QR_CODE, $qrCode->toArrayPublic());

        $terminal = $this->getTerminalForMethod(Payment\Method::UPI, $qrCode, null, [
            'flow'  => 'intent'
        ]);

        if (($terminal instanceof Terminal\Entity) === false)
        {
            throw new Exception\LogicException('UPI intent terminal needs to there for merchant',
                Error\ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                [
                    'merchant_id'   => $qrCode->merchant->getId(),
                ]);
        }

        // Once we have mocked the complete payment, we can call gateway
        $gatewayInput = [
            'payment'           => [
                Payment\Entity::ID              => $qrCode->getId(),
                Payment\Entity::AMOUNT          => $qrCode->getAmount(),
                Payment\Entity::CURRENCY        => 'INR',
                Payment\Entity::DESCRIPTION     => $qrCode->source->description,
                Payment\Entity::RECEIVER_TYPE   => Receiver::QR_CODE,
                Payment\Entity::RECEIVER_ID     => $qrCode->getId(),
            ],
            'merchant'          => $qrCode->merchant,
            'terminal'          => $terminal,
        ];

        if ($qrCode->source->hasOrder() === true)
        {
            $gatewayInput['order'] = $qrCode->source->entity;
        }

        $mode = app('rzp.mode');

        // Any exception on gateway will rollback the process right away
        $response = app('gateway')->call($terminal->getGateway(), 'get_intent_url', $gatewayInput, $mode, $terminal);

        // To Signed terminals, gateway will return qr_code_url along with intent_url
        // otherwise gateway will only return intent_url, And qr_code_url is for QR.
        if (isset($response['data']['qr_code_url']))
        {
            return $response['data']['qr_code_url'];
        }
        else if (isset($response['data']['intent_url']))
        {
            return $response['data']['intent_url'];
        }
    }

    /**
     * This method will select the terminals using a dummy payment
     * The terminals will have all the mpans which will be used to
     * generate qr codes
     *
     * @param string        $method
     * @param PublicEntity  $receiver
     *
     * @param string|null   $network
     * @param array|[]      $metadata
     *
     * @return mixed
     */
    public function getTerminalForMethod(
        string $method,
        PublicEntity $receiver,
        string $network = null,
        array $metadata = [])
    {
        $paymentArray = (new Payment\Entity)->getDummyPaymentArray($method, $receiver, $network, $metadata);

        $paymentProcessor = new PaymentProcessor($receiver->merchant);

        return $paymentProcessor->processAndReturnTerminal($paymentArray);
    }
}
