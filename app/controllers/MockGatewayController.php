<?php

class MockGatewayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->mockHdfcGatewayServer = new Gateway\Hdfc\Mock\Server;

        $input = file_get_contents('php://input');

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
        $server = new Gateway\Atom\Mock\Server;

        $input = Input::all();

        $data = $server->atomPaymentChooseOrg($input);

        return View::make('gateway.atomBankSubmit')
                   ->with('data', $data);
    }

    public function postAtomInitPayment()
    {
        $server = new Gateway\Atom\Mock\Server;

        $input = Input::all();

        return $server->initiateAtomPayment($input);
    }

    public function postAtomRzpPayment()
    {
        $server = new Gateway\Atom\Mock\Server;

        $input = Input::all();

        $data = $server->atomRzpPayment($input);

        return View::make('gateway.atomRzpBankPage')
                   ->with('data', $data);
    }

    public function postAtomRzpPaymentSubmit()
    {
        $input = Input::all();

        $server = new Gateway\Atom\Mock\Server;

        list($url, $data) = $server->atomRzpPaymentPageSubmit($input);

        return View::make('gateway.atomMerchantCallback')
                   ->with('url', $url)
                   ->with('data', $data);
    }

    public function postAxisPayment()
    {
        $input = Input::all();

        $server = new Gateway\AxisMigs\Mock\Server;

        $url = $server->authorize($input);

        return Redirect::to($url);
    }
}