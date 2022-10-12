<?php


namespace RZP\Models\CyberCrimeHelpDesk;

use RZP\Base\Validator as BaseValidator;


class Validator extends BaseValidator
{
    protected static $sendMailToLEAFromCyberCrimeHelpdeskRules = [
        'requester_mail'    => 'required|email|max:255',
        'payment_requests'  => 'required|array',
        'files'             => 'sometimes|array'
    ];

    protected static $cyberCrimeHelpdeskWorkflowActionCreateRules = [
        'requester_mail'                =>  'required|email|max:255',
        'ticket_data'                   =>  'required|array|size:3',
        'ticket_data.ticket'            =>  'required|array|min:1',
        'ticket_data.file_names'        =>  'sometimes|array',
        'ticket_data.fd_ticket_id'      =>  'required|string|max:255',
    ];

}
