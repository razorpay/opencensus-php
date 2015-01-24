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
        return $this->mockHdfcGatewayServer->gatewayTransaction();
    }

    public function authEnrolled()
    {
        return $this->mockHdfcGatewayServer->authEnrolled();
    }

    public function getAtomChooseBank()
    {
        $server = new Gateway\MockAtom\Server;

        $input = Input::all();

        $data = $server->netBankingTransactionChooseBank($input);

        return View::make('gateway.atomBankSubmit')
                   ->with('data', $data);
    }

    public function postAtomInitNetBanking()
    {
        $server = new Gateway\MockAtom\Server;

        $input = Input::all();

        return $server->initiateNetBankingTransaction($input);
    }

    public function postAtomRzpBankPage()
    {
        $server = new Gateway\MockAtom\Server;

        $input = Input::all();

        $data = $server->atomRzpBankPage($input);

        return View::make('gateway.atomRzpBankPage')
                   ->with('data', $data);
    }

    public function postAtomRzpBankSubmit()
    {
        $input = Input::all();

        $server = new Gateway\MockAtom\Server;

        list($url, $data) = $server->atomRzpBankPageSubmit($input);

        return View::make('gateway.atomMerchantCallback')
                   ->with('url', $url)
                   ->with('data', $data);
    }
}