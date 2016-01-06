<?php

namespace Models\Merchant;

use Auth;
use Hash;
use Requests;
use Models\Base;
use Models\Merchant;
use Models\User;
use Models\Invitation;
use Models\MerchantDetails;
use Razorpay\Mailers\UserMailer;
use Razorpay\Api\Errors\BadRequestError;

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

        $merchant->password = Hash::make($merchant->password);
        $merchant->saveOrFail();

        $user = User\Entity::createFromMerchant($merchant);
        $user->saveOrFail();
        $user->merchants()->attach($merchant, ['role' => 'owner']);

        $details = array(
            'merchant_id' => $merchant->id,
            'contact_email' => $merchant->email
        );

        MerchantDetails\Entity::createOrFail($details);

        (new UserMailer($merchant))->accountVerification()->queueAndDeliver();

        $slackData = [
            'id'    => $merchant->id,
            'name'  => $merchant->name,
            'email' => $merchant->email
        ];

        $this->slackSignupPost($slackData);

        return [$error, $slackData];
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
                    $user->saveOrFail();
                }
            }
            $merchant->saveOrFail();
        }

        $merchantDetails = $merchant->merchantDetails;
        $merchantDetails->contact_email = $merchant->email;
        $merchantDetails->save();

        return [$error, null];
    }

    public function confirm($token)
    {
        $merchant = Merchant\Entity::getMerchantForConfirmation($token);

        if (is_null($merchant))
        {
            return array('Invalid confirmation token or the merchant is already confirmed.');
        }

        try
        {
            $merchant_api_data = $merchant->generateApiData();
            $this->setApiCredentials();

            $response = $this->api->merchant->create($merchant_api_data);
        }
        catch(BadRequestError $e)
        {
            return array($e->getMessage());
        }

        // Confirm the merchant and associated users (with same email)
        $merchant->confirm();

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

            $user = Auth::user();

            if ($user->once($credentials))
            {
                $user = Auth::user()->get();

                $merchant = $user->currentMerchant;

                if ($user->confirm_token === null)
                {
                    return [['Merchant already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], []];
                }

                (new UserMailer($merchant))->accountVerification()->queueAndDeliver();

                return array(array(),array());
            }
        }

        return array(array('Email or password is invalid.'), array());
    }

    public function fetch($merchant_id)
    {
        $merchant = Merchant\Entity::findOrFail($merchant_id);

        $merchant['tags'] = $merchant->tags;

        return $merchant->toArray();
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
        catch(BadRequestError $e)
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
        catch(BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $key_data);
    }


    /**
     * Get the merchant entity from the gibven merchant id
     *
     * @param  string $merchantId
     * @return \Models\Merchant\Entity
     */
    public function fetchCurrentMerchantForUser($user)
    {
        $merchant = Entity::findOrFail($user->getCurrentMerchantId())->toArray();

        if($user->currentMerchant->primaryOwner()->id == $user->id)
        {
            $merchant['primaryOwner'] = true;
        }
        else
        {
            $merchant['primaryOwner'] = false;
        }

        return $merchant;
    }

    /**
     * Returns all the webhooks
     * @param  string $mode live|test
     */
    public function getWebhooks($mode)
    {
        $merchantId = \Auth::user()->user()->getCurrentMerchantId();

        $this->setApiCredentials($merchantId, $mode);

        $errors = $data = null;

        try
        {
            $data = $this->api->webhook->all()->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function editWebhook($mode, $webhookId, $input)
    {
        $merchantId = \Auth::user()->user()->getCurrentMerchantId();

        $this->setApiCredentials($merchantId, $mode);

        $errors = $data = null;

        try
        {
            $data = $this->api->webhook
                ->fetch($webhookId)
                ->edit($input)
                ->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function createWebhook($mode, $input)
    {
        $merchantId = \Auth::user()->user()->getCurrentMerchantId();

        $this->setApiCredentials($merchantId, $mode);

        $errors = [];
        $data = null;

        try
        {
            // This is just semantics
            // completely equivalent to all() for now
            $data = $this->api->webhook->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    /**
     * Remove the team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function removeTeamMemberForOwner($userId, $user, $input)
    {
        $error = array();

        if($userId == $user->id)
        {
            return array("You cannot remove yourself.");
        }

        $merchant = $user->merchants()->with('users', 'invitations')->where('role','owner')->first();

        if(is_null($merchant))
        {
            return array("We couldn't find the merchant that you own.");
        }

        $merchant->users()->detach($userId);

        return $error;
    }


    /**
     * Update a team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function updateTeamMemberForOwner($userId, $user, $input)
    {
        $error = array();

        if($userId == $user->id)
        {
            $error[] = "You cannot change your role.";
            return array($error, null);
        }

        $validator = (new Merchant\Entity)->validateInput('updateTeamMember',$input);

        if($validator->fails())
        {
            $error = $validator->messages();
            return array($error, null);
        }

        $merchant = $user->merchants()->with('users', 'invitations')->where('role','owner')->first();

        if(is_null($merchant))
        {
            $error[] = "We couldn't find the merchant that you own.";
            return array($error, null);
        }

        $userToUpdate = $merchant->users->find($userId);

        if (is_null($userToUpdate))
        {
            $error[] = "The team member you are looking for does'nt exist";
            return array($error, null);
        }

        $userToUpdate->merchants()->updateExistingPivot(
            $merchant->id, ['role' => $input['role']]
        );

        list($error, $merchant) = (new User\Service)->getOwnedMerchantForUser($user);

        return array($error, $merchant);
    }

    /**
     * Fetches merchant balance
     *
     * @param  string $merchantId Merchant Id
     * @return array contains both test and live balances
     */
    public function fetchMerchantBalance($merchantId)
    {
        $this->setApiCredentials($merchantId, 'test');

        $test = $this->api->merchant->setId($merchantId)->fetchBalance()->toArray();

        $this->setApiCredentials($merchantId, 'live');

        $live = $this->api->merchant->setId($merchantId)->fetchBalance()->toArray();

        return compact('test', 'live');
    }
}
