<?php
namespace App\Http\Controllers;

use Request;
use Response;
use App\Graph;

class GraphController extends Controller
{
    public function handleRequestForGraph()
    {
        $input = Request::all();

        list($response, $headersIncoming) = (new Graph\Service)->handleGraphqlRequest($input);

        return Response::json($response, 200, $headersIncoming);
    }
}
