<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Feature\Constants as Feature;

/**
 * Documentation here :
 * https://docs.google.com/document/d/1bsx1t21Q_n5cQBnM_REyolGbn92Fzscu0LrRYiKqqsU/
 */
class GatewayDowntimeSorter extends Terminal\Sorter
{
    protected $properties = [
        'downtime',
    ];

    /**
     * Lists down the methods for which `downtimeSorter` is applicable
     */
    protected $allowedMethods = [
        Payment\Method::CARD,
        Payment\Method::EMI,
        Payment\Method::UPI,
    ];

    /**
     * Sorts the terminals wrt the downtimes.
     *
     * Presently, we only sort terminals for card & emi payments
     *
     * The terminals whose gateways are down,
     * will be pushed to the bottom of the list.
     * Scenarios & cases are mentioned in the spec.
     *
     * There is no weightage given to the priority in which the
     * terminals are moved to the end of the list.
     *
     * @param $terminals array of Terminal\Entity
     * @param $input array
     * @return $sortedTerminals array of Terminal\Entity
     */
    public function downtimeSorter(array $terminals): array
    {
        if (in_array($this->input['payment']->getMethod(), $this->allowedMethods, true) === false)
        {
            return $terminals;
        }

        if ($this->input['payment']->getApplication() === Payment\Gateway::GOOGLE_PAY)
        {
            return $terminals;
        }

        try
        {
            // @note: Temporarily setting verbose to true here
            // for logging of terminals of downtime sorter
            $verbose = true;

            $downtimes = $this->repo->useSlave(function () use ($terminals)
            {
                $downtimes = (new Downtime\Core)->getApplicableDowntimesForPayment($terminals, $this->input);

                $downtimes = $this->filterDowntimesByFeature($downtimes);

                return $downtimes;
            });

            if ($downtimes->isEmpty() === true)
            {
                return $terminals;
            }

            $sortedTerminals = $this->sortTerminals($terminals, $downtimes);

            if ($verbose === true)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_DOWNTIME_SORTING,
                    [
                        'downtimes'        => $downtimes->pluck(Downtime\Entity::ID)->toArray(),
                        'sorted_terminals' => array_pluck($sortedTerminals, 'id'),
                    ]);
            }

            return $sortedTerminals;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return $terminals;
        }
    }

    /**
     * Downtimes fetched will include those created internally, i.e. by gateway
     * exception throttling. We don't want these to be used for all merchants,
     * only the ones who have a feature enabled.
     */
    protected function filterDowntimesByFeature(Base\PublicCollection $downtimes): Base\PublicCollection
    {
        $merchant = $this->input['merchant'];

        if ($merchant->isFeatureEnabled(Feature::DOWNTIME_ROUTING) === true)
        {
            return $downtimes;
        }

        $downtimes = $downtimes->filter(function ($downtime) {
            return ($downtime->getSource() !== Downtime\Source::INTERNAL);
        });

        return $downtimes;
    }

    /**
     * Performs sorting on relevant downtimes & given terminals
     *
     * Separates terminals into demotedTerminals/nonDemotedTerminals
     * Returns after merging demoted with nonDemoted terminals
     *
     * @param $terminals array
     * @param $downtimes array
     * @param array (sorted array of terminals)
     */
    protected function sortTerminals(array $terminals, $downtimes): array
    {
        $demotedTerminals = [];

        $nonDemotedTerminals = [];

        foreach ($terminals as $terminal)
        {
            $shouldDemote = $this->shouldDemoteTerminal($terminal, $downtimes);

            if ($shouldDemote === true)
            {
                $demotedTerminals[] = $terminal;
            }
            else
            {
                $nonDemotedTerminals[] = $terminal;
            }
        }

        return array_merge($nonDemotedTerminals, $demotedTerminals);
    }

    /**
     * Checks if priority of terminal should be demoted
     * against the list of downtimes
     *
     * @param $terminal Terminal\Entity
     * @param $downtimes Base\PublicCollection
     * @return bool
     */
    protected function shouldDemoteTerminal(Terminal\Entity $terminal, Base\PublicCollection $downtimes): bool
    {
        foreach ($downtimes as $downtime)
        {
            if ($this->isDowntimeApplicableOnTerminal($terminal, $downtime) === true)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Compares on basis on terminal related data
     * 1. Terminal Id
     * 2. Terminal Gateway
     * 3. Downtime Gateway
     *
     * @param $terminal Terminal\Entity
     * @param $downtimes Downtime\Entity
     * @return bool
     */
    protected function isDowntimeApplicableOnTerminal(Terminal\Entity $terminal, Downtime\Entity $downtime): bool
    {
        if (is_null($downtime->getTerminalId()) === false)
        {
            return $terminal->getId() === $downtime->getTerminalId();
        }

        return ((($downtime->getGateway() === Downtime\Entity::ALL) or
                 ($terminal->getGateway() === $downtime->getGateway())) and
                (($downtime->getAcquirer() === Downtime\Entity::ALL) or
                 ($downtime->getAcquirer() === Downtime\Entity::UNKNOWN) or
                 ($terminal->getGatewayAcquirer() === $downtime->getAcquirer())));
    }
}
