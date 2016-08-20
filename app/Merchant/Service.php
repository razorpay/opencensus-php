<?php

namespace App\Merchant;

use Auth;
use Hash;
use Requests;

use App\Base;
use App\Merchant;
use App\User;
use App\Invitation;
use App\MerchantDetails;

use Razorpay\Mailers\UserMailer;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error as ApiError;

class Service extends Base\Service
{

    const INVALID_EMAIL_OR_PASSWORD = 'Email or password is invalid.';
    const EMAIL_CHANGE_FORBIDDEN    = "Email change forbidden on this account";
    const NAME_CHANGE_FORBIDDEN     = "Name change forbidden on this account";
    const INVALID_CONFIRMATION_TOKEN= 'Invalid confirmation token or the merchant is already confirmed.';
    const ROLL_KEY_FORBIDDEN        = "Roll key forbidden on this account";
    const SELF_REMOVE_FORBIDDEN     = "You cannot remove yourself.";
    const NO_OWNED_MERCHANT         = "We couldn't find the merchant that you own.";
    const SUBMERCHANT_NOT_ALLOWED   = "Your account does not have sub-merchant creation privileges. Please contact support@razorpay.com";
    const BANK_ACCOUNT_NOT_FOUND    = "Could not find a Bank Account";

    public function __construct()
    {
        $this->currentUser = Auth::user();

        if ($this->currentUser)
        {
            $this->currentMerchant = $this->currentUser->currentMerchant();
        }
    }

    public static function register(User\Entity $user, array $data, $referer = false)
    {
        $merchantData = [
            'name'  =>  $data['business_name'],
            'email' =>  $user->email,
        ];

        $error = (new Merchant\Validator)
            ->validateInput('create', $merchantData)->messages();

        $merchant = null;

        // This makes sure that the User and Merchant entities are in sync for now
        // We can drop the extra fields sometime since they aren't really used
        if (empty($error))
        {
            $merchant = Entity::createFromUser($user, $data);

            // This is called for certain special email addresses
            $merchant->setCustomId();

            if ($referer)
            {
                $merchant->tag('ref-'.$referer);
            }

            $merchant->save();

            $details = [
                'merchant_id'    => $merchant->id,
                'contact_email'  => $merchant->email,
            ];

            if (isset($data['contact_mobile']))
            {
                $details['contact_mobile'] = $data['contact_mobile'];
            }

            MerchantDetails\Entity::createOrFail($details);
        }

        return [$error, $merchant];

    }

    /**
     * Registers a sub-merchant account and associates it both ways:
     * 1. Marks the original user as the referral
     * 2. Adds the original user to the new merchant's team
     *
     * This is one scenario where we don't use currentMerchant
     * @param  string $merchantId Merchant Id
     * @param  array  $input      [description]
     * @return [type]             [description]
     */
    public function registerSubMerchant(array $input)
    {
        $currentMerchant = $this->currentMerchant;

        if(! $currentMerchant->isAggregator())
        {
            return [[self::SUBMERCHANT_NOT_ALLOWED], null];
        }

        $error = (new Merchant\Validator)
            ->validateInput('create_submerchant', $input)->messages();

        if (empty($error))
        {
            $businessName = $input['name'];

            $email = \Input::get('email');

            if (!$email or empty($email))
            {
                $email = $currentMerchant->email;
            }

            $merchant = Entity::createFromMerchant($currentMerchant, $businessName, $email);

            try
            {
                $this->createSubMerchantOnApi($merchant, $currentMerchant);
            }
            catch(ApiError $e)
            {
                return [[$e->getMessage()], null];
            }

            $merchant->save();

            $details = [
                'merchant_id'   => $merchant->id,
                'contact_email' => $merchant->email
            ];

            MerchantDetails\Entity::createOrFail($details);

            // Finally attach the current user to the new user's team
            $this->currentUser->joinMerchantByIdWithRole($merchant->id, 'owner');

            return [null, $merchant->toArray()];
        }
        else
        {
            return [$error, null];
        }
    }

    protected function createSubMerchantOnApi(Entity $merchant, Entity $aggregator)
    {
        $data = [
            'name'  =>  $merchant->name,
            'id'    =>  $merchant->id
        ];

        // Only send the email field if the email is not
        // the same as the aggregator email
        if ($merchant->email !== $aggregator->email)
        {
            $data['email'] = $merchant->email;
        }

        $this->setApiCredentials($aggregator->id);

        $response = $this->api->merchant
            ->createSubMerchant($data)
            ->toArray();

        return $response;
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
            return [[static::EMAIL_CHANGE_FORBIDDEN], null];
        }

        $originalEmail = $merchant->email;
        $error = $merchant->changeEmail($input);

