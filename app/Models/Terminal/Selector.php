<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Constants\Mode;
use RZP\Models\Card\Network;
use RZP\Models\Payment;

use RZP\Trace;
use RZP\Exception;
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
        Filters\MiscFilter::class,
    ];

    /**
     * Very important that the sorting order is maintained
     * @var array
     */
    protected static $sorters = [
        Sorters\CardSorter::class,
        Sorters\NetbankingSorter::class,
        Sorters\MerchantSorter::class,
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

    public function select($options = [], $verbose = false)
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

        if(isset($options['exclude']) and is_array($options['exclude']))
        {
            $this->input['exclude'] = $options['exclude'];
        }

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
        else
        {
            $terminal = $sortedTerminals[0];
        }

        if (isset($options['chance']))
        {
            $terminal = (new Binning)->select($terminal, $options['chance'], $this->input, $terminals);

            $sortedTerminals = array($terminal);
        }

        $this->setTerminalForPayment($this->payment, $terminal);

        // hack to return multiple terminals if needed.
        if(isset($options['multiple']) and ($options['multiple'] === true))
        {
            return $sortedTerminals;
        }

        return $terminal;
    }

    public function setTerminalForPayment($payment, $terminal = null)
    {
        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $payment->toArrayAdmin()]);
        }

        $payment->terminal()->associate($terminal);

        $payment->setGateway($terminal->getGateway());
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

    protected function getOptionsForTerminals()
    {
        $options = [];

        if (($this->mode === Mode::LIVE) and
            (App::environment('testing') === false))
        {
            $chance = rand(1,100);

            $options['chance'] = $chance;

        }

        $options['multiple'] = true;

        return $options;
    }



    /**
     * Methods selects a list of terminals for payment. We are
     * selecting a list here since, we want to iterate through
     * a bunch of terminals, in case the terminal fails
     * @return Entity
     */
    public function selectTerminalsForPayment()
    {
        $options = $this->getOptionsForTerminals();

        $terminalsSelected = $this->select($options);

        if(!isset($options['multiple']))
        {
            // make this into an array, since the caller expects an array
            $terminalsSelected = array($terminalsSelected);
        }

        return $terminalsSelected;

    }
}