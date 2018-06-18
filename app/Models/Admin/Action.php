<?php

namespace RZP\Models\Admin;

use RZP\Models\Admin\AuditActionCode as AC;

//TODO: Add action for merchant methods.
//TODO: Add action for feature?
class Action
{

    const LOGIN = [
        'category'  => AC::CATEGORY_AUTH,
        'action'    => AC::ACTION_LOGIN
    ];

    const LOGIN_FAIL = [
        'category'  => AC::CATEGORY_AUTH,
        'action'    => AC::ACTION_LOGIN_FAIL
    ];

    const LOGIN_OAUTH = [
        'category'  => AC::CATEGORY_AUTH,
        'action'    => AC::ACTION_LOGIN_OAUTH
    ];

    const LOGIN_FAIL_OAUTH = [
        'category'  => AC::CATEGORY_AUTH,
        'action'    => AC::ACTION_LOGIN_FAIL_OAUTH
    ];

    const GENERATE_LOGIN_TOKEN = [
        'category'  => AC::CATEGORY_AUTH,
        'action'    => AC::ACTION_GENERATE_LOGIN_TOKEN
    ];

    const CREATE_ADMIN = [
        'category'  => AC::CATEGORY_UAM,
        'label'     => AC::LABEL_ADMIN,
        'action'    => AC::ACTION_CREATE,
    ];

    const EDIT_ADMIN = [
        'category'  => AC::CATEGORY_UAM,
        'label'     => AC::LABEL_ADMIN,
        'action'    => AC::ACTION_EDIT,
    ];

    const RESET_PASSWORD = [
        'category' => AC::CATEGORY_UAM,
        'label'  => AC::LABEL_ADMIN,
        'action' => AC::ACTION_RESET_PASSWORD,
    ];

    const RESET_PASSWORD_FAILED = [
        'category' => AC::CATEGORY_UAM,
        'label'  => AC::LABEL_ADMIN,
        'action' => AC::ACTION_RESET_PASSWORD_FAILED,
    ];

    const RESET_PASSWORD_INVALID_AUTH_TYPE = [
        'category' => AC::CATEGORY_UAM,
        'label'  => AC::LABEL_ADMIN,
        'action' => AC::ACTION_RESET_PASSWORD_INVALID_AUTH_TYPE,
    ];

    const RESET_PASSWORD_INVALID_OLD_PASSWORD = [
        'category' => AC::CATEGORY_UAM,
        'label'  => AC::LABEL_ADMIN,
        'action' => AC::ACTION_RESET_PASSWORD_INVALID_OLD_PASSWORD,
    ];

    const DELETE_ADMIN = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_ADMIN,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_GROUP = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_GROUP,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_GROUP = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_GROUP,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_GROUP = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_GROUP,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_DISPUTE = [
        'category'  => AC::CATEGORY_DISPUTE,
        'label'     => AC::LABEL_DISPUTE,
        'action'    => AC::ACTION_CREATE,
    ];

    const EDIT_DISPUTE  = [
        'category'  => AC::CATEGORY_DISPUTE,
        'label'     => AC::LABEL_DISPUTE,
        'action'    => AC::ACTION_EDIT,
    ];

    const CREATE_AUTH_POLICY = [
        'category' => AC::CATEGORY_AUTH,
        'label' => AC::CATEGORY_AUTH_POLICY,
        'action' => AC::ACTION_CREATE
    ];

    const CREATE_ORG_HOSTNAME = [
        'category' => AC::CATEGORY_HOSTNAME,
        'label' => AC::LABEL_HOSTNAME,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_ORG_HOSTNAME = [
        'category' => AC::CATEGORY_HOSTNAME,
        'label' => AC::LABEL_HOSTNAME,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_ORG_HOSTNAME = [
        'category' => AC::CATEGORY_HOSTNAME,
        'label' => AC::LABEL_HOSTNAME,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_ORG = [
        'category' => AC::CATEGORY_ORG,
        'label' => AC::LABEL_ORG,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_ORG = [
        'category' => AC::CATEGORY_ORG,
        'label' => AC::LABEL_ORG,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_ORG = [
        'category' => AC::CATEGORY_ORG,
        'label' => AC::LABEL_ORG,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_PERMISSION = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_PERMISSION,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_PERMISSION = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_PERMISSION,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_PERMISSION = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_PERMISSION,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_ROLE = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_ADMIN_ROLES,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_ROLE = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_ADMIN_ROLES,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_ROLE = [
        'category' => AC::CATEGORY_UAM,
        'label' => AC::LABEL_ADMIN_ROLES,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_MERCHANT = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_MERCHANT = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_MERCHANT = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_SUBMERCHANT = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_SUBMERCHANT,
        'action' => AC::ACTION_CREATE
    ];

    //TODO: this action needs to be implemented
    const EDIT_MERCHANT_BALANCE = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT_BALANCE,
        'action' => AC::ACTION_EDIT
    ];

    const CREATE_MERCHANT_CREDITS = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT_CREDITS,
        'action' => AC::ACTION_CREATE
    ];

    const EDIT_MERCHANT_CREDITS = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT_CREDITS,
        'action' => AC::ACTION_EDIT
    ];

    const DELETE_MERCHANT_CREDITS = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT_CREDITS,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_MERCHANT_PRICING_PLAN = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_MERCHANT_PRICING_PLAN,
        'action' => AC::ACTION_CREATE
    ];

    const CREATE_PRICING_PLAN_RULE = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_PRICING_PLAN_RULE,
        'action' => AC::ACTION_CREATE
    ];

    const DELETE_PRICING_PLAN_RULE = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_PRICING_PLAN_RULE,
        'action' => AC::ACTION_DELETE
    ];

    const CREATE_UPDATE_PRICING_PLAN_RULE = [
        'category' => AC::CATEGORY_MERCHANT,
        'label' => AC::LABEL_PRICING_PLAN_RULE,
        'action' => AC::ACTION_EDIT
    ];
}
