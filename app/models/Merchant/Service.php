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

    const INVALID_EMAIL_OR_PASSWORD = 'Email or password is invalid.';
    const EMAIL_CHANGE_FORBIDDEN    = "Email change forbidden on this account";
    const NAME_CHANGE_FORBIDDEN     = "Name change forbidden on this account";
    const INVALID_CONFIRMATION_TOKEN= 'Invalid confirmation token or the merchant is already confirmed.';
    const ROLL_KEY_FORBIDDEN        = "Roll key forbidden on this account";
    const SELF_REMOVE_FORBIDDEN     = "You cannot remove yourself.";
    const NO_OWNED_MERCHANT         = "We couldn't find the merchant that you own.";
    const SUBMERCHANT_NOT_ALLOWED   = "Your account does not have sub-merchant creation privileges. Please contact support@razorpay.com";

    const MASTER_MERCHANT = 'Mastermerchant';

    public function __construct()
    {
        $this->currentUser = Auth::user()->user();
        $this->currentMerchant = $this->currentUser->currentMerchant;
    }

    public static function register(User\Entity $user, $businessName, $referer = false)
    {
        $data = [
            'name'  =>  $businessName,
            'email' =>  $user->email,
        ];

        $error = (new Merchant\Validator)
            ->validateInput('create', $data)->messages();

        $merchant = null;

        // This makes sure that the User and Merchant entities are in sync for now
        // We can drop the extra fields sometime since they aren't really used
        if (empty($error))
        {
            $merchant = Entity::createFromUser($user, $businessName);

            // This is called for certain special email addresses
            $merchant->setCustomId();

            if ($referer)
            {
                $merchant->tag('ref-'.$referer);
            }

            $merchant->save();

            $details = [
                'merchant_id'   => $merchant->id,
                'contact_email' => $merchant->email
            ];

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
        $masterMerchant = $this->currentMerchant;

        if(! $masterMerchant->isMasterMerchant())
        {
            return [[self::SUBMERCHANT_NOT_ALLOWED], null];
        }

        $error = (new Merchant\Validator)
            ->validateInput('create_submerchant', $input)->messages();

        // We are re-using the merchant email here
        $data = [
            'name'  =>  $input['name'],
            'email' =>  $masterMerchant->email,
        ];

        if (empty($error))
        {
            $businessName = $input['name'];
            $merchant = Entity::createFromMerchant($masterMerchant, $businessName);

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
    public function changeName($id, $input)
    {
        $merchant = Merchant\Entity::findorfail($id);

        if ($merchant->isTestAccount()) {
            return [[static::NAME_CHANGE_FORBIDDEN], null];
        }

        $error = $merchant->changeName($input);

        if (empty($error))
        {
            $merchant->save();
        }

        return [$error, null];
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

        try
        {
            $merchantOnApi = $this->fetchApiEntityIfExists('merchant', $merchantApiData['id']);

            // Only create the merchant if it doesn't exist on the API
            if ($merchantOnApi === null)
            {
                $response = $this->api->merchant->create($merchantApiData);
            }
        }

        catch(BadRequestError $e)
        {
            return array($e->getMessage());
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

            $user = Auth::user();

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

    public function fetchReferredMerchants($merchantId)
    {
        $tag = "ref-$merchantId";

        return Merchant\Entity::with(array('merchantDetails' => function($query)
            {
                $query->addSelect(array('merchant_id', 'submitted'));
            }))
            ->withAnyTag($tag)
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

    public function updateMerchantConfig($merchantId, $input)
    {
        $this->setApiCredentials($merchantId);
        $error = $data = null;

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
}
