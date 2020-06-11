<?php
namespace App\Http\Controllers;

use Request;
use Response;

use App\Admin\GraphRequestAny;

class GraphController extends Controller
{
    public function handleRequestForGraph()
    {
        $data = Request::all();

        $request = new GraphRequestAny($data);

        list($response, $headersIncoming) = $request->send();

        return Response::json($response, 200, $headersIncoming);
    }
}
