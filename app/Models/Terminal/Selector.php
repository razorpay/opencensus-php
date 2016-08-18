<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode;
use RZP\Models\Card\Network;
use RZP\Models\Payment;

use RZP\Trace;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

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
        Filters\MiscFilter::class,
    ];

    /**
     * Very important that the sorting order is maintained
     * ExclusivitySorter should be at end, for giving preferrence to direct terminals
     * @var array
     */
    protected static $sorters = [
        Sorters\CardSorter::class,
        Sorters\NetbankingSorter::class,
        Sorters\MerchantSorter::class,
        Sorters\ExclusivitySorter::class,
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

        $this->traceTerminals($filteredTerminals, 'Terminals after filtration', $verbose);

        //
        // Sorting is done on the final list of filtered terminals.
        // The sorting is run for each of the sorting classes.
        //
        $sortedTerminals = $filteredTerminals;

        foreach (self::$sorters as $sorter)
        {
            $sortedTerminals = (new $sorter)->sort($sortedTerminals, $this->input, $verbose);
            $this->traceTerminals($sortedTerminals, 'Terminals after ' . $sorter, $verbose);
        }

        $this->traceTerminals($sortedTerminals, 'Terminals after sorting', $verbose);

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
        // if binning is enabled, make the binned terminal the top most one
        // add other terminals in case of failing binned terminal
        if ($options and $options->getChance() > 0)
        {
            $terminal = (new Binning)->select($sortedTerminals[0], $options->getChance(), $this->input, $terminals);

            array_unshift($sortedTerminals, $terminal);

            $sortedTerminals = array_unique($sortedTerminals, SORT_REGULAR);

            // array unique removes index. We need to renumber it.
            $sortedTerminals = array_values($sortedTerminals);
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
        if ($this->merchant->getId() === '4izmfM9TFCAgFN')
        {
            $verbose = true;
        }

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
     * @return Entity
     */
    public function selectTerminals()
    {
        $options = new Terminal\Options();

        $terminalsSelected = $this->select($options);

        if ($options->getMultiple() === false)
        {
            // make this into an array, since the caller expects an array
            $terminalsSelected = array($terminalsSelected);
        }

        // restrict international merchants from using terminal rotation to prevent fraud
        if ($this->merchant->isInternational() === true)
        {
            return array($terminalsSelected[0]);
        }

        return $terminalsSelected;

    }
}
