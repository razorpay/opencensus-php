<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Provider
{
    protected $trace;
    protected $cache;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];
    }

    // Bank Account Providers
    const YESBANK   = 'yesbank';
    const KOTAK     = 'kotak';
    const ICICI     = 'icici';
    const RBL       = 'rbl';
    const RBL_JSW   = 'rbl_jsw';
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

    const IFSC = [
        self::YESBANK   => 'YESB0CMSNOC',
        self::KOTAK     => 'KKBK0000958',
        self::DASHBOARD => 'RAZR0000001',
        self::ICICI     => 'ICIC0000104',
        self::RBL       => 'RATN0VAAPIS',
        self::HDFC_ECMS => 'HDFC0000113',
        self::RBL_JSW   => 'RATN0000001'
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
        self::RBL_JSW => [
            BankAccount::IFSC_CODE => self::IFSC[self::RBL_JSW],
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
        ],
        self::RBL_JSW => [
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

    /**
     * function will return terminal from cache is present,
     * if not will call the callabck method and get terminals.
     * if will apply filters if present in function call parameters
     *
     * @param      $merchantId
     * @param      $getTerminalsCallback
     * @param null $filters
     *
     * @return mixed|\RZP\Models\Terminal\Entity
     */
    public function getTerminals($merchantId, $getTerminalsCallback, $filters = null): Terminal\Entity
    {
        try
        {
            $cacheKey          = VirtualAccount\Constant::TERMINAL_CACHE_PREFIX . '_' . $merchantId;
            $terminals         = $this->cache->get($cacheKey);
            $filteredTerminals = $terminals === null ? [] : $terminals;

            $this->trace->info(TraceCode::SMART_COLLECT_TERMINAL_CACHING, [
                'merchantId'        => $merchantId,
                'cacheKey'          => $cacheKey,
                'cacheValuePresent' => !empty($terminals),
                'terminalIds'        => $terminals != null ? array_column($terminals, 'id') : ''
            ]);

            if ((count($filteredTerminals) > 0) and $filters !== null)
            {
                $filteredTerminals = array_filter($terminals, function($terminalAttributes) use ($filters)
                {
                    return $filters($terminalAttributes);
                });
            }
            if (count($filteredTerminals) === 0)
            {
                $this->trace->count(Metric::SMART_COLLECT_TERMINAL_CACHING_MISS);
                $terminal    = $getTerminalsCallback();
                $terminals   = $terminals === null ? [] : $terminals;
                $terminals[] = $terminal->exportAttributes();
                $this->cache->set($cacheKey, $terminals, VirtualAccount\Constant::TERMINAL_CACHE_TTL);

                $this->trace->info(TraceCode::SMART_COLLECT_SET_TERMINAL_CACHE, [
                    'merchant id'           => $merchantId,
                    'cacheKey'              => $cacheKey,
                    'cached terminal ids'   => $terminals != null ? array_column($terminals, 'id') : ''
                ]);

                return $terminal;
            }
            else
            {
                $this->trace->count(Metric::SMART_COLLECT_TERMINAL_CACHING_HIT);
                $terminalAttributes = head($filteredTerminals);

                return (new Terminal\Entity())->buildFromAttributes($terminalAttributes);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::SMART_COLLECT_TERMINAL_CACHING_EXCEPTION);
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::SMART_COLLECT_TERMINAL_CACHING_UNAVAILABLE,
                                         [
                                             'merchant_id' => $merchantId,
                                         ]
            );
        }
        return $getTerminalsCallback();
    }

    public static function getUnsuportedProviderByRazorpay()
    {
        return array_values(self::getUnsupportedGateways());
    }

    public static function getUnsuportedProviderNamesByRazorpay()
    {
        return array_keys(self::getUnsupportedGateways());
    }

    public static function getUnsupportedGateways(): array
    {
        return [
            self::YESBANK => self::IFSC[self::YESBANK],
            self::ICICI => self::IFSC[self::ICICI],
            self::KOTAK => self::IFSC[self::KOTAK],
        ];
    }

    public static function getGatewaySyncProvider() {
        return [
            self::IFSC[Provider::RBL],
            self::IFSC[Provider::RBL_JSW]
        ];
    }

}
