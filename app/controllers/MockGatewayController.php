<?php

class MockGatewayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $input = file_get_contents('php://input');

        $app = \App::getFacadeRoot();
        $this->gateway = $app['gateway'];

        $this->mockHdfcGatewayServer = $this->gateway->server('hdfc');
        $this->mockHdfcGatewayServer->setInput($input);
    }

    public function post3dSecure()
    {
        $input = Input::all();

        $data = $this->mockHdfcGatewayServer->threeDSecure($input);

        return View::make('gateway.3dsecure')->with('data', $data);
    }

    public function enroll()
    {
        return $this->mockHdfcGatewayServer->enroll();
    }

    public function payment()
    {
        return $this->mockHdfcGatewayServer->gatewayTransaction();
    }

    public function authEnrolled()
    {
        return $this->mockHdfcGatewayServer->authEnrolled();
    }

    public function getAtomChooseOrg()
    {
        $server = $this->gateway->server('atom');

        $input = Input::all();

        $data = $server->atomPaymentChooseOrg($input);

        return View::make('gateway.atomBankSubmit')
                   ->with('data', $data);
    }

    public function postAtomInitPayment()
    {
        $server = $this->gateway->server('atom');

        $input = Input::all();

        return $server->authorize($input);
    }

    public function postAtomRzpPayment()
    {
        $server = $this->gateway->server('atom');

        $input = Input::all();

        $data = $server->atomRzpPayment($input);

        return View::make('gateway.atomRzpBankPage')
                   ->with('data', $data);
    }

    public function postAtomRzpPaymentSubmit()
    {
        $server = $this->gateway->server('atom');

        $input = Input::all();

        list($url, $data) = $server->atomRzpPaymentPageSubmit($input);

        return View::make('gateway.atomMerchantCallback')
                   ->with('url', $url)
                   ->with('data', $data);
    }

    public function postAxisPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('axis_migs');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postAxisGeniusPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('axis_genius');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function getKotakPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('kotak');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postPaytmPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('paytm');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postBilldeskPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('billdesk');

        return $server->authorize($input);
    }

    public function getSharpPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('sharp');

        list($data, $error) = $server->action($input);

        if ($error !== null)
        {
            return ApiResponse::json($error);
        }

        if ($data['action'] === 'authorize')
        {
            return View::make('gateway.sharpBankPage')
                       ->with($data);
        }
    }

    public function postSharpPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('sharp');

        $url = $server->authSubmit($input);

        return Redirect::to($url);
    }

    public function postNetbankingPayment($bank)
    {
        $input = Input::all();

        $driver = 'netbanking_'.$bank;
        $server = $this->gateway->server($driver);

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postMobikwikPayment()
    {
        $input = Input::all();

        $server = $this->gateway->server('mobikwik');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }
}