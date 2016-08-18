<?php

namespace RZP\Http\Controllers;

use RZP\Constants\Mode;
use Database\DefaultConnection;
use Request;
use Redirect;
use View;
use RZP\Http\ApiResponse;

class MockGatewayController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $input = file_get_contents('php://input');

        \Database\DefaultConnection::set(Mode::TEST);

        $this->gateway = $this->app['gateway'];

        $this->mockHdfcGatewayServer = $this->gateway->server('hdfc');
        $this->mockHdfcGatewayServer->setInput($input);
    }

    public function post3dSecure()
    {
        $input = Request::all();

        $data = $this->mockHdfcGatewayServer->threeDSecure($input);

        return View::make('gateway.3dsecure')->with('data', $data);
    }

    public function postAcs($gateway)
    {
        $input = Request::all();

        unset($input['key_id']);

        $server = $this->gateway->server($gateway);

        $data = $server->acs($input);

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

        $input = Request::all();

        $data = $server->atomPaymentChooseOrg($input);

        return View::make('gateway.atomBankSubmit')
                   ->with('data', $data);
    }

    public function postAtomInitPayment()
    {
        $server = $this->gateway->server('atom');

        $input = Request::all();

        return $server->authorize($input);
    }

    public function postAtomRzpPayment()
    {
        $server = $this->gateway->server('atom');

        $input = Request::all();

        $data = $server->atomRzpPayment($input);

        return View::make('gateway.atomRzpBankPage')
                   ->with('data', $data);
    }

    public function postAtomRzpPaymentSubmit()
    {
        $server = $this->gateway->server('atom');

        $input = Request::all();

        list($url, $data) = $server->atomRzpPaymentPageSubmit($input);

        return View::make('gateway.atomMerchantCallback')
                   ->with('url', $url)
                   ->with('data', $data);
    }

    public function postAxisPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('axis_migs');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postFirstDataPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('first_data');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postAxisGeniusPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('axis_genius');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function getKotakPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('kotak');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postPaytmPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('paytm');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postBilldeskPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('billdesk');

        return $server->authorize($input);
    }

    public function postEbsPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('ebs');

        return $server->authorize($input);
    }

    public function postAmexPayment()
    {
        $input = Request::all();

        $server = new \RZP\Gateway\Amex\Mock\Server;

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function getSharpPayment()
    {
        $input = Request::all();

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
        $input = Request::all();

        $server = $this->gateway->server('sharp');

        $url = $server->authSubmit($input);

        return Redirect::to($url);
    }

    public function postNetbankingPayment($bank)
    {
        $input = Request::all();

        $driver = 'netbanking_'.$bank;
        $server = $this->gateway->server($driver);

        $data = $server->authorize($input);

        if (filter_var($data, FILTER_VALIDATE_URL))
        {
            return Redirect::to($data);
        }

        return $data;
    }

    public function postMobikwikPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('mobikwik');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function postSbiepayPayment()
    {
        $input = Request::all();

        $server = new \RZP\Gateway\Sbiepay\Mock\Server;

        return $server->authorize($input);
    }

    public function postWalletPayment($wallet, $paymentId = null)
    {
        $input = Request::all();

        $driver = 'wallet_' . $wallet;

        $server = $this->gateway->server($driver);

        return $server->authorize($input, $paymentId);
    }
}
