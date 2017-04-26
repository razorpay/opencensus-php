<?php

namespace App\Merchant;

use Auth;
use Hash;
use Queue;
use Requests;
use App\Base;
use App\User;
use App\Admin;
use App\Merchant;
use App\Invitation;
use App\MerchantDetails;
use App\Mailers\UserMailer;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error as ApiError;
use App\Exceptions\EntityNotFoundException;


class Service extends Base\Service
{
    const INVALID_EMAIL_OR_PASSWORD             = 'Email or password is invalid.';
    const EMAIL_CHANGE_FORBIDDEN                = "Email change forbidden on this account";
    const NAME_CHANGE_FORBIDDEN                 = "Name change forbidden on this account";
    const ROLL_KEY_FORBIDDEN                    = "Roll key forbidden on this account";
    const SELF_REMOVE_FORBIDDEN                 = "You cannot remove yourself.";
    const NO_OWNED_MERCHANT                     = "We couldn't find the merchant that you own.";
    const SUBMERCHANT_NOT_ALLOWED               = "Your account does not have sub-merchant creation privileges. Please contact support@razorpay.com";
    const ACCOUNT_CREATION_NOT_ALLOWED          = "You do not have account creation privileges. Please contact support@razorpay.com";
    const SUBMERCHANT_EMAIL_NOT_UNIQUE          = "Unique email is required to create a new user";
    const NOT_AUTHORIZED_TO_ACCESS_MERCHANT     = "Cannot access merchant";
    const BANK_ACCOUNT_NOT_FOUND                = "Could not find a Bank Account";

    public function __construct()
    {
        $this->currentUser = Auth::user();
    }

    public static function register(User\Entity $user, array $data, $referer = false)
    {
        $merchantData = [
            'name'  =>  $data['business_name'],
            'email' =>  $user->email,
        ];

        $error = (new Merchant\Validator)->validateInput('create', $merchantData)->messages();

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
        }

