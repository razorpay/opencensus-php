<?php

namespace RZP\Services\FTS;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Http\Throttle\Constant as K;

class Validator extends Base\Validator
{
    protected static $createSourceAccountRules = [
      'id'                          => 'sometimes|alpha_num|size:14',
      'type'                        => 'filled|string|required|in:bank_account,banking_account',
      'config'                      => 'required|array',
      'product'                     => 'filled|string|required|in:payout,refund,payout_refund,settlement,penny_testing,ca_payout,es_on_demand',
      'channel'                     => 'filled|string|required|in:yesbank,icici,citi,m2p,axis,rbl,amazon_pay',
      'credentials'                 => 'required|array',
      'fund_account_id'             => 'sometimes|integer',
      'mozartIdentifier'            => 'filled|string|required|in:V1,V2,V3,V4',
      'sourceAccountType'           => 'filled|string|required|in:current,nodal',
      'sourceAccountTypeIdentifier' => 'sometimes|string|in:pool,direct',
    ];

    protected static $deleteSourceAccountRules = [
      'source_account_id' => 'filled|required|integer'
    ];

    protected static $fetchTransferStatusRules = [
      'source_ids'   => 'required|array|min:1,max:100',
      'source_ids.*' => 'required|string|filled|size:14'
    ];

    protected static $fetchModeRules = [
        'selected_mode'   => 'required|string|in:IMPS,NEFT',
    ];
}
