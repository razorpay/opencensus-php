<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace;
use RZP\Trace\TraceCode;

class Selector
{
    protected $mode;
    protected $payment;
    protected $repo;
    protected $trace;
    protected $merchant;
    protected $input;

    protected static $filters = [
        Filters\TransactionFilter::class,
        Filters\MerchantFilter::class,
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
        Sorters\NewTerminalLoadSorter::class,

        // Boosts direct terminals over shared terminals
        Sorters\ExclusivitySorter::class,

        // Sorting based on merchant category
        Sorters\MerchantSorter::class,

        // Sorting based on older failed attempts
        Sorters\FailedTerminalsSorter::class,
    ];

    public function __construct(Payment\Entity $payment, $mode)
    {
        $app = App::getFacadeRoot();

        $this->mode = $mode;

        $this->payment = $payment;

        $this->repo = $app['repo']->terminal;

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
        $merchantTerminals = $this->repo->getTerminalsForMerchantAndSharedMerchant(
            $this->merchant->getId());

        // Fetch Shared Terminals
        $sharedTerminals = $this->repo->getAllSharedTerminals();

        $merchantTerminals = $merchantTerminals->merge($sharedTerminals);

        return $merchantTerminals;
    }

    public function select(Options $options = null, $verbose = false)
    {
        $terminals = $this->getTerminals();

        $this->traceTerminals($terminals, 'Terminals fetched from db', $verbose);

        //
        // Initially, the terminals are run through a filter class, which removes
        // the terminals which do not match the filters. For further iterations, the
        // filtered list of terminals is used to further filter upon using the other
        // filter classes.
        //
        $filteredTerminals = $terminals->all();

        foreach (self::$filters as $filter)
        {
            $filteredTerminals = (new $filter)->filter($filteredTerminals, $this->input, $verbose);
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
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input, $verbose, $options);
            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', true);

        $terminal = null;

        if (empty($sortedTerminals) === true)
        {
            if ($this->mode === Mode::TEST)
            {
                // The current list of terminals which were retrieved earlier does
                // not contain the sharp terminal and hence, making a call to DB.
                $terminal = $this->repo->find(Shared::SHARP_RAZORPAY_TERMINAL);

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
            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $traceData = ['count' => count($terminals), 'terminals' => $terminalIds, 'msg' => $msg];

            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
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
