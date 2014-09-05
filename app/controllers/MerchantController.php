<?php

use Models\Service;

use Http\AppResponse;

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

       return AppResponse::jsonResponse([], $data);
    }

    public function getKeepAlive()
    {
        return AppResponse::jsonResponse([]);
    }

    public function postSignin()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->login($input);

        return AppResponse::jsonResponse($error);
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->register($input);

        return AppResponse::jsonResponse($error);
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Service\Merchant)->changePassword($input);

        return AppResponse::jsonResponse($error);
    }

    public function getLogout()
    {
        \Auth::merchant()->logout();
        
        return AppResponse::jsonResponse([]);
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

    public function getKeys($mode)
    {
        $keys = (new Service\Merchant)->fetchKeysFromApi(\Auth::merchant()->id(), $mode);

        return AppResponse::jsonResponse([], $keys);
    }

    public function postNewKey($mode)
    {
        $key = (new Service\Merchant)->createKey(\Auth::merchant()->id(), $mode);

        return AppResponse::jsonResponse([], $key);
    }

    public function postKeys($mode)
    {
        $input = Input::all();
        unset($input['_token']);

        $input['merchant_id'] = \Auth::merchant()->id();

        list($error, $data) = (new Service\Merchant)->rollKeys($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getConfirm($token)
    {
        $error = (new Service\Merchant)->confirm($token);

        return AppResponse::jsonResponse($error);
    }


    public function getActivationDetails()
    {
        $response = (new Service\MerchantDetails)->fetchDetails();
        
        return AppResponse::jsonResponse([], $response);
    }

    public function postActivation()
    {
        $error = (new Service\MerchantDetails)->submitDetails();

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationStep($id)
    {
        $input = Input::all();
        unset($input['_token']);

        if($id != 5)
        {
            $error = (new Service\MerchantDetails)->saveDetails($id, $input);  
        }
        else
        {
            $error = (new Service\MerchantDetails)->checkUploads();
        }
        
        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationFile()
    {
        $input = Input::all();
        unset($input['_token']);

        $error = (new Service\MerchantDetails)->saveUploadedFile($input);

        return AppResponse::jsonResponse($error);
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
