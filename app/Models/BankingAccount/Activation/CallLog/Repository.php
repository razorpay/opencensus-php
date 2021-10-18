<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;


use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\Base;
use Illuminate\Database\Query\JoinClause;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_call_log';

}
