<?php

namespace RZP\Http\Controllers;


use App;
use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Services\ReconService;
use RZP\Exception\BadRequestException;

class ReconServiceController extends Controller
{
    protected $recon;

    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const WHITELISTED_ROUTES_REGEX = [
        self::GET => [
            '^recon_runs$',
            '^ingestion_runs$',
            '^recon_runs\/\d+$',
            '^ingestion_runs\/\d+$',
            '^ingestion_run_logs$',
            '^ingestion_run_logs\/\d+$',
            '^journal_voucher$',
            '^workspaces$',
            '^merchants$',
            '^file_types$',
            '^signed_url$',
        ],
    ];

    const WHITELISTED_ADMIN_ROUTES_REGEX = [
        self::GET => [
            '^ping$',
            '^status$',
            '^merchants$',
            '^merchants\/[[:alnum:]]{14}$',
            '^workspaces$',
            '^workspaces\/[[:alnum:]]{14}$',
            '^file_types$',
            '^file_types\/[[:alnum:]]{14}$',
            '^recon_runs\/get_metadata\/[[:alnum:]]{14}$',
            '^recon_summary$',
            '^sources$',
            '^sources\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
            '^sources\/[[:alnum:]]{14}\/file_types$',
            '^rules$',
            '^rules\/\d+$',
            '^recon_state$',
            '^recon_state\/\d+$',
            '^rule_state_map$',
            '^rule_state_map\/\d+$',
            '^recon_runs$',
            '^ingestion_runs$',
            '^recon_runs\/\d+$',
            '^ingestion_runs\/\d+$',
            '^journal_voucher$',
            '^signed_url$',
            '^file\/[[:alnum:]]{14}$',
            '^workflow_config$',
            '^workflow_config\/[[:alnum:]]{14}$',
            '^workflow_file_detail$',
            '^workflow_file_detail\/[[:alnum:]]{14}$',
        ],
        self::POST => [
            '^merchants$',
            '^workspaces$',
            '^cron_jobs$',
            '^bulk_rule$',
            '^file_types$',
            '^recon_runs\/trigger\/[[:alnum:]]{14}$',
            '^sources$',
            '^file_types\/[[:alnum:]]{14}\/source_configs$',
            '^sources\/[[:alnum:]]{14}\/file_types$',
            '^rules$',
            '^recon_state$',
            '^rule_state_map$',
            '^notifications$',
            '^notification_types$',
            '^notification_channels$',
            '^default_notification_channels$',
            '^recon_rules_download$',
            '^file$',
            '^file\/[[:alnum:]]{14}$',
            '^send_notification$',
            '^output_email$',
            '^workflow_file$',
            '^ingestion_runs$',
            '^recon_runs$',
            '^workflow_config$',
        ],
        self::PUT => [
            '^notifications$',
        ],
        self::PATCH => [
            '^merchants\/[[:alnum:]]{14}$',
            '^workspaces\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
            '^recon_state\/\d+$',
            '^rule_state_map\/\d+$',
            '^recon_runs\/\d+$',
            '^ingestion_runs\/\d+$',
            '^recon_run_logs\/\d+$',
            '^ingestion_run_logs\/\d+$'
        ],
        self::DELETE => [
            '^merchants\/[[:alnum:]]{14}$',
            '^workspaces\/[[:alnum:]]{14}$',
            '^cron_jobs\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}$',
            '^sources\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
            '^rules\/\d+$',
            '^recon_state\/\d+$',
            '^rule_state_map\/\d+$',
            '^bulk_delete$',
            '^recon_runs\/\d+$',
            '^ingestion_runs\/\d+$',
            '^file\/[[:alnum:]]{14}$',
            '^workflow_config\/[[:alnum:]]{14}$',
        ],
    ];

    public function handleAny($path = '')
    {
        $method = Request::method();

        if(array_key_exists($method, self::WHITELISTED_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedRoutesRegex = implode('|', self::WHITELISTED_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $data = Request::all();

        $response = $this->reconService()->sendAnyRequest($path, $method, $data);

        return ApiResponse::json($response);
    }

    public function handleAdminCall($path = '')
    {
        $input = Request::all();

        $method = $input['method'];

        if(array_key_exists($method, self::WHITELISTED_ADMIN_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedAdminRoutesRegex = implode('|', self::WHITELISTED_ADMIN_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedAdminRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $response = $this->reconService()->sendAnyRequest($path, $method, $input);

        return ApiResponse::json($response);
    }

    public function uploadFile()
    {
        $input = Request::all();

        $response = $this->reconService()->uploadFile($input);

        return ApiResponse::json($response);
    }

    public function workflowFileUpload()
    {
        $input = Request::all();

        $response = $this->reconService()->workflowFileUpload($input);

        return ApiResponse::json($response);
    }

    protected function reconService()
    {
        $app = App::getFacadeRoot();

        return new ReconService($app);
    }
}
