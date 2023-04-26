<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Models\Admin\ConfigKey;

class AuthSelector extends Base\Core
{
    protected $input;

    protected $options;

    protected $authOrder;

    protected $autflow;

    /** @var Terminal\Auth\Card\AuthFilter $autflowObj */
    protected $autflowObj;

    protected static $filters = [
        Terminal\Filters\Auth\TransactionFilter::class,
        Terminal\Filters\Auth\RuleFilter::class,
    ];

    /**
     * Very important that the sorting order is maintained
     * @var array
     */
    protected static $sorters = [
        Terminal\Sorters\Auth\AuthLoadSorter::class,
    ];

    public function __construct(array $input)
    {
        parent::__construct();

        $this->input = $input;

        $this->options = new Terminal\Options;

        $this->setAuthFilterByMethod();
    }

    protected function setAuthFilterByMethod()
    {
       $payment = $this->input['payment'];

       // only card transactions
       if ($payment->isMethodCardOrEmi() === true)
       {
            $this->autflowObj = new Terminal\Auth\Card\AuthFilter($payment);
       }
    }

    /**
     * @throws \Exception
     */
    public function select()
    {
        $terminals = $this->getTerminals();

        $applicableTerminals = $this->autflowObj->getAuthenticationTerminals($terminals);

        $verbose = $this->isVerboseLogEnabled();

        $this->traceAuthTerminals($applicableTerminals, 'Auth terminals via auth', $verbose);

        $this->input['auths'] = array_pluck($applicableTerminals, 'auth_type');

        // Fetch Authentication gateway filter rules
        $applicableRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchApplicableAuthenticationRulesForPayment($this->input);
        });

        $applicableTerminals = $this->filterTerminals($applicableTerminals, $applicableRules, $verbose);

        $this->input['auths'] = array_pluck($applicableTerminals, 'auth_type');

        $applicableTerminals = $this->sortTerminals($applicableTerminals, $applicableRules, $verbose);

        if (count($applicableTerminals) === 0 )
        {

            throw new \Exception;
        }

        return $applicableTerminals[0];
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

        $filterRules = $this->getRulesForFiltering($rules);

        foreach (self::$filters as $filter)
        {
            $filterObj = new $filter($this->input, $this->options, $filterRules);

            $filteredTerminals = $filterObj->filter($filteredTerminals, $verbose);
        }

        $this->traceAuthTerminals($filteredTerminals, 'Auth terminals after filteration', $verbose);

        return $filteredTerminals;
    }

    // $shouldHitRoutingService is not being used. add to make this func signature compatible with RZP\Models\Terminal\Selector::sortTerminals()
    protected function sortTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = false, bool $shouldHitRoutingService = false): array
    {
        $sortedTerminals = $terminals;

        $sorterRules = $this->getRulesForSorting($rules);

        foreach (self::$sorters as $sorter)
        {
            $sorterObj = new $sorter($this->input, $this->options, $sorterRules);

            $sortedTerminals = $sorterObj->sort($sortedTerminals, $verbose);
        }

        $this->traceAuthTerminals($terminals , 'Auth terminals after sorting', $verbose);

        $this->sortThreeDSTwoTerminalIfApplicable($sortedTerminals);

        return $sortedTerminals;
    }

    protected function traceAuthTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose === true) and (empty($terminals) === false))
        {
            $traceData = [
                'auth_terminals' => $terminals,
                'msg'            => $msg
            ];

            $this->trace->info(TraceCode::AUTH_SELECTION, $traceData);
        }
    }

    protected function getRulesForFiltering(Base\PublicCollection $rules): Base\PublicCollection
    {
        return $rules->filter(function ($rule)
        {
            return ($rule->isFilter() === true);
        });
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

    protected function getTerminals()
    {
        return AuthenticationTerminals::AUTHENTICATION_TERMINALS;
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
        /*
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
        */
    }

    private function sortThreeDSTwoTerminalIfApplicable(& $sortedTerminals)
    {
        if (app()->isEnvironmentQA() === true &&  $this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_3DS2) === true){
            foreach($sortedTerminals as $sortedTerminal){
                if(isset($sortedTerminal["gateway_auth_version"])){
                    $sortedTerminals = array($sortedTerminal);
                    return;
                }
            }
        }
    }
}
