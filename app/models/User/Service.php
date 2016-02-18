<?php

namespace Models\User;

use Config;
use DB;
use Auth;
use Hash;
use Input;

use Models\Base;
use Models\Invitation;
use Models\Merchant;
use Models\MerchantDetails;
use Models\User;

use Requests;
use Razorpay\Mailers\UserMailer;

class Service extends Base\Service
{
    const ACCOUNT_ALREADY_EXISTS = 'You already have an account. Log in and accept the invite in you account settings page.';

    protected function getRef(array &$input)
    {
        $referer = false;

        // Unset because we fail the build step otherwise
        if (isset($input['ref']))
        {
            $referer = $input['ref'];
            unset($input['ref']);
        }

        return $referer;
    }

    /**
     * Gets the email for a given invitation token
     * Throws a recoverable exception otherwise
     * @param  string $token invitation token
     * @return string $email
     */
    protected function getInvitationFromToken($token)
    {
        list($error, $invitation) = (new Invitation\Service)->getInvitationFromToken($token);

        if ($error)
        {
            // This error is a string
            throw new RecoverableException($error);
        }

        $user = User\Entity::where('email', $invitation->email)->first();

        if ($user)
        {
            throw new RecoverableException(static::ACCOUNT_ALREADY_EXISTS);
        }

        return $invitation;
    }

    /**
     * Main registration method. Contains most business logic for deciding what to
     * register and as what (user|merchant) and with what details. See
     * HACKING.md for a bit more details.
     *
     * @param  array  $input [description]
     */
    public function register(array $input)
    {
        $data = [];
        $error = null;
        $referer = $this->getRef($input);

        $invitationToken = Input::get('invitation', null);

        if ($invitationToken)
        {
            $invitation     = $this->getInvitationFromToken($invitationToken);
            $input['email'] = $invitation->email;
        }

        $user = $this->buildUserEntity($input);

        // These two branches are exclusive
        // You cannot accept an invite and create a merchant account
        // at the same time
        if (isset($input['business_name']))
        {
            // See HACKING.md in the root of the repo for a detailed note
            assert(! $invitationToken);
            $data = $this->createMerchantFromUser($user, $input['business_name'], $referer);
        }

        elseif ($invitationToken)
        {
            $this->attachUserToInvite($user, $invitation);
            $data['login'] = true;
        }

        // We would never really reach this with an error because we are using exceptions here
        return [$error, $data];
    }

    /**
     * Attach a user to a merchant using an invitation
     */
    protected function attachUserToInvite(User\Entity $user, Invitation\Entity $invitation)
    {
        Merchant\Entity::attachUserToMerchantByInvitation($invitation, $user);

        $user->confirm_token = null;
        $user->save();

        Auth::user()->login($user);
    }

    /**
     * Builds a new user entity from the input
     * @param  array  $input array build for the user entity
     * @return Models\User\Entity
     */
    protected function buildUserEntity(array $input)
    {
        // Now we can build a new user using the entire input
        $user = new User\Entity;
        $error = $user->build($input);

        if (! empty($error))
        {
            throw new RecoverableException($error[0]);
        }

        $user->password = Hash::make($user->password);
        $user->save();

        return $user;
    }

    /**
     * Create a merchant entity from a user entity
     * @param  Models\User\Entity $user
     * @param  string $businessName business name
     * @param  string $referer      Could be false as well
     * @return array containing some minor details
     */
    protected function createMerchantFromUser(User\Entity $user, $businessName, $referer)
    {
        $merchant = Merchant\Service::register($user, $businessName, $referer);

        $user->merchants()->attach($merchant, ['role' => 'owner']);

        (new UserMailer($user))->accountVerification()->queueAndDeliver();

        return $this->slackSignupPost($merchant, $user, $referer);
    }

    /**
     * Makes a call to sorting hat to post on Slack that a new merchant
     * signed up
     */
    protected function slackSignupPost($merchant, $user, $referer = false)
    {
        $data = [
            'id'        => $merchant->id,
            'name'      => $merchant->name,
            'email'     => $user->email,
            'user_name' => $user->name,
        ];

        $config = Config::get('razorpay.sorting_hat');
        $merchantLink = "https://dashboard.razorpay.com/admin#/app/merchants/{$data['id']}/detail";
        $message = "[New Signup]($merchantLink) as {$data['user_name']}";

        if ($referer)
        {
            $message .= " | REF: $referer";
        }

        $postData = [
            'email'         => $data['email'],
            'name'          => $data['name'],
            'message'       => $message,
            'token'         => $config['token']
        ];

        // We want to keep environment conditional checks as late as possible
        if ($_ENV['SLACK_ENABLE'] === true)
        {
            /**
             * TODO: Move this to queue perhaps
             */
            Requests::post($config['url'], [], $postData);
        }

        return $data;
    }

    /**
     * TODO: Cleanup this method
     * @param  array  $input [description]
     * @return [type]        [description]
     */
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

        if ($user->currentMerchant and $user->currentMerchant->isTestAccount())
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
    public function switchCurrentMerchantForUser($merchantId, User\Entity $user)
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
     * @param  User\Entity  $user
     * @return Merchant\Entity[]
     */
    public function getAllMerchantsForUser(User\Entity $user)
    {
        $error = array();

        $merchants = $user->merchants()->get();

        if($merchants->count() < 0)
        {
            $merchants = [];
        }
        else
        {
            $currentMerchantId = $user->getCurrentMerchantId();

            foreach ($merchants as $merchant)
            {
                // Set current to a boolean
                $merchant->current = ($merchant->id == $currentMerchantId);
                $merchant->setVisible(['id','name','email','current']);
            }
        }

        return $merchants;
    }

    /**
     * Get the current merchant for the authenticated user.
     *
     * @param  User\Entity  $user
     * @return \Illuminate\Http\Response
     */
    public function getOwnedMerchantForUser(User\Entity $user)
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
