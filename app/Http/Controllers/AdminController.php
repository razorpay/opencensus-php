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

    public function getSlackTestQueue()
    {
        $input = Request::all();

        $app = App::getFacadeRoot();

        // post via direct send
        $rand = rand();

        $rand = rand();

        $headline = "Successful Send[queue] ->".$rand;

        $message = array("Test Details[queue]->".$rand => "Test Message->".$rand);

        // message should appear successfully
        $app['slack']->queue($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        $endpoint = $app['slack']->getEndPoint();

        $app['slack']->setEndPoint("http://www.google.com");

        $headline = "Failing Endpoint Message[queue] ->".$rand;

        // this should fail with bad endpoint
        $app['slack']->queue($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        sleep(10);

        // message should start appearing now
        $app['slack']->setEndPoint($endpoint);

        $data = array('status' => 'posted to slack[queue]');

        return ApiResponse::json($data);
    }

    public function getSlackTestSend()
    {
        $input = Request::all();

        $app = App::getFacadeRoot();

        // post via direct send
        $rand = rand();

        $headline = "Successful Message[send] ->".$rand;

        $message = array("Test Details[Send]->".$rand => "Test Message->".$rand);

        $app['slack']->send($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        $endpoint = $app['slack']->getEndPoint();

        $app['slack']->setEndPoint("http://www.google.com");

        $headline = "Failing Endpoint Message[send] ->".$rand;

        // this should get queued, as this fails and should get automatically
        // released for queue
        $app['slack']->send($headline, $message, ['channel' => '#dev-test-2',
            'username' => 'Jordan Belfort',
            'icon' => ':boom:']);

        sleep(10);

        // message should start appearing after this
        $app['slack']->setEndPoint($endpoint);

        $data = array('status' => 'posted to slack[send]');

        return ApiResponse::json($data);
    }
}
