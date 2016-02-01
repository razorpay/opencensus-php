<?php

namespace Models\Merchant;

use Auth;
use Hash;
use Requests;
use Models\Base;
use Models\Merchant;
use Models\User;
use Models\MerchantDetails;
use Razorpay\Mailers\UserMailer;


class Service extends Base\Service
{
    public function register(array $input)
    {
        $referer = false;

        if (isset($input['ref']))
        {
            $referer = $input['ref'];
            unset($input['ref']);
        }

        $merchant = new Merchant\Entity;
        $error = $merchant->build($input);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $merchant->password = Hash::make($merchant->password);

        // This is called for certain special email addresses
        $merchant->setCustomId();
        $merchant->saveOrFail();

        if ($referer)
        {
            $merchant->tag('ref-'.$referer);
        }

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
            'id'        => $merchant->id,
            'name'      => $merchant->name,
            'email'     => $merchant->email
        ];

        $this->slackSignupPost($slackData, $referer);

        return [$error, $slackData];
    }

    protected function slackSignupPost($slackData, $referer)
    {
        if($_ENV['SLACK_ENABLE'] === true)
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
            return [["Name change forbidden on this account"], null];
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

        if ($merchant === null)
        {
            return array('Invalid confirmation token or the merchant is already confirmed.');
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
        catch(\Razorpay\Api\Errors\BadRequestError $e)
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

            $user = \Auth::user();

            if ($user->once($credentials))
            {
                $user = \Auth::user()->get();

                $merchant = $user->currentMerchant;

                if ($user->confirm_token === null)
                {
                    return [['Merchant already confirmed. You can login ' .
                             '<a href="'.\URL::to('#/access/signin').'">here</a>'], []];
                }

                (new UserMailer($merchant))->accountVerification()->queueAndDeliver();

                return [[], []];
            }
        }

        return [['Email or password is invalid.'], []];
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
     * Fetches merchant balance
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

        return Merchant\Entity::withAnyTag($tag)
            ->get(['id', 'name', 'email', 'activated']);
    }
}
