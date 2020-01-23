<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use Config;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Diag\EventCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Constants\Environment;
use RZP\Models\Payment\Method;
use RZP\Services\SmartRouting;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Entity;
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
use RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi\GatewayProcessor;

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

    /**
     * Sorters running in smart routing service
     * @var array
     */
    protected static $smartRoutingSorters = [

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

        $this->addMswipeTerminals($allTerminals);

        $this->processHitachiOnboarding($allTerminals);

        $allTerminals = array_filter($allTerminals, function ($terminal)
        {
            return $terminal->isEnabled() === true;
        });

        $verbose = $this->isVerboseLogEnabled();

        $this->traceTerminals($allTerminals, 'Terminals fetched from db', $verbose);

        $applicableRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchApplicableRulesForPayment($this->input);
        });

        $filteredTerminals = $this->filterTerminals($allTerminals, $applicableRules, $verbose);

        $payment = $this->input['payment'];

        $shouldHitRoutingServiceFlag = 0;


        // checking filtered terminals and razorX experiment for smart routing
        if ((empty($filteredTerminals) === false) && ($this->shouldHitRoutingService($payment->getId()) === true))
        {
            $shouldHitRoutingServiceFlag = 1;
        };

        $sortedTerminals = $this->sortTerminals($filteredTerminals, $applicableRules, $verbose, $shouldHitRoutingServiceFlag);

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
                     ($payment->card->isNetworkUnknown() === true))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
            else if (($payment->isCard() === true) and
                     ($payment->card->isDiners() === true))
            {
                $merchant = $this->input[Constants::MERCHANT];

                $merchant->methods->setDinersCard(0);

                $this->alertDinersDisabledForMerchant($merchant, $payment);

                $this->repo->saveOrFail($merchant->methods);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
            else if ($payment[Entity::METHOD] === Method::NETBANKING)
            {
                $merchant = $this->input[Constants::MERCHANT];

                // raising an alert on slack for no terminal found
                $this->alertNetbankingTerminalNotFound($merchant, $payment);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
            }
            else
            {
                throw new Exception\RuntimeException(
                    'No terminal found.',
                    ['payment' => $this->input['payment']->toArrayAdmin()],
                    null,
                    ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND
               );
            }
        }

        if ($shouldHitRoutingServiceFlag === 1)
        {
            try
            {
                $terminalSetSentToSmartRouting = [];

                // making a hash map of terminalId -> terminals
                foreach ($sortedTerminals as $terminal)
                {
                    $terminalSetSentToSmartRouting[$terminal['id']] = $terminal;
                }

                // calling the smart routing service for sorted terminals set
                $terminalSetReceivedFromSmartRouting = $this->sendParametersToSmartRoutingService($payment,
                    $this->input['merchant'], $allTerminals, $sortedTerminals, $filteredTerminals);

                $terminalIds = [];

                $newSortedTerminals = [];

                if ($terminalSetReceivedFromSmartRouting !== null)
                {
                    // creating new sorted terminals using order received from smart routing
                    foreach ($terminalSetReceivedFromSmartRouting as $terminal)
                    {
                        // populating terminalIds array for data link layer
                        array_push($terminalIds, $terminal['id']);

                        // populating newSortedTerminals array for the payment process
                        array_push($newSortedTerminals, $terminalSetSentToSmartRouting[$terminal['id']]);

                    };

                }

                if (count($newSortedTerminals) > 0)
                {
                    $sortedTerminals = $newSortedTerminals;
                }
                else
                {

                    $sortedTerminals = $this->sortTerminals($filteredTerminals, $applicableRules, $verbose, 2);

                    $this->trace->error(
                        TraceCode::SMART_ROUTING_TERMINALS_COUNT_IS_ZERO,
                        [
                            'input_terminals'    => $sortedTerminals,
                            'sorted_terminals_from_smart_routing' => $newSortedTerminals,
                            'is_error_timeout' => $terminalSetReceivedFromSmartRouting != null ? false : true,
                        ]);
                }

                // sending the event to data link layer
                $this->app['diag']->trackPaymentEvent(
                    EventCode::PAYMENT_SORTED_TERMINALS_RECEIVED_FROM_SMART_ROUTING, $payment, null,
                    [
                        'sorted_terminal_ids' => $terminalIds,
                    ]
                );
            }
            catch (\Throwable $e)
            {
                $sortedTerminals = $this->sortTerminals($filteredTerminals, $applicableRules, $verbose, 2);

                $this->trace->error(
                    TraceCode::PAYMENTS_DATA_PUSH_ROUTING_SERVICE_ERROR,
                    [
                        'error' => $e->getMessage(),
                    ]);
            }
        }

        return $sortedTerminals;
    }

    protected function getTerminals()
    {
        // Fetch all terminals (enabled/disabled) for both the current merchant and the shared Merchant
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

    // $shouldHitRoutingService = 0 i.e Run all sorters
    // 1 = run the diff
    // 2 = fallback
    protected function sortTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = false, int $shouldHitRoutingService = 0): array
    {
        //
        // Sorting is done on the final list of filtered terminals.
        // The sorting is run for each of the sorting classes.
        //
        $sortedTerminals = $terminals;

        // default sorters
        $sorters = self::$sorters;

        // removing smart routing sorters from the list of api sorters
        if ($shouldHitRoutingService === 1)
        {
            $sorters = array_diff(self::$sorters, self::$smartRoutingSorters);
        }
        else if ($shouldHitRoutingService === 2)
        {
            $sorters = self::$smartRoutingSorters;
        }

        foreach ($sorters as $sorter)
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

            $merchant = $this->input['merchant'];

            if (($payment->isMethod(Method::CARD) === true) and ($payment->isBharatQr() === false)
                and (in_array($merchant->getCategory(), GatewayProcessor::HITACHI_BLACKLISTED_MCC) === false))
            {
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
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_TERMINAL_CREATION_ERROR);
        }

    }

    private function sendParametersToSmartRoutingService($payment, $merchant, $allTerminals, $sortedTerminals, $filteredTerminals)
    {

        try
        {
            $response = null;

            $paymentData = $payment->toArray();

            if ($payment->hasCard() === true)
            {
                $card = $this->repo->card->findOrFail($payment->getCardId());

                $paymentData['card'] = $card->toArray();

                $iin = $card->iinRelation;

                if ($iin !== null)
                {
                    $flows = $iin->getFlows();

                    $paymentData['card']['flows'] = $flows;
                }
            }

            if ($payment->getEmiPlanId() !== null)
            {
                $paymentData['emi'] = $this->getPaymentEmiArray($payment);
            }

            if (isset($paymentData['vpa']) === true)
            {
                $paymentData['vpa'] = $payment->getPspFromVpa();
            }

            $paymentData['meta_data'] = $this->getPaymentMetadataArray($payment);

            if (in_array($paymentData['method'], [Method::CARD, Method::UPI, Method::EMI]) === true )
            {
                $downtimes = $this->repo->useSlave(function () use ($filteredTerminals) {
                    return (new Downtime\Core)->getApplicableDowntimesForPayment($filteredTerminals, $this->input);
                });
            }
            else
            {
                $downtimes = [];
            }

            $failedTerminalIds = $this->options->getFailedTerminals();

            $merchantData = $this->getMerchantData($merchant);

            $data = [
                'payment'             => $paymentData,
                'merchant'            => $merchantData,
                'terminals'           => array_values($allTerminals),
                'filtered_terminals'  => array_values($sortedTerminals),
                'gateway_downtime'    => $downtimes,
                'failed_terminals'    => array_values($failedTerminalIds),
                'gateway_tokens'      => $this->input['gateway_tokens'],
                'gateway_config'      => $this->getGatewayConfig(),
                'chance'              => $this->options->getChance(),
            ];

            $this->trace->info(
                TraceCode::SMART_ROUTING_REQUEST,
                [
                    'payment'             => $data['payment'],
                    'merchant'            => $data['merchant'],
                    'filtered_terminals'  => $data['filtered_terminals'],
                    'gateway_downtime'    => $data['gateway_downtime'],
                    'failed_terminals'    => $data['failed_terminals'],
                ]);

            $response = $this->app->smartRouting->sendPaymentData($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::PAYMENTS_DATA_PUSH_ROUTING_SERVICE_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }

        return $response;
    }

    protected function shouldHitRoutingService(string $paymentId = null)
    {
        $isProduction = $this->app->environment(Environment::PRODUCTION);

        if ($isProduction === false)
        {
            return false;
        }

        if ($this->isTestMode() === true)
        {
            return false;
        }

        if ($paymentId === null)
        {
            $this->trace->info(TraceCode::PAYMENT_ID_NULL);
            return false;
        }

        $response = $this->app->razorx->getTreatment($paymentId, 'payments_hit_routing_service', $this->mode);

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
            'network_codes'                       => NetworkName::$codes,
            'categories'                          => Terminal\Category::CATEGORIES
        ];
    }

    protected function getPaymentMetadataArray($payment)
    {
        $metadata = $payment->getMetadata();

        if ( (isset ($metadata['payment_analytics']) === true) and
            ($metadata['payment_analytics'] !== null ))
        {
            $metadata['payment_analytics'] = $metadata['payment_analytics']->toArray();
        }

        return $metadata;
    }

    protected function getPaymentEmiArray(Entity $payment): array
    {
        $emiPlanArray = [];
        $emiPlan = $payment->emiPlan;

        $emiPlanArray['issuer_name']         = $emiPlan->getIssuerName();
        $emiPlanArray['rate']                = $emiPlan->getRate();
        $emiPlanArray['duration']            = $emiPlan->getDuration();
        $emiPlanArray['emi_subvention']      = $emiPlan->getSubvention();

        return $emiPlanArray;
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
        $merchantData['fee_bearer']        = $merchant->getFeeBearer();
        $merchantData['org_id']            = $merchant->getOrgId();

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

    protected function alertNetbankingTerminalNotFound(Merchant\Entity $merchant, $payment)
    {
        $alertArray = [
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'bank'                  => $payment[Entity::BANK],
            'amount'                => $payment[Entity::AMOUNT],
        ];

        $this->trace->critical(TraceCode::NETBANKING_TERMINAL_NOT_FOUND, $alertArray);

        $message = 'Netbanking payment failed with no terminal found';

        $this->app['slack']->queue(
            $message,
            $alertArray,
            [
                'channel'               => Config::get('slack.channels.pgob_alerts'),
                'username'              => 'alerts',
                'icon'                  => ':x:'
            ]
        );
    }

    // this is for usemswipeterminal enabled merchant, if the $mswipeTerminalIds are not in fetched list of terminals, we
    // add the merchant as submerchant for all mswipeTerminals. This is a temporary soln, in future we will be modifying
    // fetch terminals to get terminals of parent merchant as well
    protected function addMswipeTerminals(&$terminals)
    {
        try {
            $merchant = $this->input['merchant'];

            $mswipeTerminalIds = ['C7EW8LggSH7FnY', 'CXjvHPZlPnqWBX', 'CNqL80h9pI0hsI', 'CHYaN0FnjkG5ni',
                'CWybuzsFqa9KDz'];

            if ($merchant->isUseMswipeTerminalsEnabled() === false)
            {
                return;
            }

            $terminalIds = $this->getTerminalIds($terminals);

            $diff = array_diff($mswipeTerminalIds, $terminalIds);

            if (count($diff) === 0)
            {
                return;
            }

            foreach ($mswipeTerminalIds as $mswipeTerminalId)
            {
                if (in_array($mswipeTerminalId, $terminalIds) === true)
                {
                    continue;
                }

                (new Service)->addMerchantToTerminal($mswipeTerminalId, $merchant->getId());
            }

            // disabling cache for this merchant for terminal fetch as merchant has been added as submerchant to other
            // terminals, new fetch result will have these extra terminals in result.
            $cacheTag = Terminal\Entity::getCacheTag($merchant->getId());

            (new Terminal\Entity)->flushCache($cacheTag);

            $terminals = $this->getTerminals();
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::PAYMENTS_MWSIPE_TERMINAL_ASSIGNEMENT_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }
    }

    protected function getTerminalIds($terminals)
    {
        $terminalIds = [];

        foreach ($terminals as $terminal)
        {
            $terminalIds[] = $terminal->getId();
        }

        return $terminalIds;
    }

    protected function alertDinersDisabledForMerchant(Merchant\Entity $merchant, $payment)
    {
        $alertArray = [
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'payment_id'            => $payment[Entity::ID],
            'payment_international' => $payment[Entity::INTERNATIONAL],
            'network'               => 'DICL',
            'reason'                => 'no terminal found',
            'action_taken'          => 'diners club disabled for merchant'
        ];

        $this->trace->critical(TraceCode::DISABLING_DINERS_FOR_MERCHANT, $alertArray);

        $message = 'Diners Club payment failed with no terminal found';

        $this->app['slack']->queue(
            $message,
            $alertArray,
            [
                'channel'               => Config::get('slack.channels.pgob_alerts'),
                'username'              => 'alerts',
                'icon'                  => ':x:'
            ]
        );
    }
}