        if (empty($error))
        {
            if ($merchant->hasUsers())
            {
                $user = $merchant->users()->where('email',$originalEmail)->first();
                if ($user)
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

    /**
     * take care when calling this function
     * This is only called from the admin service
     * @param  string $id    Merchant Id
     * @param  array $input  Array with new Merchant Name
     */
    public static function changeName($id, $name)
    {
        $merchant = Merchant\Entity::findorfail($id);

        if ($merchant->isTestAccount())
        {
            return [static::NAME_CHANGE_FORBIDDEN];
        }

        $merchant->changeName($name);
        $merchant->save();
    }

    public function confirm($token)
    {
        $merchant = Merchant\Entity::getMerchantForConfirmation($token);

        if (is_null($merchant))
        {
            return array(static::INVALID_CONFIRMATION_TOKEN);
        }

        return $this->confirmMerchantById($merchant->id);
    }

    public function confirmMerchantById($merchantId)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);

        $merchantApiData = $merchant->generateApiData();

        // This is internal auth as of now
        // We need to shift this to some other auth
        $this->setApiCredentials();

        $merchantOnApi = $this->fetchApiEntityIfExists('merchant', $merchantApiData['id']);

        // Only create the merchant if it doesn't exist on the API
        if ($merchantOnApi === null)
        {
            $response = $this->api->merchant->create($merchantApiData);
        }

        // Confirm the merchant and associated users (with same email)
        // This also calls the mailing list subscription for the user email
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

            $user = Auth::guard('user');

            if ($user->once($credentials))
            {
                $user = Auth::user()->get();

                if ($user->confirm_token === null)
                {
                    return [['User already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], null];
                }

                (new UserMailer($user))->accountVerification()->queueAndDeliver();

                return array(array(),array());
            }
        }

        return array(array(static::INVALID_EMAIL_OR_PASSWORD), array());
    }

    public function fetch($merchant_id)
    {
        $merchant = Merchant\Entity::findOrSoftFail($merchant_id);

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
        if ($this->currentMerchant->isTestAccount()) {
            return [[static::ROLL_KEY_FORBIDDEN], null];
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
     * @return Array with merchant, merchant details
     */
    public function fetchCurrentMerchantForUser($user)
    {
        $merchantId = $user->getCurrentMerchantId();

        $merchant = $this->fetch($merchantId);

        if ($user->currentMerchant->primaryOwner()->id === $user->id)
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
        $merchantId = $this->currentUser->getCurrentMerchantId();

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
        $merchantId = $this->currentUser->getCurrentMerchantId();

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
        $merchantId = $this->currentUser->getCurrentMerchantId();

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

        if ($userId === $user->id)
        {
            return array(static::SELF_REMOVE_FORBIDDEN);
        }

        $merchant = $user->merchants()->with('users', 'invitations')->where('role','owner')->first();

        if (is_null($merchant))
        {
            return array(static::NO_OWNED_MERCHANT);
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

        if ($userId === $user->id)
        {
            $error[] = "You cannot change your role.";
            return array($error, null);
        }

        $validator = (new Merchant\Entity)->validateInput('updateTeamMember',$input);

        if ($validator->fails())
        {
            $error = $validator->messages();
            return array($error, null);
        }

        $merchant = $user->merchants()->with('users', 'invitations')->where('role','owner')->first();

        if (is_null($merchant))
        {
            $error[] = static::NO_OWNED_MERCHANT;
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
     * Uses Proxy Auth on the API
     *
     * @param  string $merchantId Merchant Id
     * @return array contains both test and live balances
     */
    public function fetchMerchantBalance($merchantId)
    {
        $test = $this->fetchProxyMerchantBalance($merchantId, 'test');
        $live = $this->fetchProxyMerchantBalance($merchantId, 'live');

        return compact('test', 'live');
    }

    protected function fetchProxyMerchantBalance($merchantId, $mode)
    {
        try
        {
            $this->setApiCredentials($merchantId, $mode);
            return $this->api->merchant->fetchProxyBalance()->toArray();
        }

        catch(BadRequestError $e)
        {
            return [
                'id'        =>  $merchantId,
                'balance'   =>  0
            ];
        }
    }

    public function fetchReferredMerchants($merchantId)
    {
        $tag = "ref-$merchantId";

        return Merchant\Entity::with(array('merchantDetails' => function($query)
            {
                $query->addSelect(array('merchant_id', 'submitted'));
            }))
            ->withAnyTag($tag)
            ->whereNull('archived_at')
            ->get(['id', 'name', 'activated', 'created_at']);
    }

    public function fetchMerchantConfig($merchantId)
    {
        $this->setApiCredentials($merchantId);
        $error = $data = null;

        try
        {
            $data = $this->api->merchant->fetchConfig()->toArray();
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    /**
     * Makes sure that the hex color is in proper
     * format for the API. Just drops the first
     * character if it is 7 characters in length
     * also, uppercases
     * @param  array $input Input Data
     * @return array Input data
     */
    protected function fixHexColor(array $input)
    {

        if (isset($input['brand_color']))
        {
            $color = $input['brand_color'];
            $len = strlen($color);

            if ($len === 7)
            {
                $color = substr($color, 1);
            }

            $color = strtoupper($color);

            $input['brand_color'] = $color;
        }

        return $input;
    }

    public function updateMerchantConfig($merchantId, $input)
    {
        $this->setApiCredentials($merchantId);
        $error = $data = null;

        $input = $this->fixHexColor($input);

        try
        {
            $data = $this->api->merchant->updateConfig($input)->toArray();
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function updateMerchantLogoConfig($merchantId, $input)
    {
        $this->setApiCredentials($merchantId);
        $error = $data = null;

        if (isset($input['logo']) === false)
        {
            $error = ['Internal Server Error. Contact support for help.'];
            return [$error, $data];
        }

        try
        {
            $data = $this->api->merchant->updateLogoConfig($input)->toArray();
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }
        catch(\Exception $e)
        {
            $error = ['Internal Server Error. Contact support for help.'];
        }

        return [$error, $data];
    }

    /**
     * This one uses Proxy Auth
     * @return [type] [description]
     */
    public function fetchBankAccount()
    {
        $this->setApiCredentials($this->currentMerchant->id);
        $error = $data = null;

        try
        {
            $data = $this->api->merchant->fetchProxyBankAccount()->toArray();
        }
        catch(BadRequestError $e)
        {
            $error = [self::BANK_ACCOUNT_NOT_FOUND];
        }

        return [$error, $data];
    }
}
