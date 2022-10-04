<?php

namespace RZP\Services\Partnerships;


use RZP\Base;

class Validator extends Base\Validator
{
    const CREATE_RULE                  = 'create_rule';
    const CREATE_RULE_GROUP            = 'create_rule_group';
    const CREATE_RULE_CONFIG_MAPPING   = 'create_rule_config_mapping';
    const UPDATE_RULE                  = 'update_rule';
    const UPDATE_RULE_CONFIG_MAPPING   = 'update_rule_config_mapping';
    const GET                          = 'get';
    const CREATE_AUDIT_LOG             = 'create_audit_log';


    protected static $createRuleRules = [
        'name'                => 'required|string',
        'rule_variable_id'    => 'required|string',
        'rule_operator'       => 'required|string',
        'rule_group_id'       => 'required|string',
        'max_value'           => 'filled|integer|min:1|max:100',
        'min_value'           => 'filled|integer|min:1|max:100',
    ];

    protected static $updateRuleRules = [
        'id'                  => 'required|string',
        'name'                => 'sometimes|string',
        'rule_variable_id'    => 'sometimes|string',
        'rule_operator'       => 'sometimes|string',
        'rule_group_id'       => 'sometimes|string',
        'max_value'           => 'filled|integer|min:1|max:100',
        'min_value'           => 'filled|integer|min:1|max:100',
    ];

    protected static $createRuleGroupRules = [
        'name' => 'required|string'
    ];

    protected static $createRuleConfigMappingRules = [
        'name'                => 'required|string',
        'partner_id'          => 'required|string',
        'rule_group_id'       => 'required|string',
    ];

    protected static $updateRuleConfigMappingRules = [
        'id'                  => 'required|string',
        'name'                => 'required|string',
        'partner_id'          => 'required|string',
        'rule_group_id'       => 'required|string',
    ];

    protected static $getRules = [
        'id' => 'required|string'
    ];

    protected static $createAuditLogRules = [
        'audit_log' => 'required'
    ];
}
