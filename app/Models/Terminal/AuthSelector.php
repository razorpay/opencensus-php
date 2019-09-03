<?php

namespace RZP\Models\Terminal;

use App;
use Cache;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Terminal\Category;
use RZP\Constants\Entity as Constants;

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

    public function select()
    {
        $terminals = $this->getTerminals();

        $applicableTerminals = $this->autflowObj->getAuthenticationTerminals($terminals);

        $this->traceAuthTerminals($applicableTerminals, 'Auth terminals via auth', true);

        $this->input['auths'] = array_pluck($applicableTerminals, 'auth_type');

        // Fetch Authentication gateway filter rules
        $applicableRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchApplicableAuthenticationRulesForPayment($this->input);
        });

        $applicableTerminals = $this->filterTerminals($applicableTerminals, $applicableRules);

        $this->input['auths'] = array_pluck($applicableTerminals, 'auth_type');

        $applicableTerminals = $this->sortTerminals($applicableTerminals, $applicableRules);

        return $applicableTerminals[0];
    }

    protected function filterTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = true)
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

        $this->traceAuthTerminals($terminals, 'Auth terminals after filteration', $verbose);

        return $filteredTerminals;
    }

    protected function sortTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = true): array
    {
        $sortedTerminals = $terminals;

        $sorterRules = $this->getRulesForSorting($rules);

        foreach (self::$sorters as $sorter)
        {
            $sorterObj = new $sorter($this->input, $this->options, $sorterRules);

            $sortedTerminals = $sorterObj->sort($sortedTerminals, $verbose);
        }

        $this->traceAuthTerminals($terminals , 'Auth terminals after sorting', $verbose);

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
}
