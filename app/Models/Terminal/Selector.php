<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use Config;
use DeepCopy\DeepCopy;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Diag\EventCode;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\Constants as PaymentConstants;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
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
use RZP\Models\Card\IIN\MandateHub;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Merchant\Preferences;
use RZP\Constants\Entity as Constants;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Models\Gateway\Terminal\Service as TerminalService;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi\GatewayProcessor;
use Throwable;

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


    public function __construct(array $input, Terminal\Options $options = null)
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

        $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getGatewayCurrency();

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
        $payment = $this->input['payment'];

        $allTerminals = [];

        $verbose = false;

        // force_terminal_id is sent in the payment request in manual terminal testing flow, force_terminal_id is forcefully selected for payment inorder to test that terminal
        $forceTerminalId = $payment->getForceTerminalId();

        $fetchApiTerminals = $this->shouldFetchApiTerminals($payment);

        if (empty($forceTerminalId) === false)
        {
            $allTerminals = [];

            $this->trace->info(
                TraceCode::SELECTING_FORCED_TERMINAL_FOR_PAYMENT,
                [
                    'payment'           => $payment->toArray(),
                    'force_terminal_id' => $forceTerminalId,
                ]);

            array_push($allTerminals, $this->repo->terminal->getById($forceTerminalId));
        }
        else if ($fetchApiTerminals === true)
        {
            $allTerminals = $this->repo->useSlave(function ()
            {
                return $this->getTerminals();
            });

            $this->addMswipeTerminals($allTerminals);

            $this->processHitachiOnboarding($allTerminals);

            $this->processFulcrumOnboarding($allTerminals);

            $allTerminals = array_filter($allTerminals, function ($terminal)
            {
                $status = $terminal->getStatus();

                return (($terminal->isEnabled() === true) and
                        ($status === Status::ACTIVATED));
            });
        }

        $verbose = $this->isVerboseLogEnabled();

        $this->traceTerminals($allTerminals, 'Terminals fetched from db', $verbose);

        $sortedTerminals = [];

        $methods = $payment->fetchPaymentMethods();

        $isGooglePay = $payment->isGooglePay();

        // checking filtered terminals and razorX experiment for smart routing
        if ($this->shouldHitRoutingService($payment->getId()) === true)
        {
            try
            {
                $terminalSetSentToSmartRouting = [];

                // making a hash map of terminalId -> terminals
                foreach ($allTerminals as $terminal)
                {
                    $terminalSetSentToSmartRouting[$terminal['id']] = $terminal;
                }

                $terminalSetReceivedFromSmartRouting = [];

                foreach ($methods as $method)
                {
                    $payment->setMethod($method);

                    // calling the smart routing service for sorted terminals set
                    $methodTerminalSetReceivedFromSmartRouting = $this->sendParametersToSmartRoutingService($payment,
                        $this->input['merchant'], $allTerminals, $sortedTerminals);

                    if($methodTerminalSetReceivedFromSmartRouting !== null)
                    {
                        $terminalSetReceivedFromSmartRouting = array_merge($terminalSetReceivedFromSmartRouting, $methodTerminalSetReceivedFromSmartRouting);
                    }
                }

                $terminalIds = [];

                $newSelectedTerminals = [];

                if (empty($terminalSetReceivedFromSmartRouting) === false)
                {
                    if ($fetchApiTerminals === true) {
                        // creating new sorted terminals using order received from smart routing
                        foreach ($terminalSetReceivedFromSmartRouting as $terminal) {
                            // populating terminalIds array for data link layer
                            array_push($terminalIds, $terminal['id']);

                            //https://razorpay.slack.com/archives/CNV2GTFEG/p1678886082906659?thread_ts=1678871157.017139&cid=CNV2GTFEG
                            if ($terminalSetSentToSmartRouting[$terminal['id']] != null)
                            {
                            // populating newSortedTerminals array for the payment process
                                array_push($newSelectedTerminals, $terminalSetSentToSmartRouting[$terminal['id']]);
                            }
                            else
                            {
                                $this->trace->info(
                                    TraceCode::TERMINAL_API_SELECTION_MISMATCH,
                                    [
                                        'payment'     => $payment->getId(),
                                        'terminal_id' => $terminal['id'],
                                    ]);

                                $data = [
                                    'route' => $this->app['request.ctx']->getRoute()
                                ];

                                $this->trace->count(Terminal\Metric::TERMINAL_API_SELECTION_MISS, $data);
                            }

                        };
                    }
                    else
                    {
                        $newSelectedTerminals = Terminal\Service::getEntityArrayFromTerminalServiceResponse($terminalSetReceivedFromSmartRouting);

                        $this->trace->info(
                            TraceCode::TERMINALS_SERVICE_PAYMENT_TERMINALS,
                            [
                                'data' => $newSelectedTerminals,
                            ]);
                    }
                }

                if (count($newSelectedTerminals) > 0)
                {
                    $sortedTerminals = $newSelectedTerminals;
                }
                else if (($this->shouldFallback()) and ($fetchApiTerminals === true))
                {
                    $sortedTerminals = $this->filterAndSortTerminals($allTerminals, $verbose);

                    if (empty($sortedTerminals) === false)
                    {
                        $traceTerminals = [];

                        $terminalsArray = (new DeepCopy)->copy($sortedTerminals);

                        // remove sensitive data from logging
                        foreach ($terminalsArray as $terminal)
                        {
                            unset($terminal['mc_mpan'], $terminal['visa_mpan'], $terminal['rupay_mpan']);

                            array_push($traceTerminals, $terminal);
                        }

                        $this->trace->error(
                            TraceCode::SMART_ROUTING_TERMINALS_MISMATCH,
                            [
                                'terminals_from_api'            => $traceTerminals,
                                'terminals_from_smart_routing'  => $newSelectedTerminals,
                                'payment_id'                    => $payment->getId(),
                                'method'                        => $payment->getMethod(),

                            ]);
                    }
                }

                // sending the event to data link layer
                $this->app['diag']->trackPaymentEventV2(
                    EventCode::PAYMENT_TERMINALS_RECEIVED_FROM_SMART_ROUTING, $payment, null,
                    [
                        'metadata' => [
                            'payment' => [
                                'id'             => $payment->getPublicId(),
                                'terminal_ids'   => $terminalIds
                            ]
                        ],
                        'read_key'  => array('payment.id'),
                        'write_key' => 'payment.id'
                    ],
                    [
                        'terminal_ids' => $terminalIds,
                    ]
                );

            }
            catch (\Throwable $e)
            {
                $merchant = $this->input['merchant'];

                $sortedTerminals = [];

                if (($merchant->isFeatureEnabled(Features::RAAS) === false) and ($fetchApiTerminals === true)
                    and ($merchant->isFeatureEnabled(Features::ONLY_DS) === false))
                {
                    $sortedTerminals = $this->filterAndSortTerminals($allTerminals, $verbose);
                }

                $this->trace->error(
                    TraceCode::PAYMENTS_DATA_PUSH_ROUTING_SERVICE_ERROR,
                    [
                        'error'         => $e->getMessage(),
                        'payment_id'    => $payment->getId(),
                    ]);
            }

        }
        else
        {
            $sortedTerminals =[];

            foreach ($methods as $method)
            {
                $payment->setMethod($method);

                $sortedMethodTerminals = $this->filterAndSortTerminals($allTerminals, $verbose);

                if (empty($sortedMethodTerminals) === false)
                {
                    $sortedTerminals = array_merge($sortedTerminals, $sortedMethodTerminals);
                }
            }
        }

        if($isGooglePay === true)
        {
            $payment->setMethod(Method::UNSELECTED);
        }

        if (empty($sortedTerminals) === true)
        {
            if ((($this->isTestMode() === true) or ($this->app->environment('testing') === true))
                and $this->shouldHitRouterForUpiInAppPayments($payment) != true
                and $this->shouldBVTRequestHitRouter($this->app['request']->header('X-RZP-TESTCASE-ID')) !== true)
            {
                //
                // The current list of terminals which were retrieved earlier do
                // not contain the sharp terminal and hence, making a call to DB.
                //
                $terminal = $this->repo->terminal->find(Shared::SHARP_RAZORPAY_TERMINAL);
                $sortedTerminals = array($terminal);
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

//                $merchant->methods->setDinersCard(0);

                $this->alertDinersDisabledForMerchant($merchant, $payment);

//                $this->repo->saveOrFail($merchant->methods);
//
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

        return $sortedTerminals;
    }

    public function selectAuthenticationTerminal($terminal, $authenticationChannel)
    {
        $payment = $this->input['payment'];

        $merchant = $this->input['merchant'];

        $terminals = $this->sendParametersToSmartRoutingAuthN($payment, $merchant, $terminal, $authenticationChannel);

        return $terminals[0];
    }

    private function sendParametersToSmartRoutingAuthN($payment, $merchant, $terminal, $authenticationChannel)
    {
        try
        {
            $paymentData = $payment->toArray();

            if ($payment->hasCard() === true)
            {
                $card = $payment->card;

                $paymentData['card'] = $card->toArray();

                $iin = $card->iinRelation;

                if ($iin !== null)
                {
                    $flows = $iin->getFlows();

                    $paymentData['card']['flows'] = $flows;
                }
                $paymentData['card']['iin'] = $card->getIin();
                $paymentData['card']['token_iin'] = $card->getTokenIin();
            }

            if ($payment->getEmiPlanId() !== null)
            {
                $paymentData['emi'] = $this->getPaymentEmiArray($payment);
            }

            $paymentData['meta_data'] = $this->getPaymentMetadataArray($payment);

            $paymentData['application'] = $payment->getApplication();

            $paymentData[PaymentConstants::AUTHENTICATION_CHANNEL] = $authenticationChannel;

            $authNTerminals = $this->getAuthNTerminals();

            $merchantData = $this->getMerchantData($merchant);

            $data = [
                'payment'                           => $paymentData,
                'merchant'                          => $merchantData,
                'terminals'                         => array_values($terminal),
                'mode'                              => $this->mode,
                'chance'                            => $this->options->getChance(),
                'valid_auths'                       => $this->getValidAuths($payment, $authNTerminals),
                //'max_terminals'                   => $payment->getMaxRetryAttempt(),
            ];

            $terminals = $this->app->smartRouting->sendAuthNPaymentData($data);

            $traceData = $data;

            $unsetTerminals = [];

            // remove sensitive terminal data from logging
            foreach ($traceData['terminals'] as $traceTerminal)
            {
                unset($traceTerminal['mc_mpan'], $traceTerminal['visa_mpan'], $traceTerminal['rupay_mpan'], $traceTerminal['network_mpan']);

                array_push($unsetTerminals, $traceTerminal);
            }

            $traceData['terminals'] = $unsetTerminals;

            unset($traceData['authentication_terminals']);

            // remove sensitive data from logging
            unset($traceData['payment']['email'], $traceData['payment']['contact'], $traceData['payment']['notes']);

            // checking card key exist or not in array
            if (isset($traceData['payment']['card']) === true)
            {
                unset($traceData['payment']['card']);
            }

            $this->trace->info(
                TraceCode::SMART_ROUTING_REQUEST_AUTHENTICATION,
                [
                    'data' => $traceData,
                ]);

            return $terminals;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SMART_ROUTING_AUTHN_PUSH_FAILED,
                [
                    'error'             => $e->getMessage(),
                    'payment_id'        => $payment->getId(),
                ]);
        }
    }

    protected function getAuthNTerminals()
    {
        return AuthenticationTerminals::AUTHENTICATION_TERMINALS;
    }

    protected function getValidAuths($payment, $terminals)
    {
        $valid = [];

        // To select authentication terminals we first filter out all the terminals based on valid auths

        if ($payment->isMethodCardOrEmi() === true)
        {
            $autflowObj = new Terminal\Auth\Card\AuthFilter($payment);

            $authenticationGateways = array_unique(array_pluck($terminals, 'authentication_gateway'));

            // to get all the auths valid for the payment

            $valid = $autflowObj->getValidAuths($authenticationGateways);
        }

        return $valid;
    }

    protected function getTerminals()
    {

        $chargeAccountmerchant = (empty($this->input['charge_account_merchant']) === false) ? $this->input['charge_account_merchant'] : null;

        $merchant = $this->input['merchant'];

            // Fetch all terminals (enabled/disabled) for both the current merchant and the shared Merchant
            $merchantTerminals = $this->repo
                                      ->terminal
                                      ->getTerminalsForMerchantAndSharedMerchant($merchant);

            if ($chargeAccountmerchant !== null)
            {
                $chargeAccountMerchantTerminals = $this->repo
                                                   ->terminal
                                                   ->getTerminalsForMerchantAndSharedMerchant($chargeAccountmerchant);

                $merchantTerminals = $merchantTerminals->merge($chargeAccountMerchantTerminals);
            }

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

    protected function filterAndSortTerminals(array $allTerminals,  bool $verbose = false): array
    {
        $payment = $this->input['payment'];

        $forceTerminalId = $payment->getForceTerminalId();

        // For terminal testing, allTerminals will have only forced terminal here
        if (empty($forceTerminalId) === false)
        {
            return $allTerminals;
        }

        $applicableRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchApplicableRulesForPayment($this->input);
        });

        $selectedTerminals = $allTerminals;

        $selectedTerminals = $this->filterTerminals($selectedTerminals, $applicableRules, $verbose);

        $selectedTerminals = $this->sortTerminals($selectedTerminals, $applicableRules, $verbose);

        $this->removeSharedTerminalFromFallbackIfApplicable($selectedTerminals, $payment);

        return $selectedTerminals;

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


        foreach (self::$sorters as $sorter) {
            $sorterRules = $this->getRulesForSorting($rules);

            $sorterObj = new $sorter($this->input, $this->options, $sorterRules);

            $sortedTerminals = $sorterObj->sort($sortedTerminals, $verbose);

            //temporarily setting verbose true here

            $verbose = true;

            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);

            $verbose = false;
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
        return false;
        //commenting this for IPL
        /*try
        {
            $verbose = (bool) Cache::get(ConfigKey::TERMINAL_SELECTION_LOG_VERBOSE);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINAL_CONFIG_FETCH_ERROR);

            $verbose = false;
        }

        return $verbose;*/

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

    protected function processHitachiOnboarding(&$terminals)
    {
        try {
            if($this->shouldOnboardHitachiTerminal($terminals) === true) {
                $this->processDirectTerminalOnBoarding($terminals, Constants::HITACHI);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_TERMINAL_CREATION_ERROR);
        }
    }

    protected function processFulcrumOnboarding(&$terminals)
    {
        try {
            if($this->shouldOnboardFulcrumTerminal($terminals) === true) {
                $this->processDirectTerminalOnBoarding($terminals, Constants::FULCRUM);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_TERMINAL_CREATION_ERROR);
        }
    }

    private function sendParametersToSmartRoutingService($payment, $merchant, $allTerminals, $sortedTerminals)
    {
        try
        {
            $response = null;

            $paymentData = $payment->toArrayGateway();

            if ($payment->hasCard() === true)
            {
                $card = $payment->card;

                $paymentData['card'] = $card->toArray();

                $iin = $card->iinRelation;

                if ($iin !== null)
                {
                    $flows = $iin->getFlows();

                    $paymentData['card']['flows'] = $flows;

                    //Condition for sending mandate_hubs only during mandate create process
                    if (empty($this->input['card_mandate']) === false)
                    {
                        $mandateHubs = $iin->getApplicableMandateHubs($merchant, $payment->hasSubscription());

                        $paymentData['card']['mandate_hubs'] = $mandateHubs;
                    }
                }

                $paymentData['card']['tokenised'] = $card->isTokenPan();
                $paymentData['card']['iin'] = $card->getIin();
                $paymentData['card']['token_iin'] = $card->getTokenIin();

                if (empty($this->input['card_mandate']) === false)
                {
                    $mandateHubs = MandateHub::getEnabledMandateHubs($iin->getMandateHubs());

                    $paymentData['card']['mandate_hubs'] = $mandateHubs;
                }
            }

            if ($payment->getEmiPlanId() !== null)
            {
                $paymentData['emi'] = $this->getPaymentEmiArray($payment);
            }

            if (isset($paymentData['vpa']) === true)
            {
                $paymentData['vpa'] = $payment->getBankCodeFromVpa();
            }

            $paymentData['application'] = $payment->getApplication();

            $paymentData['meta_data'] = $this->getPaymentMetadataArray($payment);

            $paymentData['force_terminal_id'] = $payment->getForceTerminalId();

            $paymentData[PaymentConstants::AUTHENTICATION_CHANNEL] = $this->input[PaymentConstants::AUTHENTICATION_CHANNEL];

            if(($payment->getMethod() === Method::PAYLATER and (in_array($payment['wallet'], PayLater::$fullNameForSupportedBanks, true))) ||
                ($payment->getMethod() === Method::CARDLESS_EMI and (in_array($payment['wallet'], CardlessEmi::$fullNameForSupportedBanks, true))))
            {
                $paymentData['wallet'] = strtoupper($payment['wallet']);
            }

            if ((in_array($paymentData['method'], [Method::CARD, Method::UPI, Method::EMI]) === true ) and
                ($payment->isGooglePayCard() === false))
            {
                $downtimes = $this->repo->useSlave(function () use ($allTerminals) {
                    return (new Downtime\Core)->getApplicableDowntimesForPayment($allTerminals, $this->input);
                });
            }
            else
            {
                $downtimes = [];
            }

            $failedTerminalIds = $this->options->getFailedTerminals();

            $merchantData = $this->getMerchantData($merchant);

            $chargeAccountMerchantData = (empty($this->input['charge_account_merchant']) === false) ? $this->getMerchantData($this->input['charge_account_merchant']) : null;

            $merchantId = $payment->getMerchantId();

                 $data = [
                     'payment'                   => $paymentData,
                     'merchant'                  => $merchantData,
                     'gateway_downtime'          => $downtimes,
                     'mode'                      => $this->mode,
                     'failed_terminals'          => array_values($failedTerminalIds),
                     'gateway_tokens'            => $this->input['gateway_tokens'],
                     'charge_account_merchant'   => $chargeAccountMerchantData,
                 ];

            $tracePayment = $data['payment'];

            // remove sensitive data from logging
            unset($tracePayment['email'], $tracePayment['contact'], $tracePayment['notes']);

            // checking card key exist or not in array
            if (isset($tracePayment['card']) === true)
            {
                unset($tracePayment['card']);
            }

            $tokens = [];

            // Adding temporary logs for gateway token for nach
            if ( $payment->isMethod(Method::NACH) === true )
            {
                foreach($data['gateway_tokens'] as $token)
                {
                    array_push($tokens, $token['token_id']);
                }
            }

            $this->trace->info(
                TraceCode::SMART_ROUTING_REQUEST,
                [
                    'payment'             => $tracePayment,
                    'mode'                => $this->mode,
                    'merchant'            => $data['merchant'],
                    'gateway_downtime'    => $data['gateway_downtime'],
                    'failed_terminals'    => $data['failed_terminals'],
                    'gateway_tokens'      => $tokens,
                ]);

            $response = $this->app->smartRouting->sendPaymentData($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::PAYMENTS_DATA_PUSH_ROUTING_SERVICE_ERROR,
                [
                    'error'             => $e->getMessage(),
                    'payment_id'        => $payment->getId(),

                ]);
        }

        return $response;
    }

    protected function shouldFallback()
    {
        $payment = $this->input['payment'];

        $merchant = $this->input['merchant'];

        $excludedMethods = array(Method::EMANDATE,Method::CARD,Method::WALLET,Method::NETBANKING, Method::UPI);

        if ((in_array($payment[Entity::METHOD],$excludedMethods)) or
            ($merchant->isFeatureEnabled(Features::RAAS) === true))
        {
            return false;
        }

        if (in_array($merchant->getOrgId(), [OrgEntity::HDFC_COLLECT_ORG_ID, OrgEntity::HDFC_ORG_ID]))
        {
            return false;
        }

        if($this->merchant->isFeatureEnabled(Feature\Constants::ONLY_DS) === true)
        {
            return false;
        }

        if ($payment->isInAppUPI() === true)
        {
            return false;
        }

        if ($payment[Entity::METHOD] === Method::EMI)
        {
            return $this->isEMIFallbackApplicable();
        }

        return true;
    }

    protected function isEMIFallbackApplicable(){

        $payment = $this->input['payment'];

        // For will have fallback only for hdfc & indb for now, will be removing them once rules are fixed for these as well
        if ($payment[Entity::BANK] === IFSC::HDFC || $payment[Entity::BANK] === IFSC::INDB)
        {
            return true;
        }

        return false;

    }

    protected function shouldHitRoutingService(string $paymentId = null)
    {
        $payment = $this->input['payment'];

        // for cash on delivery payments, there is no gateway involved, hence we can bypass
        // routing logic
        if (($payment->isCoD() === true) or
            ($payment->isOffline() === true))
        {
            return false;
        }

        $card = $payment->card;

        // For HDFC DC EMI, we need not send the request to smart routing till the same is
        // implemented at the routing service
        if (($payment[Entity::METHOD] === Method::EMI) and
            ($payment[Entity::BANK] === IFSC::HDFC) and
            ($card[Card\Entity::TYPE] === Card\Type::DEBIT))
        {
            $variantFlag = $this->app->razorx->getTreatment($paymentId, "ROUTER_HDFC_DEBIT_EMI",  $this->mode);

            if ($variantFlag === 'on_hdfc_debit_emi')
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        // if its a BVT and its an app payment the call should go through router
        if($this->shouldHitRouterForUpiInAppPayments($payment) === true)
        {
            return true;
        }

        if($this->shouldBVTRequestHitRouter($this->app['request']->header('X-RZP-TESTCASE-ID')) === true)
        {
            return true;
        }

        // Route payments to router in Prod and stage
        if (in_array($this->app['env'], [Environment::PRODUCTION, Environment::BETA, Environment::AXIS, Environment::FUNC, Environment::PERF, Environment::PERF2]) === false)
        {
            return false;
        }

        if ($paymentId === null)
        {
            $this->trace->info(TraceCode::PAYMENT_ID_NULL);

            return false;
        }

        return true;
    }

    protected function getGatewayConfig()
    {
        // in case of any changes in gateway config, please contact smart routing team
        // changes done here won't be reflected in routing

        return [
            'cybersource_merchant_whitelist'       => Preferences::CYBERSOURCE_MERCHANT_WHITELIST,
            'mcc_filter_gateways'                  => Gateway::MCC_FILTER_GATEWAYS,
            'only_authorization_gateway'           => Gateway::$onlyAuthorizationGateway,
            'bit_position'                         => Terminal\Type::getBitPositions(),
            'gateway_acquirer_ifsc_mapping'        => Gateway::$gatewayAcquirerIfscMapping,
            'bharat_qr_card_network'               => Gateway::$bharatQrCardNetwork,
            'card_network_map'                     => Gateway::$cardNetworkMap,
            'card_network_recurring_map'           => Gateway::$cardNetworkRecurringMap,
            'netbanking_gateways'                  => Gateway::$netbankingGateways,
            'auth_type_to_emandate_gateway_map'    => Gateway::$authTypeToEmandateGatewayMap,
            'recurring_gateways'                   => Gateway::$recurringGateways,
            'upi_intent_gateways'                  => Gateway::$upiIntentGateways,
            'subscription_over_one_year_gateways'  => Gateway::$subscriptionOverOneYearGateways,
            'headless'                             => Gateway::$headless,
            'emi_bank_to_gateway_map'              => Gateway::$emiBankToGatewayMapForRouteService,
            'emi_bank_to_card_type_to_gateway_map' => Gateway::$emiBankToGatewayMap,
            'netbanking_to_gateway_map'            => Gateway::$netbankingToGatewayMap,
            'gateways_emandate_banks_map'          => Gateway::$gatewaysEmandateBanksMap,
            'emi_banks_card_terminals'             => Gateway::$emiBanksUsingCardTerminals,
            'gateway_supported_banks'              => Netbanking::getGatewaySupportedBankList(),
            'network_codes'                        => NetworkName::$codes,
            'categories'                           => Terminal\Category::CATEGORIES
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

        if ((isset($metadata[UpiMetadata\Entity::UPI_METADATA]) === true) and
            ($metadata[UpiMetadata\Entity::UPI_METADATA] instanceof UpiMetadata\Entity))
        {
            $metadata[UpiMetadata\Entity::UPI_METADATA] = $metadata[UpiMetadata\Entity::UPI_METADATA]->toArray();
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
        $merchantData['methods']           = $merchant->getMethods()->toArray();
        $merchantData['purpose_code']      = $merchant->getPurposeCode();
        $merchantData['country_code']      = $merchant->getCountry();

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
        $merchant = $this->input['merchant'];


        $terminalUpdated = (new Terminal\Service())->checkAndAddMswipeTerminals($terminals, $merchant);

        if ($terminalUpdated === true) {

            // This flow should not trigger ideally, adding log here to verify, will remove the code once confirmed
            $this->trace->info(
                TraceCode::PAYMENTS_MWSIPE_TERMINAL_ASSIGNEMENT,
                [
                    'merchant'           => $merchant->getId(),
                ]);

            $terminals = $this->repo->useSlave(function ()
            {
                return $this->getTerminals();
            });
        }
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
        ];

        $this->trace->critical(TraceCode::DICL_TERMINAL_NOT_FOUND, $alertArray);

        $message = 'Diners Club payment failed with no terminal found';

        $this->app['slack']->queue(
            $message,
            $alertArray,
            [
                'channel'   =>    Config::get('slack.channels.pgob_alerts'),
                'username'  =>    'alerts',
                'icon'      =>    ':x:'
            ]
        );
    }

    private function shouldFetchApiTerminals($payment): bool
    {

        if ($payment->isOffline() === true)
        {
            return true;
        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false
            && $payment[Entity::METHOD] === Method::NETBANKING)
        {
            return true;
        }

        $merchantId = $payment->getMerchantId();

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "API_ROUTER_NEW_CONTRACT_2",  $this->mode);

        if ($variantFlag === 'proxy_ts')
        {
            return false;
        }

        return true;
    }

    protected function processDirectTerminalOnBoarding(&$allTerminals, $gateway) {
        try
        {
            $newTerminal = $this->createDirectTerminal($gateway);
            if ($newTerminal !== null)
            {
                array_push($allTerminals, $newTerminal);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_TERMINAL_CREATION_ERROR);
        }
    }

    protected function shouldOnboardHitachiTerminal($allTerminals): bool {
        $payment = $this->input['payment'];
        $merchant = $this->input['merchant'];

        if ($merchant->isFeatureEnabled(Feature\Constants::ONLY_DS) === true ||
            $merchant->isFeatureEnabled(Feature\Constants::SKIP_HITACHI_AUTO_ONBOARD) === true ||
            (isset($payment[Entity::RECEIVER_TYPE]) && $payment[Entity::RECEIVER_TYPE] === Receiver::POS))
        {
            $this->trace->info(
                TraceCode::SKIPPING_HITACHI_AUTOMATIC_ONBOARDING,
                [
                    'payment'             => $payment,
                    'merchant'            => $merchant,
                ]);
            return false;
        }

        // We dont want to onboard hitachi terminal if payment
        // is done using amex cards, since hitachi does not support it
        // Slack: https://razorpay.slack.com/archives/CNV2GTFEG/p1673252018996959?thread_ts=1673001461.787579&cid=CNV2GTFEG
        if (($payment->isMethodCardOrEmi() === true) and
            (Gateway::isCardNetworkUnsupportedOnGateway(Gateway::HITACHI, ($payment->card->getNetworkCode() ?? '')) === true))
        {
            $this->trace->info(
                TraceCode::SKIPPING_HITACHI_AUTOMATIC_ONBOARDING_UNSUPPORTED_NETWORK,
                [
                    'payment'             => $payment,
                    'merchant'            => $merchant,
                    'card_network'        => ($payment->card->getNetworkCode() ?? ''),
                ]);

            return false;
        }

        $createTerminalCondition = (($payment->isMethod(Method::CARD) === true) and
        ($payment->isBharatQr() === false) and
        ((in_array($merchant->getCategory(), \RZP\Gateway\Hitachi\Gateway::BLACKLISTED_MCC) === false) or
            ($merchant->isFeatureEnabled(Feature\Constants::OVERRIDE_HITACHI_BLACKLIST) === true)));

        if ($createTerminalCondition === true)
        {
            $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getGatewayCurrency();
            $hasDirectTerminal = (new TerminalService)->checkDirectTerminalForGateway(
                $allTerminals,
                Constants::HITACHI,
                $merchant,
                $currency);

            return ($hasDirectTerminal === false);
        }
        return false;
    }

    protected function shouldOnboardFulcrumTerminal($allTerminals): bool {
        $payment = $this->input['payment'];
        $merchant = $this->input['merchant'];

        if ($merchant->isFeatureEnabled(Feature\Constants::ONLY_DS) === true ||
            $merchant->isFeatureEnabled(Feature\Constants::SKIP_FULCRUM_AUTO_ONBOARD) === true ||
            (isset($payment[Entity::RECEIVER_TYPE]) && $payment[Entity::RECEIVER_TYPE] === Receiver::POS))
        {
            $this->trace->info(
                TraceCode::SKIPPING_FULCRUM_AUTOMATIC_ONBOARDING,
                [
                    'payment'             => $payment,
                    'merchant'            => $merchant,
                ]);
            return false;
        }

        $currency = $payment->getGatewayCurrency();
        $hasHitachiTerminal = (new TerminalService)->checkDirectTerminalForGateway(
            $allTerminals,
            Constants::HITACHI,
            $merchant,
            $currency);

        $createTerminalCondition = (($payment->isMethod(Method::CARD) === true) and
        ($hasHitachiTerminal === true) and
        ($payment->isBharatQr() === false) and ($currency === Currency::INR) and ($payment->isDCC() === false));

        if($createTerminalCondition === true) {
            $hasDirectTerminal = (new TerminalService)->checkDirectTerminalForFulcrumGateway(
                $allTerminals,
                Constants::FULCRUM,
                $merchant,
                $currency);

            if($hasDirectTerminal === false) {
                $paymentId = $payment->getId();

                return $this->checkRazorXExperimentForFulcrumOnBoarding($paymentId);
            }
            return false;
        }
        return false;
    }

    protected function checkRazorXExperimentForFulcrumOnBoarding(string $paymentId = null): bool {
        if($paymentId === null)
            return false;

        $variantFlag = $this->app->razorx->getTreatment($paymentId, "ROUTER_FULCRUM_ON_BOARDING", $this->mode);

        $this->trace->info(
            TraceCode::RAZORX_VARIANT_FULCRUM_ONBOARDING,
            [
                'paymentID'          => $paymentId,
                'variant'            => $variantFlag,
            ]);

        if ($variantFlag === 'on')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    private function removeSharedTerminalFromFallbackIfApplicable(&$selectedTerminals, $payment)
    {
        // for upi autopay, after oc101, we can only use shared terminals for exisiting mandates subsequent debit.
        // so, basically only for auto payments of older mandates, never for initial payments (new mandate registrations).
        if(($payment->isUpiRecurring() === true) and
           ($payment->isRecurringTypeAuto() === false))
        {
            $selectedTerminals = array_filter($selectedTerminals, function ($terminal) {
                return $terminal['merchant_id'] !== "100000Razorpay";
            });

            $this->trace->info(
                TraceCode::UPI_RECURRING_FALLBACK_REMOVED_SHARED_TERMINAL,
                [
                    'Removed shared terminal in the fallback'        => $selectedTerminals,
                ]);
        }
    }

    private function shouldHitRouterForUpiInAppPayments($payment)
    {
        // if its a BVT and its an app payment the call should go through router
        if((in_array($this->app['env'], [Environment::BVT]) === true) and $payment->isInAppUPI())
        {
            return true;
        }
    }
}
