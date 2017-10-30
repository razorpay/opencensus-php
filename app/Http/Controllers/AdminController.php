<?php
namespace App\Http\Controllers;

use App;
use Auth;
use View;
use Input;
use Cache;
use Config;
use Session;
use Response;
use Redirect;
use App\Admin;
use OAuthFacade;
use App\Merchant;
use App\Admin\Entity;
use App\Http\AppResponse;
use App\Http\SlackResponse;
use Razorpay\Api\Request as ApiRequest;

class AdminController extends Controller
{

    protected $redirectTo = '/admin';
    protected $guard = 'admin';

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;
    }

    /**
     * Route = /admin/auth
     * @return
     */
    public function initiateAuth()
    {
        // If already logged in
        if (Auth::guard('api')->check())
        {
            return redirect('/admin');
        }

        $org = $this->getOrg()->getData(true);

        if ($org['success'])
        {
            $org = $org['data'];
        }
        else
        {
            return AppResponse::jsonResponse(['Organization not found'], null);
        }

        $code = Input::get('code');

        if (! empty($code) || config('oauth.mock'))
        {
            $oauth = $this->triggerGoogleOAuth($code, $org);

            if (! empty($oauth))
            {
                return $oauth;
            }
        }

        switch($org['auth_type'])
        {
            case 'google_auth':
                return redirect($this->getGoogleOAuthUrl());
        }

        // Password login by default
        return redirect('/admin#/access/auth/password');
    }

    /**
     * We always return the view, since it does not
     * contain anything sensitive
     */
    public function getIndex()
    {
        return view('admin.index', [
            'entry' => \Config::get('app.entry_asset_url'),
            'org' => $this->getOrg()->getData(true),
            'user' => $this->getAdmin()->getData(true)
        ]);
    }

    public function getAngular()
    {
        return view('admin.tmpgetIndex');
    }

    protected function getGoogleOAuthUrl()
    {
        $googleService = OAuthFacade::consumer('Google');

        return (string) $googleService->getAuthorizationUri();
    }

    public function triggerGoogleOAuth($code, $org)
    {
        $googleService = OAuthFacade::consumer('Google');

        // if code is provided get user data and sign in
        if ($code !== null or (config('oauth.mock') === true))
        {
            $error = (new Admin\Service)->loginWithGoogle($code, $googleService, $org['id']);

            if (empty($error))
            {
                // sort of a page reload/refresh
                return redirect('/admin');
            }
            else
            {
                return AppResponse::jsonResponse($error, []);
            }
        }
    }

    public function postSignin()
    {
        $input = Input::all();

        $domain = \Request::server('SERVER_NAME');

        // This is password based login
        list($error, $user) = (new Admin\Service)->passwordLogin($domain, $input);

        if (! empty($error))
        {
            return AppResponse::jsonResponse($error, []);
        }

        if (Auth::guard('api')->check())
        {
            return AppResponse::jsonResponse(null);
        }

        return AppResponse::jsonResponse(['Invalid Credentials'], []);
    }

    public function getOrg()
    {
        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = (new Admin\Service)->getOrg($domain);

        return AppResponse::jsonResponse($error, $org);
    }

    public function getAdmin()
    {
        // We are not caching admin data in session because permissions,
        // roles, etc. might change
        //
        // So for now every time the user refreshes the page
        // we will load entire payload and give it to the frontend
        // to work with.
        //
        // Later at some point we'll have to shove all these data in
        // redis and that'll work well.

        $admin = Auth::guard('api')->user();

        list($error, $data) = (new Admin\Service)->getAdminData($admin);

        if (! empty($error))
        {
            Auth::guard('api')->logout();
        }

        return AppResponse::jsonResponse($error, $data);
    }

    public function getAdminActivity()
    {
        $id = Auth::guard('api')->user()->id;

        $activity = (new Admin\Service)->getAdminActivity($id);

        return AppResponse::jsonResponse([], $activity);
    }

    public function deleteOtherAdminActivity()
    {
        $id = Auth::guard('api')->user()->id;

        (new Admin\Service)->deleteAllOtherAdminSessions($id);

        return AppResponse::jsonResponse([]);
    }

    public function deleteAdminActivity($sessionId)
    {
        (new Admin\Service)->deleteOneAdminSessions($sessionId);

        return AppResponse::jsonResponse([]);
    }

    public function getLogout()
    {
        list($error, $data) = (new Admin\Service)->logout();

        return AppResponse::jsonResponse($error, $data);
    }

    public function getKeepAlive()
    {
        $error = [];
        $response = (new Admin\Service)->updateKeepAlive();

        if ($response === false)
        {
            // Auth::admin()->logout();
            // $error = ['You have been logged out'];
        }

        return AppResponse::jsonResponse($error, $response);
    }

    public function getMerchantLogin($id)
    {
        $error = (new Admin\Service)->loginUsingPrimaryOwner($id);

        if (empty($error) === false)
        {
            return AppResponse::jsonResponse($error);
        }

        return redirect('/');
    }

    public function postMerchantTerminal($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantTerminal($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantActivation($id)
    {
        $dashboardOnly = Input::get('dashboard', false);

        list($error, $data) = (new Admin\Service)->activateMerchant($id, $dashboardOnly);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Calls the Creevey service over a queue to capture screenshots
     * @param  string $id Merchant Id
     */
    public function captureMerchantScreenshot($id)
    {
        $error = (new Admin\Service)->captureScreenshot($id);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Returns an HTML View for now
     * @param  string $id merchant id
     */
    public function getMerchantScreenshot($id)
    {
        $links = (new Admin\Service)->getScreenshot($id);

        return View::make('admin.screenshots', ['links' => $links]);
    }

    public function saveMerchantScreenshot($id)
    {
        $input = \Input::all();

        $error = (new Admin\Service)->saveScreenshot($id, $input);

        return AppResponse::jsonResponse($error);
    }

    public function postEditMerchant($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchant($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putEditMerchantEmail($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchantEmail($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMultipleEntities($mode, $entity, $format = 'json')
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->fetchMultipleEntities($mode, $entity, $input);

        if ($format === 'csv' and empty($error) === true)
        {
            (new Admin\Service)->logDataExport($entity, $input);

            return AppResponse::csvResponse($data['items']);
        }
        else
        {
            return AppResponse::jsonResponse($error, $data);
        }
    }

    public function getMerchantHdfcExcel($id)
    {
        list($error, $file) = (new Admin\Service)->generateMerchantHdfcExcel($id);

        if (empty($error) === false)
            return AppResponse::jsonResponse($error);

        $file->download('xlsx');
    }

    public function passThrough($path = '')
    {
        list($error, $response) = (new Admin\Service)->makeRawApiCall($path);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postReconcileSettlement()
    {
        $path = 'settlements/reconcile';
        list($error, $response) = (new Admin\Service)->makeRawApiCall($path);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postTagMerchant($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->tagMerchant($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);

    }

    public function addEntityFeatures($entityType, $entityId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->addEntityFeatures($entityType, $entityId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Confirm a user account manually
     */
    public function postConfirmUser()
    {
        $input = Input::all();

        list($error, $data) = $response = (new Admin\Service)->confirmUser($input['email']);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postSlackQuery()
    {
        $input = Input::all();

        list($message, $data) = (new Admin\Service)
            ->querySlack($input);

        return SlackResponse::jsonResponse($message, $data);
    }

    public function getMerchantAggregations($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->getMerchantAggregations($mode, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSingleMerchantAggregations($mode, $merchant)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->getSingleMerchantAggregations($mode, $input, $merchant);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getCompanyInfo($cin)
    {
        $company = new Admin\Company($cin);
        return AppResponse::jsonResponse([], $company->fetch());
    }

    public function postReconciliate($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Admin\Service)->makeReconciliateRequest($input, $mode);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
    * Expects date input in format "3 august 2016"
    */
    public function updateDayAggregations($mode)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->updateMerchantDayAggregations($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    // ----- Heimdall (Whitelabel) -----

    public function postUploadOrgLogo($orgId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->uploadOrgLogo($orgId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function getStatus()
    {
        list($response, $statusCode) = (new Admin\Service)->getStatus();

        return Response::json($response, $statusCode);
    }

    public function getEmailLogs()
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->getEmailLogs($input);

        return AppResponse::jsonResponse($error, $response);
    }
}
