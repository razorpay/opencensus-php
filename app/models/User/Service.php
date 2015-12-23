<?php

namespace Models\User;

use DB;
use Auth;
use Hash;
use Models\Base;
use Models\User;
use Models\Merchant;
use Models\Invitation;
use Models\MerchantDetails;
use Razorpay\Mailers\UserMailer;

class Service extends Base\Service
{
    public function register(array $input)
    {
        $user = new User\Entity;

        $invitationToken = isset($input['invitation']) ? $input['invitation'] : null;

        if($invitationToken)
        {
            User\Validator::$createRules['email'] = 'email|unique:merchants';
            
            list($error, $invitation) = (new Invitation\Service)->getInvitationFromToken($invitationToken);

            if($error)
            {
                return array($error, null);
            }
            $email = $invitation->email;
            $user = User\Entity::where('email',$email)->first();
            if($user)
            {
                $error = array('You already have an account. Log in and accept the invite in you account settings page.');
                return array($error, null);
            }
            $input['email'] = $email;
        }

        $error = $user->build($input);

        if (!empty($error))
        {
            return array($error, null);
        }

        $user->password = Hash::make($user->password);
        $user->save();

        $businessName = isset($input['business_name']) ? $input['business_name'] : null;

        if($businessName)
        {
            $merchant = Merchant\Entity::createFromUserWithBusinessName($user,$businessName);
            $merchant->save();
            
            $user->merchants()->attach($merchant, ['role' => 'owner']);
            
            $details = array('merchant_id' => $merchant->id,'contact_email' => $merchant->email);

            MerchantDetails\Entity::createOrFail($details);
            
            (new UserMailer($user))->accountVerification()->queueAndDeliver();
        }

        $data = array(
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email
        );

        $this->slackSignupPost($data);

        if(isset($invitation)) 
        {
            Merchant\Entity::attachUserToMerchantByInvitation($invitation, $user);
            Auth::user()->login($user);
            $data['login'] = true;

        }

        return array($error, $data);
    }

    protected function slackSignupPost($slackData)
    {
        if($_ENV['SLACK_ENABLE'] === true)
        {
            $userLink = "https://dashboard.razorpay.com/admin#/app/merchants/{$slackData['id']}/detail";

            $postData = [
                'email'         => $slackData['email'],
                'name'          => $slackData['name'],
                // This is in slack formatting
                'message'       => "[New Signup]($userLink)"
            ];

            Requests::post('https://sorting-hat-slack.herokuapp.com/',[] , $postData);
        }
    }

    public function login(array $input)
    {
        $error = (new Validator)->validateInput('login', $input)->messages();

        if (empty($error) === false)
        {
            return [['Email or password is invalid.'], null];
        }

        $credentials = array(
            'email'     => $input['email'],
            'password'  => $input['password']
        );

        if(Auth::user()->validate($credentials) === false)
        {
            // Checks credentials but doesn't login the user, throws error if invalid
            $error = ['Email or password is invalid.'];
        }
        else if (Auth::user()->attempt($credentials + array('confirm_token' => null)) === false)
        {
            // Tries to login user if confirmed, throws error if user is not confirmed
            $error = ['not activated'];
        }

        return [$error, null];
    }

    public function fetch($user_id)
    {
        $user = Entity::findOrFail($user_id)->toArray();

        return $user;
    }

    public function changePassword(array $input)
    {
        $user = Auth::user()->user();

        if ($user->currentMerchant->isTestAccount()) {
            return [["Password change forbidden on this account"], null];
        }

        $error = $user->changePassword($input);
        
        DB::transaction(function() use ($user)
        {
            $user->password = Hash::make($user->password);
            $user->save();

            if($user->hasMerchants())
            {
                $merchant = $user->merchants()->where('email',$user->email)->first();
                if($merchant)
                {
                    $merchant->password = $user->password;
                    $merchant->save();
                }
            }
        });
        
        return [$error, null];
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return \Illuminate\Http\Response
     */
    public function switchCurrentMerchantForUser($merchantId, $user)
    {
        $merchant = $user->merchants()->find($merchantId);

        if($merchant)
        {
            $user->switchToMerchant($merchant);
            return array();
        }

        return array("Couldn't find the merchant you are looking for.");
    }

    /**
     * Get all the merchants for the given user.
     *
     * @param  \Models\User\Entity  $user
     * @return \Models\Merchant\Entity[]
     */
    public function getAllMerchantsForUser($user)
    {
        $error = array();

        $merchants = $user->merchants()->get();

        if($merchants->count() < 0)
        {
            $error = array('There are no merchants.');
            return array($error, null);
        }

        $currentMerchantId = $user->getCurrentMerchantId();

        foreach ($merchants as $merchant) 
        {
            $merchant->current = $merchant->id == $currentMerchantId;
            $merchant->setVisible(['id','name','email','current']);
        }

        return array(null, $merchants);
    }

    /**
     * Get the current merchant for the authenticated user.
     *
     * @param  \Models\User\Entity  $user
     * @return \Illuminate\Http\Response
     */
    public function getOwnedMerchantForUser($user)
    {
        $merchant = $user->merchants()->with('users', 'invitations')->where('role','owner')->first();
        
        if(is_null($merchant))
        {
            $error = array("We couldn't find the merchant you are looking for.");
            return array($error, null);
        }

        return array(null, $merchant);
    }
}
