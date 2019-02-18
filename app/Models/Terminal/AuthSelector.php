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

    protected $autflowObj;

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

    public function selectAuth()
    {
        $terminals = $this->autflowObj->getAuthenticationTerminals();

        $this->traceAuthTerminals($terminals, 'Auth terminals via auth', true);

        $this->input['auths'] = array_pluck($terminals, 'auth_type');

        // Fetch Authentication gateway filter rules
        $applicableFilterRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchAuthenticationRules($this->input);
        });

        $terminals = $this->selectValidAuthViaRules($terminals, $applicableFilterRules);

        $this->input['auths'] = array_pluck($terminals, 'auth_type');

        $applicableSorterRules = $this->repo->useSlave(function ()
        {
            return (new Rule\Core)->fetchAuthenticationRules($this->input, Rule\Entity::SORTER);
        });

        if (empty($applicableSorterRules) === true)
        {
            return $terminals[0];
        }

        $terminals = $this->sortAuthTerminals($terminals, $applicableSorterRules);

        return $terminals[0];
    }

    protected function selectValidAuthViaRules(array $terminals, Base\PublicCollection $rules, bool $verbose = true)
    {
        $terminalAuthRuleFilter = new Filters\Auth\RuleFilter($this->input, $this->options, $rules);

        $terminals = $terminalAuthRuleFilter->filter($terminals, $verbose);

        $this->traceAuthTerminals($terminals, 'Auth terminals after auth rule fiter', $verbose);

        return $terminals;
    }

    protected function sortAuthTerminals(array $terminals, Base\PublicCollection $rules, bool $verbose = true): array
    {
        $rules = $this->getRulesForSorting($rules);

        $sorterObj = new Sorters\Auth\AuthLoadSorter($this->input, $this->options, $rules);

        $terminals = $sorterObj->sort($terminals, $verbose);

        $this->traceAuthTerminals($terminals , 'Auth terminals after sorting', true);

        return $terminals;
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

    protected function getRulesForSorting(Base\PublicCollection $rules): array
    {
        $sorterRules = $rules->filter(function ($rule)
        {
            return ($rule->isSorter() === true);
        });

        $sorterRules = $sorterRules->groupAuthRuleBySpecificityScore();

        return $sorterRules;
    }
}
