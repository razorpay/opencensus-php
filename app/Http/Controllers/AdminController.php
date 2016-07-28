<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Admin;
use Request;
use Redirect;
use App;

class AdminController extends Controller
{
    public function getEntityMultiple($type)
    {
        $input = Request::all();

        $data = (new Admin\Service)->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getEntityById($type, $id)
    {
        $data = (new Admin\Service)->fetchEntityById($type, $id);

        return ApiResponse::json($data);
    }

    public function postSendTestNewsletter()
    {
        $input = Request::all();

        $data = (new Admin\Service)->sendTestNewsletter($input);

        return ApiResponse::json($data);
    }

    public function postSendNewsletter()
    {
        $input = Request::all();

        $data = (new Admin\Service)->sendNewsletter($input);

        return ApiResponse::json($data);
    }

    public function getTransparentRedirect()
    {
        $input = Request::all();

        if (isset($input['url']))
        {
            $url = $input['url'];
            unset($input['url']);

            $query = http_build_query($input);
            $url .= '?'.$query;

            return Redirect::to($url);
        }
    }

    public function postTransparentRedirect()
    {
        $input = Request::all();
    }

    public function getSlackTest()
    {
        $input = Request::all();

        $app = App::getFacadeRoot();

        // post via direct send
        $rand = rand();

        $headline = "Test Headline[send] ->".$rand;

        $message = array("Test Details[Send]->".$rand => "Test Message->".$rand);

        $app['slack']->send($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        // post via queue

        $rand = rand();

        $headline = "Test Headline[queue] ->".$rand;

        $message = array("Test Details[queue]->".$rand => "Test Message->".$rand);

        $app['slack']->queue($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        $data = array('status' => 'posted to slack');

        return ApiResponse::json($data);
    }
}
