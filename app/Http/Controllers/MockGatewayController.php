<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Redirect;
use ApiResponse;
use RZP\Gateway\Hdfc;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\CheckoutView;
use RZP\Gateway\GatewayManager;
use RZP\Models\Locale\Core as LocaleCore;

class MockGatewayController extends Controller
{
    /**
     * @var GatewayManager
     */
    protected $gateway;

    /**
     * @var Hdfc\Mock\Server
     */
    protected $mockHdfcGatewayServer;

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

        if (isset($input['PaReq']) === false)
        {
           $data = $this->mockHdfcGatewayServer->debitPin($input);

           return View::make('gateway.debitpin')->with('data', $data);
        }

        else
        {
            $data = $this->mockHdfcGatewayServer->threeDSecure($input);
        }

        return View::make('gateway.3dsecure')->with('data', $data);
    }

    public function postAcs($gateway)
    {
        $input = Request::all();

        unset($input['key_id'], $input['language_code']);

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

    public function postAtomPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('atom');

        $data = $server->bank($input);

        return Redirect::to($data);
    }

    public function postAxisPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('axis_migs');

        $url = $server->acs($input);

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

        $url = $server->acs($input);

        return Redirect::to($url);
    }

    public function getKotakPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('kotak');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function getFssPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('card_fss');

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

        $data = $server->bank($input);

        return View::make('gateway.bankRedirection')->with('data', $data);
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

        $url = $server->acs($input);

        return Redirect::to($url);
    }

    public function getSharpPayment()
    {
        $input = Request::all();

        $merchant =  $this->app['basicauth']->getMerchant();

        $languageCode = LocaleCore::setLocale($input,$merchant->getId());

        $server = $this->gateway->server('sharp');

        list($data, $error) = $server->action($input);

        if ($error !== null)
        {
            return ApiResponse::json($error);
        }

        if ($data['action'] === 'authorize')
        {
            $data['language_code'] = $languageCode;

            $merchant = $this->app['basicauth']->getMerchant();

            $data += (new CheckoutView())->addOrgInformationInResponse($merchant, true);

            $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                [
                    'view create via'   =>  'gateway.sharpBankPage',
                    'org_logo'           => $data['org_logo'],
                    'org_name'          => $data['org_name'],
                ]);

            if (!filter_var($data['org_logo'], FILTER_VALIDATE_URL))
            {
                $data['org_logo'] = 'https://cdn.razorpay.com/logo.svg';
            }
            return View::make('gateway.sharpBankPage')
                       ->with('data', $data);
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

    public function postMozartPayment($gateway)
    {
        $input = Request::all();

        $server = $this->gateway->server('mozart');

        $server->setGateway($gateway);

        $data = $server->authorize($input);

        if (filter_var($data, FILTER_VALIDATE_URL))
        {
            return Redirect::to($data);
        }

        return $data;
    }

    public function postCardlessEmiPayment()
    {
        $input = Request::all();

        $driver = 'cardless_emi';

        $server = $this->gateway->server($driver);

        $data = $server->authorize($input);

        return $data;
    }

    public function postPaylaterPayment()
    {
        $input = Request::all();

        $driver = 'paylater';

        $server = $this->gateway->server($driver);

        $data = $server->authorize($input);

        return $data;
    }

    public function postMobikwikPayment()
    {
        $input = Request::all();

        $server = $this->gateway->server('mobikwik');

        $url = $server->authorize($input);

        return Redirect::to($url);
    }

    public function walletPayment($wallet, $paymentId = null)
    {
        $input = Request::all();

        $driver = 'wallet_' . $wallet;

        $server = $this->gateway->server($driver);

        return $server->authorize($input, $paymentId);
    }

    public function postEsignerPayment($esigner)
    {
        $input = Request::all();

        $server = $this->gateway->server($esigner);

        return $server->sign($input);
    }

    public function postEnachNpciNetbankingPayment($authType)
    {
        $input = Request::all();

        $driver = 'enach_npci_' . $authType;

        $server = $this->gateway->server($driver);

        $data =  $server->authorize($input);

        return $data;
    }

    public function postUpiPayment($bank)
    {
        $input = Request::all();

        $driver = 'upi_' . $bank;

        $server = $this->gateway->server($driver);

        //return $server->authorize($input, $paymentId);

        return;
    }

    public function postAepsPayment($bank)
    {
        $input = Request::all();

        $driver = 'aeps_' . $bank;

        $server = $this->gateway->server($driver);

        return $server->authorize($input);
    }

    public function postPaysecurePayment()
    {
        $input = Request::all();

        $driver = 'paysecure';

        $server = $this->gateway->server($driver);

        return $server->authorize($input);
    }

    public function generateGatewayReconciliationFile(string $gateway)
    {
        $input = Request::all();

        $recon = $this->gateway->recon($gateway, $input);

        return $recon->generateReconciliation($input);
    }
}
