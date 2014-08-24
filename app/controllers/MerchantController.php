<?php

use Models\Service;

class MerchantController extends BaseController
{
    public function getIndex()
    {
        return View::make('merchant.getIndex');
    }

    public function getLogin()
    {
        return View::make('merchant.getLogin');
    }

    public function postLogin()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->login($input);

        if (empty($error))
            return Redirect::action('MerchantController@getIndex');
        else
            return Redirect::action('MerchantController@getLogin')
                ->with('data', $data)
                ->with('error', $error);
    }

    public function getRegister()
    {
        return View::make('merchant.getRegister');
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->register($input);

        if (empty($error))
        {
            return View::make('merchant.postRegister')
                        ->with('data', $data);
        }
        else
        {
            return Redirect::action('MerchantController@getRegister')
                ->with('data', $data)
                ->with('error', $error);
        }
    }

    public function getPassword()
    {
        return View::make('merchant.getPassword');
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->changePassword($input);

        if (empty($error))
        {
            return Redirect::action('MerchantController@getPassword')
                        ->with('error', array('Password changed successfully'));
        }
        else
        {
            return Redirect::action('MerchantController@getPassword')
                ->with('error', $error);
        }
    }

    public function getLogout()
    {
        \Auth::merchant()->logout();
        return Redirect::action('MerchantController@getLogin');
    }

    public function getCsv()
    {
        $input = Input::only(
            'id', 'secret'
        );

        if (!isset($input['id']) || !isset($input['secret']))
            return;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rzp.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('rzp_id', 'rzp_secret'));
        fputcsv($output, array($input['id'], $input['secret']));
    }

    public function getAccount()
    {
        $merchant = (new Service\Merchant)->fetch(\Auth::merchant()->id());

        return $merchant;
    }

    public function getKeys($mode)
    {
        $keys = (new Service\Merchant)->fetchKeysFromApi(\Auth::merchant()->id(), $mode);

        return $keys;
    }

    public function getNewKey($mode)
    {
        $key = (new Service\Merchant)->createKey(\Auth::merchant()->id(), $mode);

        return View::make('merchant.getKeys')
                        ->with('key', $key)
                        ->with('mode', $mode);
    }

    public function postKeys($mode)
    {
        $input = Input::all();
        unset($input['_token']);

        $input['merchant_id'] = \Auth::merchant()->id();

        $data = (new Service\Merchant)->rollKeys($input, $mode);

        return $data;
    }

    public function getConfirm($token)
    {
        $response = (new Service\Merchant)->confirm($token);

        if($response)
        {   
            return Redirect::action('MerchantController@getLogin')
                ->with('error', array('Activation Successful. Login to start using Razorpay.'));
        
        }
        else
        {
            return Redirect::action('MerchantController@getRegister')
                ->with('error', array('An error occured in email verification. Please check the link and try again'));
        }
    }

    public function getActivation()
    {
        return View::make('merchant.getActivationGenerated');
    }

    public function getActivationDetails()
    {
        $response = (new Service\MerchantDetails)->fetchDetails();
        
        return Response::json($response);
    }

    public function postActivation()
    {
        $error = (new Service\MerchantDetails)->submitDetails();

        if (empty($error))
        {
            return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'status' => $error));
        }
    }

    public function postSaveActivationStep($id)
    {
        $input = Input::all();
        unset($input['_token']);

        $error = array();
        $data  = array();

        if($id != 4)
        {
            $error = (new Service\MerchantDetails)->saveDetails($id, $input);  
        }
        else
        {
            $error = (new Service\MerchantDetails)->checkUploads();
        }
        
        if (empty($error))
        {
            return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'status' => $error));
        }
    }

    public function postSaveActivationFile()
    {
        $input = Input::all();
        unset($input['_token']);

        $error = (new Service\MerchantDetails)->saveUploadedFile($input);

        if (empty($error))
        {
            return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'status' => $error[0]));
        }
    }

    public function sendConfirmationMail($job, $data)
    {
        $merchant = $data['merchant'];

        \Mail::send('emails.confirmation', compact('merchant'), function($m) use ($merchant)
        {
            $m->to($merchant['email'], $merchant['name'])->subject('Welcome to Razorpay!');
        });

        $job->delete();
    }

    public function postContact()
    {   
        $input = Input::all();

        Mail::send('emails.contact',compact('input'), function($m)
        {
            $m->to('contact@razorpay.com', 'Razorpay Contact')->subject('New Contact form submission');
        });

        return Redirect::to("https://razorpay.com/postcontact/");
    }
}
