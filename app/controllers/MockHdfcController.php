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

    public function enroll()
    {
        return $this->mockHdfcGatewayServer->enroll();
    }

    public function transaction()
    {
        return $this->mockHdfcGatewayServer->gatewayTransaction();
    }

    public function authEnrolled()
    {
        return $this->mockHdfcGatewayServer->authEnrolled();
    }
}