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
            '^sources$',
            '^file_types$',
            '^signed_url$',
            '^file_types\/[[:alnum:]]{14}$',
            '^workflow_config$',
            '^recon_rules$',
            '^recon_rules\/\d+$',
            '^recon_state$',
            '^recon_state\/\d+$',
            '^notification_types$',
            '^file_type_columns$',
            '^extraction_item$',
            '^extraction_config$',
            '^custom_method$',
            '^extraction_item\/extraction_methods$',
            '^extraction_item\/[[:alnum:]]{14}$',
            '^extraction_config\/[[:alnum:]]{14}$',
            '^reports$',
            '^reports\/[[:alnum:]]{14}$',
            '^reports\/[[:alnum:]]{14}\/result',
            '^money_loss_job_config$',
            '^money_loss_job_config\/[[:alnum:]]{14}$',
            '^money_loss_job_runs$',
            '^money_loss_job_runs\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
            '^file_details$',
        ],
        self::POST => [
            '^output_email$',
            '^file_types$',
            '^sample_file_parser$',
            '^recon_rules$',
            '^workflow_config$',
            '^create_notifications$',
            '^extraction_item$',
            '^extraction_config$',
            '^extraction_item\/conflict$',
            '^custom_method$',
            '^recon_state$',
            '^workspaces$',
            '^reports$',
            '^entities$',
            '^reports\/[[:alnum:]]{14}\/retry',
            '^json_logic_validation$',
            '^money_loss_job_config$',
            '^file_types\/[[:alnum:]]{14}\/source_configs$',
        ],
        self::PATCH => [
            '^file_types\/[[:alnum:]]{14}$',
            '^recon_rules$',
            '^recon_rules\/\d+$',
            '^rule_state_map\/\d+$',
            '^recon_state\/\d+$',
            '^extraction_item\/[[:alnum:]]{14}$',
            '^extraction_config\/[[:alnum:]]{14}$',
            '^workspaces\/[[:alnum:]]{14}$',
            '^money_loss_job_config\/[[:alnum:]]{14}$',
            '^money_loss_job_runs\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
        ],
        self::DELETE => [
            '^file_types\/[[:alnum:]]{14}$',
            '^recon_rules$',
            '^recon_rules\/\d+$',
            '^rule_state_map\/\d+$',
            '^recon_state\/\d+$',
            '^extraction_item\/[[:alnum:]]{14}$',
            '^extraction_config\/[[:alnum:]]{14}$',
            '^workspaces\/[[:alnum:]]{14}$',
            '^money_loss_job_config\/[[:alnum:]]{14}$',
            '^money_loss_job_runs\/[[:alnum:]]{14}$',
            '^file_types\/[[:alnum:]]{14}\/source_configs\/[[:alnum:]]{14}$',
        ]
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
