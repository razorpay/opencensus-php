<?php

namespace Gateway\Cybersource\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantID'              => 'required',
        'merchantReferenceCode'   => 'required|alpha_num',
        'clientLibrary'           => 'required',
        'clientLibraryVersion'    => 'required',
        'clientEnvironment'       => 'required',
        'ccAuthService'           => 'required',
        'billTo'                  => 'required',
        'card'                    => 'required',
        'purchaseTotals'          => 'required',
        'item'                    => 'required'
    );

    protected static $enrollRules = array(
        'payerAuthEnrollService'    => 'required',
        'card'                      => 'required',
        'purchaseTotals'            => 'required',
        'item'                      => 'required',
        'merchantID'                => 'required',
        'merchantReferenceCode'     => 'required',
        'clientLibrary'             => 'required',
        'clientLibraryVersion'      => 'required',
        'clientEnvironment'         => 'required'
    );

    protected static $authenticateRules = array(
        'TermUrl'          => 'required|url',
        'MD'               => 'required|alpha_num',
        'PaReq'            => 'required|alpha_num',
    );
}