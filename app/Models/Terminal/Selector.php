<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Models\Admin\ConfigKey;
use RZP\Constants\Entity as Constants;

class Selector extends Base\Core
{
    protected $input;

    protected $options;

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\RuleFilter::class,
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

        // Boost a gateway terminals based on load distribution of probabilities
        Sorters\TerminalLoadSorter::class,

        // Sorting based on merchant category
        Sorters\MerchantSorter::class,

        // Boosts direct terminals over shared terminals
        Sorters\ExclusivitySorter::class,

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

    public function select()
    {
        $allTerminals = $this->getTerminals();

        $verbose = $this->isVerboseLogEnabled();

        $this->traceTerminals($allTerminals, 'Terminals fetched from db', $verbose);

        $applicableRules = (new Rule\Core)->fetchApplicableRulesForPayment($this->input);

        $filteredTerminals = $this->filterTerminals($allTerminals, $applicableRules, $verbose);

        $payment = $this->input['payment'];

        if (empty($filteredTerminals) === true)
        {
            $basicAuth = $this->app['basicauth'];

            $access = (($basicAuth->isPrivateAuth() === true) or
                       ($basicAuth->isPrivilegeAuth() === true));

            $token = $payment->getGlobalOrLocalTokenEntity();

            //
            // We are doing this only for second recurring
            // card payments made via private/privilege auth
            //
            if (($payment->isCard() === true) and ($payment->isRecurring() === true) and
                ($token !== null) and
                ($token->isRecurring() === true) and
                ($access === true))
            {
                //
                // For fallback, we need to get direct terminals which
                // support both recurring 3DS and recurring non-3DS
                // on a single terminal. These terminals usually allow
                // payments without 2FA first.
                //
                $filteredTerminals = $this->repo
                                          ->terminal
                                          ->getDirectRecurringTerminalsOfType($this->input['merchant'], 6)
                                          ->all();
            }
        }

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

        return $sortedTerminals;
    }

    protected function getTerminals()
    {
        // Fetch terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->repo
                                  ->terminal
                                  ->getTerminalsForMerchantAndSharedMerchant(
                                                        $this->input['merchant']);

        $payment = $this->input['payment'];

        //
        // For second recurring payments, the payment must go through a designated
        // terminal, even if the merchant has since been unassigned from it. This
        // is achieved by referring to the gateway token, the original terminal of
        // that gateway token, and finding other usable terminals assigned to the
        // same primary merchant
        //
        if ($payment->isSecondRecurring(true, $this->input['gateway_tokens']) === true)
        {
            $possibleApplicableTerminals = $this->getTerminalsForSecondRecurringPayment();

            $merchantTerminals = $merchantTerminals->merge($possibleApplicableTerminals);
        }

        return $merchantTerminals->all();
    }

    protected function getTerminalsForSecondRecurringPayment()
    {
        $gatewayTokens = $this->input['gateway_tokens'];

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
}
