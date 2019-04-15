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

        $applicableTerminals = $this->selectValidAuthViaRules($applicableTerminals, $applicableRules);

        $this->input['auths'] = array_pluck($applicableTerminals, 'auth_type');

        $applicableTerminals = $this->sortAuthTerminals($applicableTerminals, $applicableRules);

        return $applicableTerminals[0];
    }

    protected function selectValidAuthViaRules(array $terminals, Base\PublicCollection $rules, bool $verbose = true)
    {
        $filterRules = $this->getRulesForFiltering($rules);

        $terminalAuthRuleFilter = new Filters\Auth\RuleFilter($this->input, $this->options, $filterRules);

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
