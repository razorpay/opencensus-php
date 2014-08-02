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
     * Stage One - The Login form
     * @return  Login View
     */
    public function getLogin()
    {
        return View::make('admin.getLogin');
    }

    /**
     * Stage Two - The Duo Auth form
     * @return Duo Login View or Redirect on error
     */
    public function postLogin()
    {
        $user = array(
            'username' => Input::get('username'),
            'password' => Input::get('password')
        );

        // /**
        //  * Validate the user details, but don't log the user in
        //  */
        // if (Auth::admin()->validate($user))
        // {
        //     $username = Input::get('username');

        //     $duoinfo = array(
        //         'HOST' => $this->_laravelDuo->get_host(),
        //         'POST' => URL::to('/') . '/admin/duologin',
        //         'USER' => $username,
        //         'SIG'  => $this->_laravelDuo->signRequest(
        //                             $this->_laravelDuo->get_ikey(),
        //                             $this->_laravelDuo->get_skey(),
        //                             $this->_laravelDuo->get_akey(),
        //                             $username)
        //     );

        //     return View::make('admin.duologin')
        //                 ->with(compact('duoinfo'))
        //                 ->with('title', 'Duo Authentication');
        // }
        // else
        // {
        //     return View::make('admin.getLogin')
        //                 ->with('error', 'Invalid Username/Password')
        //                 ->with('title', 'Login');
        // }
        
        if(Auth::admin()->attempt($user))
        {
            return Redirect::to('/admin');
        }
        else
        {
            return Redirect::action('AdminController@getLogin')
                                ->with('error', array("Invalid Username/password"));
        }
    }

    /**
     * Stage Three - After Duo Auth Form
     * @return Redirect to home
     */
    // public function postDuologin()
    // {
    //     /**
    //      * Sent back from Duo
    //      */
    //     $response = Input::get('sig_response');

    //     $U = $this->_laravelDuo->verifyResponse(
    //                     $this->_laravelDuo->get_ikey(),
    //                     $this->_laravelDuo->get_skey(),
    //                     $this->_laravelDuo->get_akey(),
    //                     $response
    //     );

    //     /**
    //      * Duo response returns USER field from Stage Two
    //      */
    //     if ($U){

    //         /**
    //          * Get the id of the authenticated user from their email address
    //          */
    //         $id = Admin::getIdFromUsername($U);

    //         /**
    //          * Log the user in by their ID
    //          */
    //         Auth::admin()->loginUsingId($id);

    //         /**
    //          * Check Auth worked, redirect to homepage if so
    //          */
    //         if (Auth::admin()->check())
    //         {
    //             return Redirect::to('/admin');
    //         }
    //     }

    //     /**
    //      * Otherwise, Auth failed, redirect to homepage with message
    //      */
    //     return View::make('admin.getLogin')
    //                 ->with('error', 'Authentication Failed!')
    //                 ->with('title', 'Login');

    // }



    public function getLogout()
    {
        Auth::admin()->logout();

        return Redirect::action('AdminController@getLogin');
    }

    public function getPassword()
    {
        return View::make('admin.getPassword');
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = Service\Admin::getInstance()->changePassword($input, Auth::admin()->get());

        $view = Redirect::action('AdminController@getPassword');

        if (empty($error))
        {
            $view->with('error', array('Passsword changed successfully!'));
        }
        else
        {
            $view->with('data', $data)->with('error', $error);
        }

        return $view;
    }

    public function getIndex()
    {
        return View::make('admin.getIndex');
    }

    public function getMerchants()
    {
        $merchants = Service\Admin::getInstance()->listMerchants();
        
        return View::make('admin.getMerchants')
                   ->with('data', $merchants);
    }

    public function getMerchantLogin($id)
    {
        $merchant = Service\Merchant::getInstance()->fetch($id);

        Auth::merchant()->loginUsingId($merchant['id']);

        return Redirect::to('/');
    }

    public function getMerchantStatus($id)
    {
        $details = Service\Admin::getInstance()->fetchMerchantStatus($id);

        return View::make('admin.getMerchantStatus')
                   ->with('details', $details);
    }

    public function getLockMerchantDetails($id)
    {
        $error = Service\Admin::getInstance()->lockMerchant($id);

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
        $error = Service\Admin::getInstance()->unlockMerchant($id);

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
        $details = Service\Admin::getInstance()->fetchMerchantDetails($id);

        return View::make('admin.getMerchantDetails')
                   ->with('data', json_encode($details));
    }

    public function getMerchantActivation($id)
    {   
        $details = Service\Admin::getInstance()->fetchMerchantStatus($id);

        $pricing_plans = Service\Admin::getInstance()->fetchPricingPlan();

        return View::make('admin.getMerchantActivation')
                   ->with('pricing_plans', $pricing_plans)
                   ->with('details', $details);
    }

    public function postMerchantActivation($id)
    {   
        $input = Input::all();

        $error = Service\Admin::getInstance()->activateMerchant($id, $input);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', array('Merchant activated successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id.'/activate')->with('status', $error);
        }
    }

    public function getMerchantDeactivation($id)
    {   
        $error = Service\Admin::getInstance()->deactivateMerchant($id);

        if(empty($error))
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', array('Merchant deactivated successfully'));
        }
        else
        {
            return Redirect::to('/admin/merchant/'.$id)->with('status', $error);
        }
    }

    public function getAdmins()
    {
        $admins = Service\Admin::getInstance()->getAdmins();

        return View::make('admin.getAdmins')
                   ->with('admins', $admins);
    }

    public function getDeleteAdmin($id)
    {
        $response = Service\Admin::getInstance()->deleteAdmin($id);

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

        list($error, $data) = Service\Admin::getInstance()->add($input, Auth::admin()->get());

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
