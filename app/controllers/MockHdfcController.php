<?php

class MockHdfcController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->mockHdfcGateway = new Gateway\MockHdfc\Gateway;

        $input = file_get_contents('php://input');
        $this->mockHdfcGateway->setInput($input);
    }

    public function enroll()
    {
        $input = Input::all();

        return Response::make($this->mockHdfcGateway->enroll($input));
    }

    public function transaction()
    {
        $input = Input::all();

        return $this->mockHdfcGateway->gatewayTransaction($input);
    }

    public function authEnrolled()
    {
        $input = Input::all();

        return $this->mockHdfcGateway->authEnrolled($input);
    }
}