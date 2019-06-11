<?php
namespace App\Http\Controllers;

use App;
use Auth;
use Input;
use Config;
use Request;

use App\Generic;
use App\Http\AppResponse;

class GenericController extends Controller
{
    const WHITELISTED_HEADERS = [
        'x-consumer',
        'x-report-type',
        'x-cross-org-id',
        'x-org-id',
    ];

    const WHITELISTED_ROUTES_REGEX = [
        '^invoices$',
        '^currency\/all\/proxy$',
        'invoices\/inv_[[:alnum:]]{14}\/notify_by\/(?:email|sms)$',
        '^invoices\/inv_[[:alnum:]]{14}\/cancel$'
    ];

    public function handleAny($mode, $path)
    {
        $allRequestHeaders = Request::header();
        $headers = [];

        foreach($allRequestHeaders as $key => $value) {
            $key = strtolower($key);

            if (in_array($key, self::WHITELISTED_HEADERS, true) === true) {
                $key = title_case($key);

                $headers[$key] = $value[0];
            }
        }

        $request = new App\Admin\ApiRequestAny([
            'mode'      => $mode,
            'headers'   => $headers,
        ]);

        $method = Request::method();

        list($error, $data) = $request->send($path, $method);

        return AppResponse::jsonResponse($error, $data);
    }

    public function handleAnyExtension($mode, $path)
    {
        $whiteListedRoutesRegex = implode('|', self::WHITELISTED_ROUTES_REGEX);

        if (preg_match($whiteListedRoutesRegex, $path, $pathMatches) === true)
        {
            $request = new App\Admin\ApiRequestAny([
                'mode'      => $mode,
                'headers'   => [
                    'X-Chrome-Extension'=> true,
                ],
            ]);

            $method = Request::method();

            list($error, $data) = $request->send($path, $method);

            return AppResponse::jsonResponse($error, $data);
        }

        return AppResponse::unauthorizedResponse('Unauthorized.', Request::route()->getName(), $path);
    }
}
