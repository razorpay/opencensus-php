<?php


namespace RZP\Models\Merchant\Acs\AsvClient;


class Constant
{
    // BaseClient
    const TRACE = 'trace';
    const CONFIG = 'config';
    const ACCOUNT_SERVICE = 'applications.acs';
    const HOST = 'host';
    const ASV_HTTP_CLIENT = 'asv_http_client';
    const USER = 'user';
    const PASSWORD = 'password';
    const ASV_HTTP_CLIENT_TIMEOUT = 'asv_http_client_timeout';
    const AUTHORIZATION_KEY = 'Authorization';

    // Sync Account Deviation Request/Response
    const ACCOUNT_ID = 'account_id';
    const MODE = 'mode';
    const MOCK = 'mock';
    const METADATA = 'metadata';
    const TASK_ID = 'task_id';
    const RESPONSE = 'response';

    const X_TASK_ID = 'X-Task-ID';

    // Route Level Timeout
    const SYNC_DEVIATION_ROUTE_HTTP_TIMEOUT_SEC = 'sync_deviation_route_http_timeout_sec';
}
