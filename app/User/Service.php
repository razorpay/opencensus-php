<?php

namespace App\User;

use Carbon\Carbon;
use Config;
use DB;
use DrewM\MailChimp\MailChimp;
use Auth;
use Hash;
use Input;

use App\Base;
use App\Invitation;
use App\Merchant;
use App\MerchantDetails;
use App\User;

use Queue;

use Requests;
use App\Mailers\UserMailer;

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
    protected function getInvitationAndUserFromToken($token)
    {
        list($error, $invitation) = (new Invitation\Service)->getInvitationFromToken($token);

        if ($error)
        {
            // This error is a string
            throw new RecoverableException($error[0]);
        }

        $user = User\Entity::where('email', $invitation->email)->first();

        if ($user)
        {
            return [$invitation, $user];
        }

        return [$invitation, null];
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
        $invitation = $user = null;

        // If we have an invitation token, the user may have created an account
        // in the meantime. $user will be equal to the user with the same email
        // as the invited user
        if ($invitationToken)

        {
            list($invitation, $user)    = $this->getInvitationAndUserFromToken($invitationToken);
            // Since input would be lacking an email in case registration is via
            // the invitation
            $input['email'] = $invitation->email;
        }

        // $user would not be null in a very rare edge case here
        // Which is two subsequent invitations without either being
        // accepted. Once the second one is accepted, this block
        // is ignored and the $user found above will be used
        if (! $user)
        {
            $user = $this->buildUserEntity($input);
        }

        // These two branches are exclusive
        // You cannot accept an invite and create a merchant account
        // at the same time
        if (isset($input['business_name']))
        {
            // See HACKING.md in the root of the repo for a detailed note
            assert(! $invitationToken);

            $data = [
                'business_name' =>  $input['business_name'],
                'contact_mobile' =>  Input::get('contact_mobile', null)
            ];
            list($error, $data) = $this->createMerchantFromUser($user, $data, $referer);
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

        $user->confirm();

        $this->subscribeToMailingList($user);

        Auth::guard('user')->login($user);
    }

    public function subscribeToMailingList(User\Entity $user)
    {
        $data = [
            'name'  =>  $user->name,
            'email' =>  $user->email
        ];

        Queue::push('App\User\Service@postToMailchimp', $data);
    }

    /**
     * This method needs to be public
     * Posts data to mailchimp
     */
    public function postToMailchimp($job, $data)
    {
        $config = Config::get('razorpay.mailchimp');

        $apiKey = $config['api_key'];
        $listId = $config['list_id'];

        // Mock can be false or null for falsy cases
        // Unset mock is considered true
        if (! $config['mock'])
        {
            $mailchimp = new MailChimp($apiKey);
            // TODO: Break down the name in 2 parts and send
            // LNAME separately
            $mailchimp->post("lists/$listId/members", [
                'email_address' => $data['email'],
                'status'        => 'subscribed',
                'merge_fields'  => $this->breakName($data['name']),
            ]);
        }

        $job->delete();
    }

    protected function breakName($name)
    {
        $data = ['FNAME' => $name];

        $index = strpos($name, ' ');

        if ($index !== false)
        {
            $data['FNAME'] = substr($name, 0, $index);
            $data['LNAME'] = substr($name, $index + 1);
        }

        return $data;
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
            $error = array_values($error);
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
    protected function createMerchantFromUser(User\Entity $user, array $data, $referer = false)
    {
        list($error, $merchant) = Merchant\Service::register($user, $data, $referer);

        if (! empty($error))
        {
            return [$error, null];
        }

        $user->merchants()->attach($merchant, ['role' => 'owner']);

        // Only send the confirmation email if the user isn't already confirmed
        if ($user->confirm_token != NULL)
        {
            (new UserMailer($user))->accountVerification()->queueAndDeliver();
        }

        return [null, $this->signupPost($merchant, $user, $referer)];
    }

    /**
     * Makes a call to sorting hat to post on Slack that a new merchant
     * signed up
     */
    protected function signupPost($merchant, $user, $referer = '')
    {
        $phoneNumber = Input::get('contact_mobile', null);
        $sortingHatData = $this->getSortingHatData($merchant, $user, $referer, $phoneNumber);
        $zapierData = $this->getZapierData($merchant, $user, $referer, $phoneNumber);


        // We want to keep environment conditional checks as late as possible

        if (config('slack.enable'))
        {
            Queue::push('App\User\Service@postToSortingHat', $sortingHatData);
            Queue::push('App\User\Service@postToZapier', $zapierData);
        }

        // These are displayed on the frontend
        return [
            'id'    =>  $merchant->id,
            'name'  =>  $merchant->name,
            'email' =>  $user->email
        ];
    }

    protected function getSortingHatData($merchant, $user, $referer, $phoneNumber)
    {
        $merchantLink = "https://dashboard.razorpay.com/admin#/app/merchants/{$merchant->id}/detail";
        $message = "[New Signup]($merchantLink) as {$user->name}";

        if ($referer)
        {
            $message .= " | REF: $referer";
        }

        if ($phoneNumber)
        {
            $message .= " | [Call - {$phoneNumber}](tel:$phoneNumber)";
        }

        return [
            'id'            => $merchant->id,
            'email'         => $user->email,
            'name'          => $merchant->name,
            'message'       => $message,
            'token'         => Config::get('razorpay.sorting_hat.token')
        ];
    }

    protected function getZapierData($merchant, $user, $referer, $phoneNumber)
    {
        // This is the same format we'll set in the google spreadsheet
        $timestamp = Carbon::createFromTimeStamp(time(), "Asia/Kolkata")
            ->format('j/m/Y');

        return [
            'id'            => $merchant->id,
            'email'         => $user->email,
            'individual'    => $user->name,
            'name'          => $merchant->name,
            'ref'           => $referer ? $referer : '',
            'timestamp'     => $timestamp,
            'contact'       => $phoneNumber? $phoneNumber : ''
        ];
    }

    /**
     * This method needs to be public because it's called
     * on a Queue
     * @param  array $data data to send to Sorting Hat
     */
    public function postToSortingHat($job, $data)
    {
        $url = Config::get('razorpay.sorting_hat.url');
        Requests::post($url, [], $data);

        $job->delete();
    }

    public function postToZapier($job, $data)
    {
        $url = Config::get('razorpay.zapier.signups');
        Requests::post($url, [], $data);

        $job->delete();
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

        // Credentials are correct
        if (Auth::attempt($credentials))
        {
            // And user is not confirmed
            if (Auth::attempt($credentials + ['confirm_token' => null]) === false)
            {
                $error = ['User account not confirmed'];
            }
        }
        else
        {
            $error = ['Email or password is invalid'];
        }

        return [$error, null];
    }

    public function changePassword(array $input)
    {
        $user = Auth::user();

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

    public function upgradeUserToMerchant($input)
    {
        $user = Auth::user();

        $error = (new User\Validator)->validateInput('upgrade', $input)->messages();

        if (! empty($error))
        {
            return [$error, null];
        }

        $data = [
            'business_name' =>  $input['business_name']
        ];

        // We don't have a referrer for the upgrade
        list($error, $data) = $this->createMerchantFromUser($user, $data);

        if (empty($error))
        {
            // $data['id'] is the newly created merchant Id
            // This confirmation creates the Merchant Account on the API Side
            // Make sure that the id is not submitted ever by the user
            (new Merchant\Service)->confirmMerchantById($data['id']);
        }

        return [$error, $data];
    }
}
