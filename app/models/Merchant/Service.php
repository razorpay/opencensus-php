<?php

namespace Models\Merchant;

use Models\Base;
use Models\Merchant;
use Models\User;
use Models\MerchantDetails;
use Auth;
use Mail;
use Requests;

class Service extends Base\Service
{
    public function register(array $input)
    {
        $merchant = new Merchant\Entity;
        $error = $merchant->build($input);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $merchant->saveOrFail();

        $user = User\Entity::createFromMerchant($merchant);
        $user->saveOrFail();
        $user->merchants()->attach($merchant, ['role' => 'owner']);

        $details = array(
            'merchant_id' => $merchant->id,
            'contact_email' => $merchant->email
        );

        MerchantDetails\Entity::createOrFail($details);

        $this->queueConfirmationMail($merchant);

        $slackData = [
            'id'    => $merchant->id,
            'name'  => $merchant->name,
            'email' => $merchant->email
        ];

        $this->slackSignupPost($slackData);

        return [$error, $slackData];
    }

    protected function queueConfirmationMail($user)
    {
        $user = $user->generateEmailData();

        Mail::send('emails.confirmation', compact('user'), function($m) use ($user)
        {
            $m->to($user['email'], $user['name'])->subject('Razorpay | Confirm Your Email');
        });
    }

    protected function slackSignupPost($slackData)
    {
        if($_ENV['SLACK_ENABLE'] === true)
        {
            $merchantLink = "https://dashboard.razorpay.com/admin#/app/merchants/{$slackData['id']}/detail";

            $postData = [
                'email'         => $slackData['email'],
                'name'          => $slackData['name'],
                // This is in slack formatting
                'message'       => "[New Signup]($merchantLink)"
            ];

            Requests::post('https://sorting-hat-slack.herokuapp.com/',[] , $postData);
        }
    }

    /**
     * take care when calling this function
     * This is only called from the admin service
     * @param  string $id    Merchant Id
     * @param  array $input  Array with new Merchant Email Address
     */
    public function changeEmail($id, $input)
    {
        $merchant = Merchant\Entity::findorfail($id);

        if ($merchant->isTestAccount()) {
            return [["Email change forbidden on this account"], null];
        }

        $originalEmail = $merchant->email;
        $error = $merchant->changeEmail($input);

        if (empty($error))
        {
            if($merchant->hasUsers())
            {
                $user = $merchant->users()->where('email',$originalEmail)->first();
                if($user)
                {
                    $user->email = $merchant->email;
                    $user->save();
                }
            }
            $merchant->save();
        }
        
        $merchantDetails = $merchant->merchantDetails;
        $merchantDetails->contact_email = $merchant->email;
        $merchantDetails->save();
    
        return [$error, null];
    }

    public function confirm($token)
    {
        $merchant = Merchant\Entity::getMerchantForConfirmation($token);

        if ($merchant === null)
        {
            return array('Invalid confirmation token or the merchant is already confirmed.');
        }

        $merchant_api_data = $merchant->generateApiData();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->merchant->create($merchant_api_data);
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        $merchant->confirm_token = null;
        $email = $merchant->email;
        $merchant->saveOrFail();

        if($merchant->hasUsers())
        {
            $user = $merchant->users()->where('email',$email)->first();
            if($user)
            {
                $user->confirm_token = $merchant->confirm_token;
                $user->save();
            }
        }

        return array();
    }

    public function resendConfirmation(array $input)
    {
        $error = (new Merchant\Validator)->validateInput('login', $input)->messages();

        if (empty($error))
        {
            $credentials = array(
                'email'     => $input['email'],
                'password'  => $input['password']
            );

            $user = \Auth::user();

            if ($user->once($credentials))
            {
                $user = \Auth::user()->get();

                if ($user->confirm_token === null)
                {
                    return [['Merchant already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], []];
                }

                $this->queueConfirmationMail($user);

                return [[], []];
            }
        }

        return [['Email or password is invalid.'], []];
    }

    public function fetchKeysFromApi($merchant_id, $mode)
    {
        $this->setApiCredentials(null, $mode);

        $response = $this->api->merchant
                              ->fetch($merchant_id)
                              ->keys()
                              ->all()
                              ->toArray();

        return $response;
    }

    public function createKey($merchant_id, $mode)
    {
        $errors = array();
        $data = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $data = $this->api->merchant
                                ->fetch($merchant_id)
                                ->keys()
                                ->create()
                                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return array($errors, $data);
    }

    public function rollKeys(array $input, $mode)
    {
        if (Auth::user()->user()->currentMerchant->isTestAccount()) {
            return [["Roll key forbidden on this account"], null];
        }

        $error = (new Merchant\Validator)->validateInput('key', $input)->messages();

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $this->setApiCredentials(null, $mode);

        $key_data = array();

        try
        {
            $response = $this->api->merchant
                                ->fetch($input['merchant_id'])
                                ->keys()
                                ->fetch($input['id'])
                                ->roll($input['delay_roll'])
                                ->toArray();

            $key_data = array(
                'old_id'        => $input['id'],
                'merchant_id'   => $input['merchant_id'],
                'new'           => $response['new']
            );
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $key_data);
    }
}