        return [$error, $merchant];

    }

    /**
     * Registers a sub-merchant account and associates it both ways:
     * 1. Marks the original user as the referral
     * 2. Adds the original user to the new merchant's team
     *
     * This is one scenario where we don't use currentMerchant
     *
     * @param  array  $input
     * @return array
     */
    public function registerSubMerchant(array $input)
    {
        $currentMerchant = $this->currentUser->currentMerchant();

        $isLinkedAccount = \Input::get('account') ?? false;

        if ($isLinkedAccount === true)
        {
            if ($currentMerchant->isMarketplace() === false)
            {
                return [[self::ACCOUNT_CREATION_NOT_ALLOWED], null];
            }
        }
        else
        {
            if ($currentMerchant->isAggregator() === false)
            {
                return [[self::SUBMERCHANT_NOT_ALLOWED], null];
            }
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

            $merchant = Entity::createFromMerchant($currentMerchant, $businessName, $email, $isLinkedAccount);

            try
            {
                $this->createSubMerchantOnApi($merchant, $currentMerchant);
            }
            catch(ApiError $e)
            {
                return [[$e->getMessage()], null];
            }

            $merchant->save();

            if ($isLinkedAccount === false)
            {
                // Finally attach the current user to the new user's team
                $this->currentUser->joinMerchantByIdWithRole($merchant->id, 'owner');
            }

            return [null, $merchant->toArray()];
        }
        else
        {
            return [$error, null];
        }
    }

    public function registerSubMerchantUser(array $input)
    {
        $currentMerchant = $this->currentUser->currentMerchant();

        $currentUser = User\Entity::getUserWithEmail($currentMerchant->email);

        $subMerchant = $this->fetch($input['id']);
        $email = $subMerchant['email'];
        $input['email'] = $email;

        $error = (new Merchant\Validator)
            ->validateInput('create_submerchant_user', $input)->messages();

        if (empty($error))
        {
            if ($email === $currentMerchant->email)
            {
                return [[self::SUBMERCHANT_EMAIL_NOT_UNIQUE], null];
            }

            // checks if the main merchant's owner user is the primary
            // owner of the submerchant account
            if ($currentMerchant->primaryOwner()->ownsMerchant($subMerchant) !== true)
            {
                return [[self::NOT_AUTHORIZED_TO_ACCESS_MERCHANT], null];
            }

            $input['name'] = $subMerchant['name'];
            $input['captcha_disable'] = User\Validator::DISABLE_CAPTCHA_SECRET;

            try
            {
                $user = (new User\Service)->createUserForSubmerchant($input);
                $user->save();

                $userApiData = (new User\Service)->getUserApiData($user);
                (new User\Service)->createUserOnApi($userApiData);

                // Finally attach the new user to the sub merchant
                $user->joinMerchantByIdWithRole($input['id'], 'owner');

                (new User\Service)->attachMerchantUserOnApi($user->id, $input['id'], 'owner');

                return [null, $user->toArray()];
            }

            catch(User\RecoverableException $e)
            {
                $error = [$e->getMessage()];

                return [$error, null];
            }
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

        $response = $this->api
                         ->merchant
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

        $originalEmail = $merchant->email;

        if ($merchant->isTestAccount())
        {
            return [[static::EMAIL_CHANGE_FORBIDDEN], null];
        }

        $error = $merchant->changeEmail($input);

        if (empty($error))
        {
            $this->handleUserEmailChange($merchant, $originalEmail, $input); //This handles different cases of 'user' email change.

            $merchant->saveOrFail();
        }

        return [$error, null];
    }

    /**
     * This handles 3 possible cases when changing user email.
     * 1. There exists a team member with the new email
     *    Here, we swap the roles of the team member(manager) with new email and the original owner
     * 2. There exists a user(not team member) with the new email
     *    Here, we change the original owner to manager and then add the user with new email as owner
     * 3. The new email is unique so far
     *    Here, we just change the email of the original user(owner).
     * @param App\Merchant\Entity $merchant Merchant entity for which email is to be changed
     * @param array $input array containing the new email
     */
    protected function handleUserEmailChange($merchant, $originalEmail, $input)
    {
        if ($merchant->hasUsers())
        {
            $teamUser = $merchant->users()->where('email', $input['email'])->first();

            $existingUser = User\Entity::getUserWithEmail($input['email']);

            $selfUser = $merchant->users()->where('email',$originalEmail)->first();

            //The merchant has a team member with new email
            if ($teamUser !== null)
            {
                //swap roles between user with new email and original owner
                $oldOwner = $merchant->users()->where('role', 'owner')->first();
                $merchant->removeUserById($oldOwner->id);
                $oldOwner->joinMerchantByIdWithRole($merchant->id, 'manager');

                $merchant->removeUserById($teamUser->id);
                $teamUser->joinMerchantByIdWithRole($merchant->id, 'owner');
            }
            //There is an existing user with new email but not a team member
            else if ($existingUser !== null)
            {
                //assign owner to existing user and make existing owner a manager.
                $oldOwner = $merchant->users()->where('role', 'owner')->first();
                $merchant->removeUserById($oldOwner->id);
                $oldOwner->joinMerchantByIdWithRole($merchant->id, 'manager');

                $existingUser->joinMerchantByIdWithRole($merchant->id, 'owner');
            }
            //change email of existing user attached to the merchant as owner
            else if ($selfUser)
            {
                $selfUser->email = $input['email'];
                $selfUser->saveOrFail();
            }
        }
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

    public function createMerchantOnApi($merchantId, $adminId = null)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);

        $merchantApiData = $this->getMerchantApiData($merchant);

        // This is internal auth as of now
        // We need to shift this to some other auth
        $this->setApiCredentials();

        $merchantOnApi = $this->fetchApiEntityIfExists('merchant', $merchantApiData['id']);

        // Only create the merchant if it doesn't exist on the API
        if ($merchantOnApi === null)
        {
            $response = $this->api->merchant->create($merchantApiData);
        }

        return $merchant;
    }

    public function getMerchantApiData($merchant)
    {
        $merchantApiData = $merchant->generateApiData();

        if (! empty($adminId))
        {
            $merchantApiData['admin_id'] = $adminId;
        }

        // Fetch org by hostname and set the orgId in the input
        // so that the merchant can be tagged to the Org
        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = (new Admin\Service)->getOrg($domain);

        $merchantApiData['org_id'] = $org['id'];

        return $merchantApiData;
    }

    public function tagAdmin($merchantOnApi)
    {
        sd($merchantOnApi);
        // $this->api->merchant->tagAdmin();
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
                $user = Auth::user();

                if ($user->getConfirmToken() === null)
                {
                    return [['User already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], null];
                }

                $user->token = $user->getConfirmToken();
                (new UserMailer($user))->accountVerification()->queueAndDeliver();

                return array(array(),array());
            }
        }

        return array(array(static::INVALID_EMAIL_OR_PASSWORD), array());
    }

    public function fetch($merchantId)
    {
        list($error, $merchant) = $this->fetchMerchantFromApi($merchantId);

        $tags = Merchant\Entity::select(['id'])
                                ->with('tagged')
                                ->where('id', $merchantId);

        return array_merge($merchant, $tags->get()->toArray()[0]);
    }

    public function fetchMerchantFromApi($merchantId)
    {
        $this->setApiCredentials();

        $error = $response = null;

        try
        {
            $response = $this->api
                             ->merchant
                             ->fetch($merchantId)
                             ->toArray();
        }
        catch(BadRequestError $e)
        {
            throw new EntityNotFoundException("merchant");
        }

        if (empty($response) === false)
        {
            $response =  [
                'id'           => $response['id'],
                'name'         => $response['name'],
                'email'        => $response['email'],
                'activated'    => (int) $response['activated'],
                'created_at'   => $response['created_at'],
                'updated_at'   => $response['updated_at'],
                'archived_at'  => $response['archived_at'],
                'suspended_at' => $response['suspended_at'],
            ];
        }

        return [$error, $response];
    }

    public function fetchKeysFromApi($merchantId, $mode)
    {
        $this->setApiCredentials(null, $mode);

        $error = $response = null;

        try
        {
            $response = $this->api
                             ->merchant
                             ->fetch($merchantId)
                             ->keys()
                             ->all()
                             ->toArray();
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $response];
    }

    public function createKey($merchantId, $mode)
    {
        $errors = $data = [];

        $this->setApiCredentials(null, $mode);

        try
        {
            $data = $this->api
                         ->merchant
                         ->fetch($merchantId)
                         ->keys()
                         ->create()
                         ->toArray();
        }
        catch(BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function getUsersListWithInvites()
    {
        $merchantId = $this->currentUser
                           ->currentMerchant()
                           ->id;

        $users = Merchant\Entity::with('users', 'invitations')
                    ->where('id', $merchantId)
                    ->first()
                    ->toArray();

        return $users;
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

    public function getInvoices($mode)
    {
        $merchantId = $this->currentUser->currentMerchant()->id;

        $this->setApiCredentials($merchantId, $mode);

        $errors = $data = null;

        try
        {
            $data = $this->api->invoice->all()->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function createInvoice($mode, $input)
    {
        $merchantId = $this->currentUser->currentMerchant()->id;

        $this->setApiCredentials($merchantId, $mode);

        $errors = [];

        $data = null;

        try
        {
            $data = $this->api->invoice->create($input)->toArray();
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function sendInvoiceNotification($mode, $invoiceId, $medium)
    {
        $errors = $data = [];

        $merchantId = $this->currentUser->currentMerchant()->id;

        // Fetches keyId from api for given merchant
        list($errors, $data) = $this->fetchKeysFromApi($merchantId, $mode);

        if ($errors)
        {
            return [$errors, $data];
        }

        if ($data['count'] === 0)
        {

            return [
                ['No keyId found for given merchant with id: ' . $merchantId],
                $data
            ];
        }

        $keyId = $data['items'][0]['id'];

        $this->setApiCredentialsForPublicAuth($keyId);

        try
        {
            $data = $this->api->invoice->sendNotification($invoiceId, $medium)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
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
    public function removeTeamMemberForOwner($userId)
    {
        $error = [];

        if ($userId === $this->currentUser->id)
        {
            return array(static::SELF_REMOVE_FORBIDDEN);
        }

        $this->currentUser->currentMerchant()->users()->detach($userId);

        return $error;
    }

    /**
     * Update a team member on the given merchant.
     *
     * @param  string  $userId
     * @param  array   $input
     * @return \Illuminate\Http\Response
     */
    public function updateTeamMemberForOwner($userId, $input)
    {
        $error = [];

        if ($userId === $this->currentUser->id)
        {
            $error[] = "You cannot change your role.";

            return [$error, null];
        }

        $validator = (new Merchant\Entity)->validateInput('updateTeamMember',$input);

        if ($validator->fails())
        {
            $error = $validator->messages();

            return [$error, null];
        }

        $users = $this->getUserOfMerhantFromApi($this->currentUser->currentMerchant()->id);

        $updatedUser = array_filter($users, function($user) use ($userId)
        {
            return ($users['id'] === $userId);
        });

        if (empty($updatedUser) === true)
        {
            $error[] = "The team member you are looking for doesn't exist";

            return [$error, null];
        }

        $newRole = $input['role'];

        $userToUpdate->merchants()->updateExistingPivot(
            $this->currentUser->currentMerchant()->id, [
                'role' => $newRole
            ]
        );

        (new User\Service)->updateMerchantUserMappingOnApi($userId, $this->currentUser->currentMerchant()->id, $input['role']);

        $merchant = $this->currentUser->getOwnerMerchant();

        if ($merchant === null)
        {
            $error = ["We couldn't find the merchant you are looking for."];

            return [$error, null];
        }

        return [$error, $merchant];
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
        $error = $data = [];

        $tag = "ref-$merchantId";

        $this->setApiCredentials();

        try
        {
            $merchant = $this->api->merchant->fetch($id)->toArray();
        }
        catch (BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        if (empty($error) === true)
        {
            $merchantTags = Merchant\Entity::select(['merchants.id'])
                                    ->with('tagged')
                                    ->where('merchants.id', $merchantId)
                                    ->withAllTags($tags)
                                    ->get()
                                    ->toArray();

            $data = array_merge($merchant, $merchantTags[$merchantId]);

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

    /**
     * This one uses Proxy Auth
     * @return [type]
     */
    public function fetchBankAccount()
    {
        $merchantId = $this->currentUser->currentMerchant()->id;

        $this->setApiCredentials($merchantId);

        $error = $data = null;

        try
        {
            $data = $this->api->merchant->fetchProxyBankAccount()->toArray();
        }
        catch (BadRequestError $e)
        {
            $error = [self::BANK_ACCOUNT_NOT_FOUND];
        }

        return [$error, $data];
    }

    public function savePreSignupDetails($merchantId, $input)
    {
        $error = (new MerchantDetails\Entity)->edit($input, 'preSignup');

        $merchantDetails = [];

        if (empty($error))
        {
            list($error, $merchantDetails) = (new MerchantDetails\Service)->saveDetailsOnAPI($input, $merchantId);

            if (empty($input['business_name']) === false)
            {
                $merchant = Merchant\Entity::findOrFail($merchantId);

                $merchant->edit(['name' => $input['business_name']], 'changeName');

                $merchant->saveOrFail();

                (new Admin\Service)->editName($merchantId, ['name' => $input['business_name']]);

                $user = $merchant->primaryOwner();

                $userEditData = [
                    'contact_mobile' => $input['contact_mobile'],
                    'name'           => $input['contact_name']
                ];

                $user->edit($userEditData, 'preSignup');

                $user->saveOrFail();

                (new User\Service)->editUserOnApi($userEditData, $user->id);

                $zapierData = (new User\Service)->getZapierData($merchant, $input);

                if (!config('razorpay.zapier.mock'))
                {
                    Queue::push('App\User\Service@postToZapier', $zapierData);
                }
            }
        }

        $presignupDetails = (new MerchantDetails\Service)->getPresignupDetails($merchantId, $merchantDetails);

        return [ $error, $presignupDetails];
    }

    public function getPreSignupDetails($merchantId)
    {
        $data = [];

        $merchant = Merchant\Entity::findorfail($merchantId);

        $referrer = $merchant->getReferrerAttribute();

        if (($referrer === null) or
            (Merchant\Entity::verifyUniqueId($referrer) === 0))
        {
            $data = (new MerchantDetails\Service)->getPresignupDetails($merchantId);
        }

        return $data;
    }

    public function getUserOfMerhantFromApi($merchantId)
    {
        $error = $response = [];

        $this->setApiCredentials();

        try
        {
            $response = $this->api->merchant->getUsers($merchantId)->toArray();
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $response];
    }
}
