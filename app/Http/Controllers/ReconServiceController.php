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
            '^recon_run_logs$',
            '^ingestion_run_logs$',
            '^recon_run_logs\/\d+$',
            '^ingestion_run_logs\/\d+$',
            '^journal_voucher$',
            '^workspaces$',
            '^merchants$',
            '^file_types$',
            '^signed_url$',
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

    public function uploadFile()
    {
        $input = Request::all();

        $response = $this->reconService()->uploadFile($input);

        return ApiResponse::json($response);
    }

    protected function reconService()
    {
        $app = App::getFacadeRoot();

        return new ReconService($app);
    }
}
