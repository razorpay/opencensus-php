<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use RZP\Constants\Mode as ConstantMode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Gateway\Rule;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace;
use RZP\Trace\TraceCode;

class Selector extends Base\Core
{
    protected $input;

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
    }

    public function select()
    {
        $allTerminals = $this->getTerminals();

        $verbose = $this->isVerboseLogEnabled();

        $this->traceTerminals($allTerminals, 'Terminals fetched from db', $verbose);

        $applicableRules = (new Rule\Core)->fetchApplicableRulesForPayment($allTerminals, $this->input);

        $filteredTerminals = $this->filterTerminals($allTerminals, $applicableRules, $verbose);

        $sortedTerminals = $this->sortTerminals($filteredTerminals, $applicableRules, $verbose);

        if (empty($sortedTerminals) === true)
        {
            if (($this->isTestMode() === true) or ($this->app->environment('testing') === true))
            {
                // The current list of terminals which were retrieved earlier does
                // not contain the sharp terminal and hence, making a call to DB.
                $terminal = $this->repo->terminal->find(Shared::SHARP_RAZORPAY_TERMINAL);

                $sortedTerminals = array($terminal);
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
                                        $this->input['merchant']->getId());

        return $merchantTerminals->all();
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
