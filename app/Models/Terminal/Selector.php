<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode as ConstantMode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Gateway\Rule;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace;
use RZP\Trace\TraceCode;

class Selector
{
    protected $mode;
    protected $payment;
    protected $terminalRepo;
    protected $trace;
    protected $merchant;
    protected $input;

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\MerchantFilter::class,
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

        // Boosts direct terminals over shared terminals
        Sorters\ExclusivitySorter::class,

        // Sorting based on merchant category
        Sorters\MerchantSorter::class,

        // Sorting based on older failed attempts
        Sorters\FailedTerminalsSorter::class,

        // Sorting based on gateway downtimes
        Sorters\GatewayDowntimeSorter::class,
    ];

    public function __construct(Payment\Entity $payment, $mode)
    {
        $app = App::getFacadeRoot();

        $this->mode = $mode;

        $this->payment = $payment;

        $this->terminalRepo = $app['repo']->terminal;

        $this->trace = $app['trace'];

        $this->merchant = $payment->merchant;

        $this->input = [
            'payment'  => $this->payment,
            'merchant' => $this->merchant,
            'mode'     => $this->mode,
        ];
    }

    public function getTerminals()
    {
        // Fetch terminals for both the current merchant and the shared Merchant
        $merchantTerminals = $this->terminalRepo->getTerminalsForMerchantAndSharedMerchant(
            $this->merchant->getId());

        return $merchantTerminals->all();
    }

    public function select(Options $options = null, $verbose = false)
    {
        $terminals = $this->getTerminals();

        $this->traceTerminals($terminals, 'Terminals fetched from db', $verbose);

        $applicableRules = (new Rule\Core)->fetchApplicableRulesForPayment($terminals, $this->input);

        //
        // Initially, the terminals are run through a filter class, which removes
        // the terminals which do not match the filters. For further iterations, the
        // filtered list of terminals is used to further filter upon using the other
        // filter classes.
        //
        $filteredTerminals = $terminals;

        foreach (self::$filters as $filter)
        {
            $filterRules = $this->getRulesForFiltering($applicableRules);

            $filterObj = new $filter($options, $filterRules);

            $filteredTerminals = $filterObj->filter($filteredTerminals, $this->input, $verbose);

            $this->traceTerminals($filteredTerminals, 'Terminals after ' . $filter, $verbose);
        }

        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', true);

        //
        // Sorting is done on the final list of filtered terminals.
        // The sorting is run for each of the sorting classes.
        //
        $sortedTerminals = $filteredTerminals;

        // In case there are failed terminals, this comes in as an exclusion list from the
        // payment. We want to now place the excluded terminals at the bottom of the sorted
        // list thereby hoping a successful payment through the non failed terminals
        $failedTerminals = $options->getFailedTerminals();

        if ((count($failedTerminals) > 0))
        {
            $this->input['failed_terminals'] = $failedTerminals;
        }

        foreach (self::$sorters as $sorter)
        {
            $sorterRules = $this->getRulesForSorting($applicableRules);

            $sorterObj = new $sorter($sorterRules);

            $sortedTerminals = $sorterObj->sort($sortedTerminals, $this->input, $verbose, $options);

            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', true);

        $terminal = null;

        if (empty($sortedTerminals) === true)
        {
            if ($this->mode === ConstantMode::TEST)
            {
                // The current list of terminals which were retrieved earlier does
                // not contain the sharp terminal and hence, making a call to DB.
                $terminal = $this->terminalRepo->find(Shared::SHARP_RAZORPAY_TERMINAL);

                $sortedTerminals = array($terminal);
            }
            else
            {
                throw new Exception\RuntimeException(
                    'No terminal found.',
                    ['payment' => $this->payment->toArrayAdmin()]);
            }
        }

        $terminal = $sortedTerminals[0];

        $this->payment->associateTerminal($terminal);

        // hack to return multiple terminals if needed.
        if ($options and $options->getMultiple() === true)
        {
            return $sortedTerminals;
        }

        return $terminal;
    }

    protected function traceTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose === true) and (empty($terminals) === false))
        {
            $terminalData = array_pluck($terminals, 'id', 'gateway');

            $traceData = ['count' => count($terminals), 'terminals' => $terminalData, 'msg' => $msg];

            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }

    protected function getRulesForFiltering(Base\PublicCollection $rules): Base\PublicCollection
    {
        return $rules->filter(function ($rule)
        {
            return ($rule->isTypeFilter() === true);
        });
    }

    protected function getRulesForSorting(Base\PublicCollection $rules): Base\PublicCollection
    {
        $sorterRules = $rules->filter(function ($rule)
        {
            return ($rule->isTypeSorter() === true);
        });

        $merchantSpecificRules = $rules->filter(function ($rule)
        {
            return ($rule->getMerchantId() === $this->merchant->getId());
        });

        if ($merchantSpecificRules->isNotEmpty() === true)
        {
            return $merchantSpecificRules;
        }

        return $sorterRules;
    }

    /**
     * Methods selects a list of terminals for payment. We are
     * selecting a list here since, we want to iterate through
     * a bunch of terminals, in case the terminal fails
     *
     * @param array $opts
     * @return Entity
     * @throws Exception\RuntimeException
     */
    public function selectTerminals($opts = [])
    {
        $options = new Terminal\Options;

        if ((isset($opts[Options::FAILED]) === true) and
            (is_array($opts[Options::FAILED]) === true))
        {
            $options->setFailedTerminals($opts[Options::FAILED]);
        }

        // Disabling rotation in case of netbanking. This is with the
        // understanding that the terminal that is selected is the only
        // relevant terminal that should be used.
        //
        // Also removes failed terminals from exclusion list. This will
        // ensure the terminal is not rotated below the original terminals
        if ($this->payment->isNetbanking())
        {
            $options->setMultiple(false);
            $options->setFailedTerminals([]);
        }

        $terminalsSelected = $this->select($options);

        if ($options->getMultiple() === false)
        {
            // make this into an array, since the caller expects an array
            $terminalsSelected = array($terminalsSelected);
        }

        return $terminalsSelected;
    }
}
