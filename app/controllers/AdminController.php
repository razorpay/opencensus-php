<?php

use Models\Service;

class AdminController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */
    // private $_laravelDuo;

    // function __construct(LaravelDuo\LaravelDuo $laravelDuo)
    // {
    //     $this->_laravelDuo = $laravelDuo;
    // }

    /**
     * Stage Two - The Duo Auth form
     * @return Duo Login View or Redirect on error
     */
    public function postSignin()
    {
        $user = array(
            'username' => Input::get('username'),
            'password' => Input::get('password')
        );

        if(Auth::admin()->attempt($user))
        {
             return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'errors' => array('Invalid username/password.')));
        }
    }

    public function getAdmin()
    {
        $admin = Auth::admin()->get()->toArray();

        return array('success' => true, 'data' => $admin);
    }

    public function getLogout()
    {
        Auth::admin()->logout();

        return Response::json(array('success' => true));
    }

    public function getKeepAlive()
    {
        return ['success' => true];
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Admin)->changePassword($input, Auth::admin()->get());

        if (empty($error))
        {
            return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'errors' => $error));
        }

        return $view;
    }

    public function getIndex()
    {
        return View::make('admin.getIndex');
    }

    public function getMerchantList()
    {
        $merchants = (new Service\Admin)->listMerchants();
        
        return View::make('admin.getMerchantList')
                   ->with('data', $merchants);
    }

    public function getMerchantLogin($id)
    {
        $merchant = (new Service\Merchant)->fetch($id);

        Auth::merchant()->loginUsingId($merchant['id']);

        return Redirect::to('/');
    }

    public function getMerchant($id)
    {
        $details = (new Service\Admin)->fetchMerchantDetails($id);

        $terminal = (new Service\Admin)->fetchMerchantTerminal($id);

        $pricing_plan = (new Service\Admin)->fetchMerchantPricing($id);

        return View::make('admin.getMerchant')
                   ->with('details', $details)
                   ->with('terminal', $terminal)
                   ->with('pricing_plan', $pricing_plan);
    }

    public function getMerchantTerminal($id)
    {   
        $details = (new Service\Admin)->fetchMerchantDetails($id);

        $terminal = (new Service\Admin)->fetchMerchantTerminal($id);

        return View::make('admin.getMerchantTerminal')
                   ->with('terminal', $terminal)
                   ->with('details', $details);
    }

    public function postMerchantTerminal($id)
    {   
        $input = Input::all();

        $error = (new Service\Admin)->postMerchantTerminal($id, $input);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id.'/terminal')->with('status', array('Terminal added successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id.'/terminal')->with('status', $error);
        }
    }

    public function getMerchantPricing($id)
    {   
        $details = (new Service\Admin)->fetchMerchantDetails($id);

        $pricing = (new Service\Admin)->fetchMerchantPricing($id);

        //sd($pricing);

        $pricing_plans = (new Service\Admin)->fetchPricingPlan();

        return View::make('admin.getMerchantPricing')
                   ->with('details', $details)
                   ->with('pricing', $pricing)
                   ->with('pricing_plans', $pricing_plans);
    }

    public function postMerchantPricing($id)
    {   
        $input = Input::all();

        $error = (new Service\Admin)->postMerchantPricing($id, $input);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id.'/pricing')->with('status', array('Pricing added successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id.'/pricing')->with('status', $error);
        }
    }

    public function getMerchantActivation($id)
    {   

        $error = (new Service\Admin)->activateMerchant($id);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', array('Merchant activated successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', $error);
        }
    }

    public function getMerchantDeactivation($id)
    {   
        $error = (new Service\Admin)->deactivateMerchant($id);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', array('Merchant deactivated successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', $error);
        }
    }

    public function getLockMerchantDetails($id)
    {
        $error = (new Service\Admin)->lockMerchant($id);

        $view = Redirect::to('/admin/merchant/'.$id);

        if(empty($error))
        {
            return $view->with('status', array('Merchant activation form locked Successfully!'));
        }
        else
        {
            return $view->with('status', $error);
        }
    }

    public function getUnlockMerchantDetails($id)
    {
        $error = (new Service\Admin)->unlockMerchant($id);

        $view = Redirect::to('/admin/merchant/'.$id);

        if(empty($error))
        {
            return $view->with('status', array('Merchant activation form unlocked Successfully!'));
        }
        else
        {
            return $view->with('status', $error);
        }
    }

    public function getMerchantDetails($id)
    {   
        $details = (new Service\Admin)->fetchMerchantDetails($id);

        $activation_details = (new Service\Admin)->fetchMerchantActivationDetails($id);

        return View::make('admin.getMerchantDetails')
                   ->with('data', json_encode($activation_details))
                   ->with('details', $details);
    }

    public function getPricingList()
    {
        $data = (new Service\Admin)->fetchPricingPlan();

        return View::make('admin.getPricingList')->with('plans', $data['data']);
    }

    public function getPricingRules($id)
    {
        $data = (new Service\Admin)->fetchPricingPlan($id);

        return View::make('admin.getPricingRules')->with('plan', $data);
    }

    public function postPricingRules($id)
    {   
        $input = Input::all();

        $data = (new Service\Admin)->fetchPricingPlan($id);

        $error = (new Service\Admin)->addPricingPlanRule($data['id'], $input);

        if(empty($error))
        {
            return Redirect::to('/admin/pricing/'.$id)->with('status', array('Rule Added successfully'));
        }
        else
        {
            return Redirect::to('/admin/pricing/'.$id)->with('status', $error);
        }
    }

    public function getNewPricingPlan()
    {
        return View::make('admin.getNewPricingPlan');
    }

    public function postNewPricingPlan()
    {
        $input = Input::all();

        $response = (new Service\Admin)->createPricingPlan($input);

        if(isset($response['error']) === false)
        {
            return Redirect::to('/admin/pricing/'.$response['id'])->with('status', array('Plan Added successfully'));
        }
        else
        {
            return Redirect::to('/admin/pricing/new')->with('status', array($response['error']['description']));
        }
    }

    public function getAdmins()
    {
        $admins = (new Service\Admin)->getAdmins();

        return View::make('admin.getAdmins')
                   ->with('admins', $admins);
    }

    public function getDeleteAdmin($id)
    {
        $response = (new Service\Admin)->deleteAdmin($id);

        return Redirect::action('AdminController@getAdmins')
                        ->with('success', $response);
    }

    public function getAddAdmin()
    {
        return View::make('admin.getAddAdmin');
    }

    public function postAddAdmin()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Admin)->add($input, Auth::admin()->get());

        $view = Redirect::action('AdminController@getAddAdmin');

        if (empty($error))
        {
            $view->with('error', array('Admin added successfully!'));
        }
        else
        {
            $view->with('data', $data)->with('error', $error);
        }

        return $view;
    }
}
