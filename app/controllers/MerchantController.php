<?php

use Models\Service;

class MerchantController extends BaseController
{
    public function getIndex()
    {
        return View::make('merchant.getIndex');
    }

    public function getUser()
    {
       $merchant = (new Service\Merchant)->fetch(\Auth::merchant()->id());

       $merchantDetails = (new Service\MerchantDetails)->fetchDetails();

       $data = $merchant + $merchantDetails;

       $response = array('success' => true, 'data' => $data);

       return Response::JSON($response);
    }

    public function getLogin()
    {
        return View::make('merchant.getLogin');
    }

    public function postSignin()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->login($input);

        if (empty($error))
            return Response::json(array('success' => true));
        else
            return Response::json(array('success' => false, 'errors' => $error));
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
            return Response::json(array('success' => true));
        }
        else
        {
            return Response::json(array('success' => false, 'errors' => $error));
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
        return Response::json(array('success' => true));
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

        return array('success' => true) + $keys;
    }

    public function postNewKey($mode)
    {
        $key = (new Service\Merchant)->createKey(\Auth::merchant()->id(), $mode);

        return array('success' => true, 'data' => $key);
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
            return Response::json(array('success' => true));
        
        }
        else
        {
            return Response::json(array('success' => false, 'errors' => array('An error occured in email verification. Please check the link and try again')));
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
            return Response::json(array('success' => false, 'errors' => $error));
        }
    }

    public function postSaveActivationStep($id)
    {
        $input = Input::all();
        unset($input['_token']);

        $error = array();
        $data  = array();

        if($id != 5)
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
            return Response::json(array('success' => false, 'errors' => $error));
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
            return Response::json(array('success' => false, 'errors' => $error));
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
