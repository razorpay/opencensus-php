<?php

use Http\AppResponse;
use Models\Merchant;
use Models\MerchantDetails;

class MerchantController extends BaseController
{
    public function getIndex()
    {
        return View::make('merchant.getIndexGenerated');
    }

    public function getMerchant()
    {
       $merchant = (new Merchant\Service)->fetch(Auth::merchant()->id());

       $merchantDetails = (new MerchantDetails\Service)->fetchDetails();

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

        list($error, $data) = (new Merchant\Service)->login($input);

        return AppResponse::jsonResponse($error);
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->register($input);

        return AppResponse::jsonResponse($error);
    }

    public function postResendConfirmation()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->resendConfirmation($input);

        return AppResponse::jsonResponse($error);
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->changePassword($input);

        return AppResponse::jsonResponse($error);
    }

    public function getLogout()
    {
        Auth::merchant()->logout();

        return AppResponse::jsonResponse([]);
    }

    public function getCsv()
    {
        $input = Input::only('id', 'secret');

        if ((isset($input['id']) === false) or
            (isset($input['secret']) === false))
        {
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rzp.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('rzp_id', 'rzp_secret'));
        fputcsv($output, array($input['id'], $input['secret']));
    }

    public function getKeys($mode)
    {
        $keys = (new Merchant\Service)->fetchKeysFromApi(Auth::merchant()->id(), $mode);

        return AppResponse::jsonResponse([], $keys);
    }

    public function postNewKey($mode)
    {
        list($error, $data) = (new Merchant\Service)->createKey(Auth::merchant()->id(), $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postKeys($mode)
    {
        $input = Input::all();

        $input['merchant_id'] = Auth::merchant()->id();

        list($error, $data) = (new Merchant\Service)->rollKeys($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getConfirm($token)
    {
        $error = (new Merchant\Service)->confirm($token);

        return AppResponse::jsonResponse($error);
    }


    public function getActivationDetails()
    {
        $response = (new MerchantDetails\Service)->fetchDetails();

        return AppResponse::jsonResponse([], $response);
    }

    public function postActivation()
    {
        $error = (new MerchantDetails\Service)->submitDetails();

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationStep($id)
    {
        $input = Input::all();

        if ((int) $id !== 5)
        {
            $error = (new MerchantDetails\Service)->saveDetails($id, $input);
        }
        else
        {
            $error = (new MerchantDetails\Service)->checkUploads();
        }

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationFile()
    {
        $input = Input::all();

        $error = (new MerchantDetails\Service)->saveUploadedFile($input);

        return AppResponse::jsonResponse($error);
    }

    public function sendConfirmationMail($job, $data)
    {
        $merchant = $data['merchant'];

        Mail::send('emails.confirmation', compact('merchant'), function($m) use ($merchant)
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
            $m->to('contact@razorpay.com', 'Razorpay Contact')
              ->subject('New Contact form submission');
        });

        return Redirect::to("https://razorpay.com/postcontact/");
    }
}
