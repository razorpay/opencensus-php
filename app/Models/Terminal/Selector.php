<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Constants\Environment;
use RZP\Models\Payment\Method;
use RZP\Services\SmartRouting;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Card\NetworkName;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Preferences;
use RZP\Constants\Entity as Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Gateway\Terminal\Service as TerminalService;

class Selector extends Base\Core
{
    protected $input;

    protected $options;

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\RuleFilter::class,
        Filters\TerminalBankFilter::class,
    ];

    /**
     * Very important that the sorting order is maintained
     * @var array
     */
    protected static $sorters = [
        // Sorts the card terminals based on gateway priorities
        Sorters\CardSorter::class,

        // Sorts the netbanking terminals based on gateway priorities
        Sorters\NetbankingSorter::class,

        //Sorts emandate terminals based on gateway priorities
        Sorters\EmandateSorter::class,

        // Boost a gateway terminals based on load distribution of probabilities
        Sorters\TerminalLoadSorter::class,

        // Sorting based on merchant category
        Sorters\MerchantSorter::class,

        // Boosts direct terminals over shared terminals
        Sorters\ExclusivitySorter::class,

        // Boosts specific auth type terminals over 3ds terminals
        Sorters\AuthTypeSorter::class,

        // Sorting based on older failed attempts
        Sorters\FailedTerminalsSorter::class,

        // Sorting based on gateway downtimes
        Sorters\GatewayDowntimeSorter::class,

        // Boosts terminals with gateway tokens over fallback terminal (without gateway tokens)
        // No fallback sorting. We are not giving priority
        // to the actual terminals as such. We will let the
        // normal sorter take care of it. [Discussed with Shk].
        // UN-SKIP THE CORRESPONDING TEST TOO!
        // Sorters\RecurringSorter::class
    ];

    public function __construct(array $input, Terminal\Options $options)
    {
        parent::__construct();

        $this->input = $input;

        $this->options = $options;

        $this->setGatewayTokensInInputIfApplicable();
    }

    protected function setGatewayTokensInInputIfApplicable()
    {
        $payment = $this->input['payment'];

        $token = $payment->getGlobalOrLocalTokenEntity();

        if (empty($token) === true)
        {
            $this->input['gateway_tokens'] = new Base\PublicCollection();
        }
        else
        {
            $reference = $payment->getReferenceForGatewayToken();

            $this->input['gateway_tokens'] = $this->repo
                                                  ->gateway_token
                                                  ->findByTokenAndReference($token, $reference, [Constants::TERMINAL]);
        }
    }

    public function createDirectTerminal($gateway)
    {
        $merchant = $this->input['merchant'];

        $payment = $this->input['payment'];

        $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getCurrency();

        $gatewayInput = [
            'currency_code'  => $currency,
            'trans_mode'     => 'CARDS',
        ];

        $input = [
            'gateway'        => $gateway,
            'gateway_input'  => $gatewayInput,
        ];

        return (new TerminalService)->onboardMerchant($merchant, $input, true);
    }

    public function select()
    {
        $allTerminals = $this->repo->useSlave(function ()
        {
            return $this->getTerminals();
        });

        $verbose = $this->isVerboseLogEnabled();

        $this->traceTerminals($allTerminals, 'Terminals fetched from db', $verbose);

        $applicableRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchApplicableRulesForPayment($this->input);
        });

        $this->processHitachiOnboarding($allTerminals);

        $filteredTerminals = $this->filterTerminals($allTerminals, $applicableRules, $verbose);

        $payment = $this->input['payment'];

        $sortedTerminals = $this->sortTerminals($filteredTerminals, $applicableRules, $verbose);

        if (empty($sortedTerminals) === true)
        {
            if (($this->isTestMode() === true) or
                ($this->app->environment('testing') === true))
            {
                //
                // The current list of terminals which were retrieved earlier do
                // not contain the sharp terminal and hence, making a call to DB.
                //
                $terminal = $this->repo->terminal->find(Shared::SHARP_RAZORPAY_TERMINAL);

                $sortedTerminals = array($terminal);
            }
            else if (($payment->isCard() === true) and ($payment->card->isRuPay() === true))
            {
                //
                // Rupay transactions for pharma merchants need to be routed through
                // the aala firstdata terminal. Hence adding this terminal manually,
                // in case no terminal found error comes.
                //
                if ($this->input['merchant']->getCategory2() === Category::PHARMA)
                {
                    $terminal = $this->repo->terminal->find('76lEBqibDvhOzY');

                    $sortedTerminals = [$terminal];
                }
                else
                {
                    //
                    // Only for Rupay card transactions if no terminal is found, we
                    // want to distribute payments via the following logic.
                    //

                    //
                    // We want to give 40 % load to FSS terminal 94RNvZoogX4kOB, and
                    // equal 10% load to other FirstData terminals, hence the below
                    // array structure
                    // courtesy : Sunny sir _/\_
                    //
                    $rupayTerminalSet = [
                        '94RNvZoogX4kOB',
                        '94RNvZoogX4kOB',
                        '94RNvZoogX4kOB',
                        '94RNvZoogX4kOB',
                        '76wS0y0kLvd2Z9',
                        '81x0D4UfzB1T7V',
                        '8f65Iykp4YRF31',
                        '7mugQsqdruXGSd',
                        '8AcyFtPYDi2rdx',
                        '76lEBqibDvhOzY',
                    ];

                    $selectedTerminalId = $rupayTerminalSet[array_rand($rupayTerminalSet)];

                    $terminal = $this->repo->terminal->find($selectedTerminalId);

                    $sortedTerminals = [$terminal];
                }
            }
            else if (($payment->isCard() === true) and
                     (($payment->card->isDiners() === true) or
                      ($payment->card->isNetworkUnknown() === true)))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
            else
            {
                throw new Exception\RuntimeException(
                    'No terminal found.',
                    ['payment' => $this->input['payment']->toArrayAdmin()]);
            }
        }

        $this->sendParametersToSmartRoutingService($this->input['payment'], $this->input['merchant'],
                                                    $allTerminals, $sortedTerminals, $filteredTerminals);
        return $sortedTerminals;
    }

    protected function getTerminals()
    {
        // Fetch terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->repo
                                  ->terminal
                                  ->getTerminalsForMerchantAndSharedMerchant($this->input['merchant']);

        $payment = $this->input['payment'];

        //
        // For second recurring payments, the payment must go through a designated
        // terminal, even if the merchant has since been unassigned from it. This
        // is achieved by referring to the gateway token, the original terminal of
        // that gateway token, and finding other usable terminals assigned to the
        // same primary merchant
        //
        if ($payment->isSecondRecurring() === true)
        {
            $possibleApplicableTerminals = $this->getTerminalsForSecondRecurringPayment();

            $merchantTerminals = $merchantTerminals->merge($possibleApplicableTerminals);
        }

        return $merchantTerminals->all();
    }

    protected function getTerminalsForSecondRecurringPayment()
    {
        $gatewayTokens = $this->input['gateway_tokens'];

        if ($gatewayTokens->count() === 0)
        {
            return [];
        }

        $merchantIdsForGatewayTokenTerminals = $gatewayTokens->pluck('terminal.merchant_id')
                                                             ->toArray();

        // Many gateway tokens, each associated with a terminal
        // Find all those terminals and gather all their merchant IDs
        //
        // Now query for appropriate terminals (type check)
        // that are assigned to any of these gathered merchants.

        $addTerminals = $this->repo
                             ->terminal
                             ->getByTypeAndMerchantIds(
                                    Type::RECURRING_NON_3DS,
                                    $merchantIdsForGatewayTokenTerminals);

        return $addTerminals;
    }

    protected function filterTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = false): array
    {
        //
        // Initially, the terminals are run through a filter class, which removes
        // the terminals which do not match the filters. For further iterations, the
        // filtered list of terminals is used to further filter upon using the other
        // filter classes.
        //
        $filteredTerminals = $terminals;

        foreach (self::$filters as $filter)
        {
            $filterRules = $this->getRulesForFiltering($rules);

            $filterObj = new $filter($this->input, $this->options, $filterRules);

            $filteredTerminals = $filterObj->filter($filteredTerminals, $verbose);

            $this->traceTerminals($filteredTerminals, 'Terminals after ' . $filter, $verbose);
        }

        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', true);

        return $filteredTerminals;
    }

    protected function sortTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = false): array
    {
        //
        // Sorting is done on the final list of filtered terminals.
        // The sorting is run for each of the sorting classes.
        //
        $sortedTerminals = $terminals;

        foreach (self::$sorters as $sorter)
        {
            $sorterRules = $this->getRulesForSorting($rules);

            $sorterObj = new $sorter($this->input, $this->options, $sorterRules);

            $sortedTerminals = $sorterObj->sort($sortedTerminals, $verbose);

            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', true);

        return $sortedTerminals;
    }

    protected function traceTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose === true) and (empty($terminals) === false))
        {
            $terminalData = array_pluck($terminals, 'gateway', 'id');

            $traceData = ['count' => count($terminals), 'terminals' => $terminalData, 'msg' => $msg];

            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }

    protected function getRulesForFiltering(Base\PublicCollection $rules): Base\PublicCollection
    {
        return $rules->filter(function ($rule)
        {
            return ($rule->isFilter() === true);
        });
    }

    /**
     * Verbosity of terminal selection logs are determined
     * by a flag held in cache
     * @return boolean verbosity flag
     */
    protected function isVerboseLogEnabled(): bool
    {
        try
        {
            $verbose = (bool) Cache::get(ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINAL_CONFIG_FETCH_ERROR);

            $verbose = false;
        }

        return $verbose;
    }

    protected function getRulesForSorting(Base\PublicCollection $rules): array
    {
        $sorterRules = $rules->filter(function ($rule)
        {
            return ($rule->isSorter() === true);
        });

        $sorterRules = $sorterRules->groupBySpecificityScore();

        return $sorterRules;
    }

    protected function processHitachiOnboarding(&$allTerminals)
    {
        try
        {
            $payment = $this->input['payment'];

            if (($payment->isMethod(Method::CARD) === true) and ($payment->isBharatQr() === false))
            {
                $merchant = $this->input['merchant'];

                $payment = $this->input['payment'];

                $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getCurrency();

                $hasHitachiDirectTerminal = (new TerminalService)->checkDirectTerminalForGateway(
                    $allTerminals,
                    Constants::HITACHI,
                    $merchant,
                    $currency);

                if ($hasHitachiDirectTerminal === false)
                {
                    $newTerminal = $this->createDirectTerminal(Constants::HITACHI);

                    if ($newTerminal !== null)
                    {
                        array_push($allTerminals, $newTerminal);
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_TERMINAL_CREATION_ERROR,
                [
                    'message'    => $e->getMessage(),
                ]);
        }

    }

    private function sendParametersToSmartRoutingService($payment, $merchant, $allTerminals, $sortedTerminals, $filteredTerminals)
    {
        try
        {
            if ($this->shouldHitRoutingService($merchant->getId()) === false)
            {
                return;
            }

            $paymentData = $payment->toArray();

            if ($payment->hasCard() === true)
            {
                $paymentData['card'] = $this->repo->card->findOrFail($payment->getCardId())->toArray();
            }

            if ($payment->getEmiPlanId() !== null)
            {
                $paymentData['emi'] = $payment->emiPlan();
            }

            $paymentData['meta_data'] = $this->getPaymentMetadataArray($payment);

            $downtimes = $this->repo->useSlave(function () use ($filteredTerminals)
            {
                return (new Downtime\Core)->getApplicableDowntimesForPayment($filteredTerminals, $this->input);
            });

            $failedTerminalIds = $this->options->getFailedTerminals();

            $merchantData = $this->getMerchantData($merchant);

            $data = [
                'payment'             => $paymentData,
                'merchant'            => $merchantData,
                'terminals'           => $allTerminals,
                'filtered_terminals'  => $sortedTerminals,
                'gateway_downtime'    => $downtimes,
                'failed_terminals'    => $failedTerminalIds,
                'gateway_tokens'      => $this->input['gateway_tokens'],
                'gateway_config'      => $this->getGatewayConfig(),
                'chance'              => $this->options->getChance(),
            ];

            $this->app->smartRouting->sendPaymentData($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::PAYMENTS_DATA_PUSH_ROUTING_SERVICE_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }
    }

    protected function shouldHitRoutingService(string $merchantId)
    {
        $isProduction = $this->app->environment(Environment::PRODUCTION);

        if ($isProduction === false)
        {
            return true;
        }

        if ($this->isTestMode() === true)
        {
            return false;
        }

        $response = $this->app->razorx->getTreatment($merchantId, 'payments_hit_routing_service', $this->mode);

        if (($response === 'on'))
        {
            return true;
        }

        return false;
    }


    protected function getGatewayConfig()
    {
        return [
            'cybersource_merchant_whitelist'      => Preferences::CYBERSOURCE_MERCHANT_WHITELIST,
            'mcc_filter_gateways'                 => Gateway::MCC_FILTER_GATEWAYS,
            'only_authorization_gateway'          => Gateway::$onlyAuthorizationGateway,
            'bit_position'                        => Terminal\Type::getBitPositions(),
            'gateway_acquirer_ifsc_mapping'       => Gateway::$gatewayAcquirerIfscMapping,
            'bharat_qr_card_network'              => Gateway::$bharatQrCardNetwork,
            'card_network_map'                    => Gateway::$cardNetworkMap,
            'card_network_recurring_map'          => Gateway::$cardNetworkRecurringMap,
            'netbanking_gateways'                 => Gateway::$netbankingGateways,
            'auth_type_to_emandate_gateway_map'   => Gateway::$authTypeToEmandateGatewayMap,
            'recurring_gateways'                  => Gateway::$recurringGateways,
            'upi_intent_gateways'                 => Gateway::$upiIntentGateways,
            'subscription_over_one_year_gateways' => Gateway::$subscriptionOverOneYearGateways,
            'headless'                            => Gateway::$headless,
            'emi_bank_to_gateway_map'             => Gateway::$emiBankToGatewayMap,
            'netbanking_to_gateway_map'           => Gateway::$netbankingToGatewayMap,
            'gateways_emandate_banks_map'         => Gateway::$gatewaysEmandateBanksMap,
            'emi_banks_card_terminals'            => Gateway::$emiBanksUsingCardTerminals,
            'gateway_supported_banks'             => Netbanking::getGatewaySupportedBankList(),
            'network_codes'                       => NetworkName::$codes
        ];
    }

    protected function getPaymentMetadataArray($payment)
    {
        $metadata = $payment->getMetadata();

        $metadata['payment_analytics'] = $metadata['payment_analytics']->toArray();

        return $metadata;
    }

    protected function getMerchantData($merchant)
    {
        $merchantData = [];

        $merchantData['id']                = $merchant->getId();
        $merchantData['entity']            = 'Merchant';
        $merchantData['live']              = $merchant->isLive();
        $merchantData['hold_funds']        = $merchant->getHoldFunds();
        $merchantData['pricing_plan_id']   = $merchant->getPricingPlanId();
        $merchantData['category']          = $merchant->getCategory();
        $merchantData['category_2']        = $merchant->getCategory2();
        $merchantData['international']     = $merchant->isInternational();
        $merchantData['has_key_access']    = $merchant->getHasKeyAccess();
        $merchantData['features']          = $merchant->getEnabledFeatures();

        $subMerchantIds = [];

        if ($merchant->isPartner() === true)
        {
            $subMerchants = (new MerchantCore())->listSubmerchants($merchant, []);

            foreach ($subMerchants as $subMerchant)
            {
                $subMerchantIds[] = $subMerchant->getId();
            }
        }
        $merchantData['sub_merchants_ids']  = $subMerchantIds;

        return $merchantData;

    }
}
