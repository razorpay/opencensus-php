<?php

namespace App\User;

use Carbon\Carbon;
use Config;
use DB;
use DrewM\MailChimp\MailChimp;
use Auth;
use Hash;
use Input;
use Session;

use App\Base;
use App\Invitation;
use App\Merchant;
use App\MerchantDetails;
use App\Session as SessionTable;
use App\User;
use App\Lead;
use App\AdminLead;
use App\Generic;

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
            list($invitation, $user) = $this->getInvitationAndUserFromToken($invitationToken);
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

            // For Drip marketing. Where URL has ?email=abc@xyz.com
            $this->updateLeadIfExists($user);
        }

        // These two branches are exclusive
        // You cannot accept an invite and create a merchant account
        // at the same time
        if ($invitationToken)
        {
            $this->attachUserToInvite($user, $invitation);

            $data['login'] = true;
        }
        else
        {
            // See HACKING.md in the root of the repo for a detailed note
            $data = [
                'business_name'  =>  $input['business_name'],
                'contact_mobile' =>  Input::get('contact_mobile', null)
            ];

            list($error, $data) = $this->createMerchantFromUser($user, $data, $referer);

            (new Merchant\Service)->createMerchantOnApi($data['id']);
        }

        // We would never really reach this with an error because we are using exceptions here
        return [$error, $data];
    }

    public function createUserForSubmerchant(array $input)
    {
        // id contains the merchant ID
        // Even though this is ignored by eloquent because we
        // have a generator, nice idea to drop it
        unset($input['id']);
        $user = $this->buildUserEntity($input);

        if ($user->confirm_token !== null)
        {
            $user->token = $user->confirm_token;
            (new UserMailer($user))->accountVerification()->queueAndDeliver();
        }

        unset($user->token);

        return $user;
    }

    public function createLead($input)
    {
        $error = $data = null;

        $lead = new Lead\Entity;

        $error = $lead->build($input);

        if (! empty($error))
        {
            $error = array_values($error);
        }
        else
        {
            $lead->save();
        }

        return [$error, null];
    }

    public function updateLeadIfExists($user)
    {
        // Update Leads as well
        $lead = Lead\Entity::where('email', $user->email)->first();

        if (! empty($lead))
        {
            $lead->registered = true;
            $lead->registered_at = $user->created_at->timestamp;

            $lead->save();
        }
    }

    /**
     * This function is used to confirm a user by email.
     * @param string $email
     */
    public function confirmUserByEmail($email)
    {
        $user = User\Entity::where('email', $email)->first();

        if ($user === null)
        {
            return [['Email is invalid.'], []];
        }

        $user->confirm();

        $this->subscribeToMailingList($user);

        /*
         * For handling the old code.
         * For all those users who have registered earlier using old code and have not confirmed yet.
         * [For them, on dashboard side we have created data. Creating data on Api side]
         */
        $merchant = $user->getOwnerMerchant();

        if ($merchant !== null)
        {
            (new Merchant\Service)->createMerchantOnApi($merchant->id);
        }

        return [null, ['email' => $user->email]];
    }

    /**
     * This function is used to confirm a user by token.
     * @param string $token
     */
    public function confirm($token)
    {
        $user = User\Entity::getUserForConfirmation($token);

        if ($user === null)
        {
            return [[static::INVALID_CONFIRMATION_TOKEN], []];
        }

        $user->confirm();

        $this->subscribeToMailingList($user);

        return [null, ['email' => $user->email]];
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
        $input['email'] = strtolower($input['email']);

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
        if ($user->getConfirmToken() != NULL)
        {
            $user->token = $user->getConfirmToken();

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
        // We want to keep environment conditional checks as late as possible

        if (config('slack.enable'))
        {
            Queue::push('App\User\Service@postToSortingHat', $sortingHatData);
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

    public function getZapierData($merchant, $input)
    {
        // This is the same format we'll set in the google spreadsheet
        $timestamp = Carbon::createFromTimeStamp(time(), "Asia/Kolkata")
            ->format('j/m/Y');

        $userName = $input['contact_name'] ?? '';

        $phoneNumber = $input['contact_mobile'] ?? '';

        $businessType = MerchantDetails\BusinessType::getType($input['business_type']) ?? '';

        $transactionVolume = MerchantDetails\TransactionVolume::getVolume($input['transaction_volume']) ?? '';

        $role = MerchantDetails\Role::getType($input['role']) ?? '';

        $department = MerchantDetails\Department::getType($input['department']) ?? '';

        $referrer = $merchant->referrer ?? '';

        return [
            'id'                    => $merchant->id,
            'email'                 => $merchant->email,
            'individual'            => $userName,
            'name'                  => $merchant->name,
            'ref'                   => $referrer,
            'timestamp'             => $timestamp,
            'contact'               => $phoneNumber,
            'business_type'         => $businessType,
            'transaction_volume'    => $transactionVolume,
            'role'                  => $role,
            'department'            => $department
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

        $headers = [];

        $options = [
            'timeout'   =>  30
        ];

        Requests::post($url, $headers, $data, $options);

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

        $res = null;

        if (empty($error) === false)
        {
            return [['Email or password is invalid.'], null];
        }

        $credentials = array(
            'email'     => $input['email'],
            'password'  => $input['password']
        );

        // Credentials are correct
        // Parameters passed are [creds], $remember, $login
        if (Auth::attempt($credentials, false, false))
        {
            // And user is not confirmed
            if (Auth::attempt($credentials + ['confirm_token' => null], false, true) === false)
            {
                // TODO: Use single error message to avoid info leak
                // @see https://github.com/razorpay/dashboard/issues/216
                $error = ['User email not confirmed. Please click on verification link in email to continue.'];
            }
            else
            {
                // Login the user
            Auth::attempt($credentials, false, true);
            }
        }
        else
        {
            $error = ['Email or password is invalid'];
        }

        if (empty($error))
        {
            $res = [
                'id'    =>  Auth::user()->getAuthIdentifier(),
            ];
        }

        return [$error, $res];
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

            $currentSessionId = Session::getId();
            (new SessionTable\Entity)->deleteAllOtherSessionsForUser($user->getAuthIdentifier(), $currentSessionId);
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
        if ($user->currentMerchant->pivot->role !== 'owner')
        {
            $error = ["We couldn't find the merchant you are looking for."];
            return [$error, null];
        }

        return array(null, $user->currentMerchant);
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
            (new Merchant\Service)->createMerchantOnApi($data['id']);

            $user->confirm();

            $this->subscribeToMailingList($user);
        }

        return [$error, $data];
    }
}
