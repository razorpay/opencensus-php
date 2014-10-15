<?php

class MockHdfcController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->mockHdfcGatewayServer = new Gateway\MockHdfc\Server;

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
        return $this->mockHdfcGatewayServer->gatewayPayment();
    }

    public function authEnrolled()
    {
        return $this->mockHdfcGatewayServer->authEnrolled();
    }
}