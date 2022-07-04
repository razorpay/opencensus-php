<?php

namespace RZP\Models\PaymentLink\CustomDomain;

class Status
{
    const CREATED = "created";
    const PROXY_CONFIG_SUCCESS = "proxy_config_success";
    const PROXY_CONFIG_FAILED = "proxy_config_failed";
    const DELETED = "deleted";
    const PROXY_CONFIG_DELETE_SUCCESS = "proxy_config_delete_success";
    const PROXY_CONFIG_DELETE_FAILED = "proxy_config_delete_failed";

    public static $statuses = [
        self::CREATED,
        self::PROXY_CONFIG_SUCCESS,
        self::PROXY_CONFIG_FAILED,
        self::DELETED,
        self::PROXY_CONFIG_DELETE_SUCCESS,
        self::PROXY_CONFIG_DELETE_FAILED,
    ];
}
