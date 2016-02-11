<?php

namespace Models\User;

use DB;
use Auth;
use Hash;
use Input;
use Models\Base;
use Models\User;
use Models\Merchant;
use Models\Invitation;
use Models\MerchantDetails;
use Razorpay\Mailers\UserMailer;
use Requests;

class Service extends Base\Service
{
    /**
     * TODO: This function is horribly long. Break it down
     * @param  array  $input [description]
     * @return [type]        [description]
     */
    public function register(array $input)
    {
        $referer = false;

        if (isset($input['ref']))
        {
            $referer = $input['ref'];
            unset($input['ref']);
        }

        $invitationToken = Input::get('invitation', null);

        if ($invitationToken)
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
                $error = ['You already have an account. Log in and accept the invite in you account settings page.'];
                return [$error, null];
            }
            $input['email'] = $email;
        }

        $user = new User\Entity;
        $error = $user->build($input);

        if (!empty($error))
        {
            return array($error, null);
        }

        $user->password = Hash::make($user->password);
        $user->save();

        $businessName = isset($input['business_name']) ? $input['business_name'] : null;

        if ($businessName)
        {
            $merchant = Merchant\Entity::createFromUserWithBusinessName($user,$businessName);
            $merchant->save();

            if ($referer)
            {
                $merchant->tag('ref-'.$referer);
            }

            // This is called for certain special email addresses
            $merchant->setCustomId();

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

        $this->slackSignupPost($data, $referer);

        if(isset($invitation))
        {
            Merchant\Entity::attachUserToMerchantByInvitation($invitation, $user);

            $user->confirm_token = null;
            $user->save();

            Auth::user()->login($user);
            $data['login'] = true;

        }

        return array($error, $data);
    }

    protected function slackSignupPost($slackData, $referer = false)
    {
        if ($_ENV['SLACK_ENABLE'] === true)
        {
            $config = \Config::get('razorpay.sorting_hat');
            $merchantLink = "https://dashboard.razorpay.com/admin#/app/merchants/{$slackData['id']}/detail";
            $message = "[New Signup]($merchantLink)";

            if ($referer)
            {
                $message .= " | REF: $referer";
            }

            $postData = [
                'email'         => $slackData['email'],
                'name'          => $slackData['name'],
                // This is in slack formatting
                'message'       => $message,
                'token'         => $config['token']
            ];

            Requests::post($config['url'], [], $postData);
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

        if (Auth::user()->validate($credentials) === false)
        {
            // Checks credentials but doesn't login the user, throws error if invalid
            $error = ['Email or password is invalid.'];
        }
        else if (Auth::user()->attempt($credentials + ['confirm_token' => null]) === false)
        {
            // Tries to login user if confirmed, throws error if user is not confirmed
            $error = ['not activated'];
            return array($error, null);
        }

        $user = Auth::user()->user();
        if($user && $user->hasMerchants() == false)
        {
            Auth::user()->logout();
            $error[] = "You don't have any associated merchants or a merchant account. Contact razorpay support.";
        }

        return array($error, null);
    }

    public function changePassword(array $input)
    {
        $user = Auth::user()->user();

        if ($user->currentMerchant->isTestAccount())
        {
            return [["Password change forbidden on this account"], null];
        }

        $error = $user->changePassword($input);

        //Any changes in user password
        //are also reflected in the merchants table for now
        DB::transaction(function() use ($user)
        {
            $user->password = Hash::make($user->password);
            $user->save();

            if($user->hasMerchants())
            {
                $merchant = $user->merchants()
                                 ->where('email',$user->email)->first();
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

        if (is_null($merchant))
        {
            $error = ["We couldn't find the merchant you are looking for."];
            return [$error, null];
        }

        return array(null, $merchant);
    }
}
