<?php

namespace App\Merchant;

use App\Base;

class Validator extends Base\Validator
{
    protected static $terminalRules = array(
        'mode'                                      => 'required|in:test,live',
        'gateway'                                   => '',
        'gateway_merchant_id'                       => '',
        'gateway_merchant_id2'                      => '',
        'gateway_terminal_id'                       => '',
        'gateway_terminal_password'                 => 'confirmed',
        'gateway_terminal_password_confirmation'    => '',
        'gateway_access_code'                       => '',
        'gateway_secure_secret'                     => '',
        'gateway_client_certificate'                => 'sometimes|file|mimetypes:application/octet-stream',
        'card'                                      => 'required',
        'emi'                                       => '',
        'emi_duration'                              => '',
        'shared'                                    => '',
        'international'                             => '',
        'category'                                  => '',
        'gateway_acquirer'                          => 'sometimes|string',
    );
}
