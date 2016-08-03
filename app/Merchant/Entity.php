<?php

namespace App\Merchant;

use Mail;
use Uuid;

use App\Base;
use App\User;
use App\Invitation;

class Entity extends Base\Entity
{
    use \Conner\Tagging\Taggable;

    public $incrementing = false;

    protected $table = 'merchants';

    protected $hidden = array('password', 'remember_token');

    protected $fillable = array(
        'id',
        'name',
        'email',
        'password',
        'confirm_token',
        'activated',
        'archived_at'
    );

    protected $appends = ['referrer', 'tags'];

    const ID_LENGTH = 14;

    protected static $generators = array('id', 'confirm_token');

    protected static $test_merchant_ids = array(
        '10000000000000',
        '100DemoAccount'
    );

    const AMEX  = 'AMEX';
    const DICL  = 'DICL';
    const DISC  = 'DISC';
    const EMI   = 'EMI';
    const JCB   = 'JCB';
    const MAES  = 'MAES';
    const MC    = 'MC';
    const RUPAY = 'RUPAY';
    const VISA  = 'VISA';
    const UNP   = 'UNP';
    const CARD  = 'CARD';
    const NETBANKING = 'NETBANKING';
    const WALLET  = 'WALLET';
    const UNKNOWN = 'UNKNOWN';

    const AGGREGATOR = 'Aggregator';

    protected static $api_mappings = array(
        'American Express'  =>  self::AMEX,
        'Diners Club'       =>  self::DICL,
        'Discover'          =>  self::DISC,
        'JCB'               =>  self::JCB,
        'Maestro'           =>  self::MAES,
        'MasterCard'        =>  self::MC,
        'RuPay'             =>  self::RUPAY,
        'Unknown'           =>  self::UNKNOWN,
        'Visa'              =>  self::VISA,
        'Union Pay'         =>  self::UNP,
        'card'              =>  self::CARD,
        'netbanking'        =>  self::NETBANKING,
        'wallet'            =>  self::WALLET,
        'emi'               =>  self::EMI,
        'Unknown'           =>  self::UNKNOWN
    );

    /**
     * Generate the user instance from the merchant instance
     *
     * @param Models\User\Entity $user
     * @return App\Merchant\Entity $merchant
     */
    public static function createFromUser(User\Entity $user, $data)
    {
        $merchant = new static();

        $merchant->id = Uuid::generate();
        $merchant->name = $data['business_name'];
        $merchant->email = $user->email;

        $merchant->password = $user->password;
        $merchant->confirm_token = $user->confirm_token;

        return $merchant;
    }

    /**
     * Create sub-merchant accounts
     * @param  App\Merchant\Entity $aggregator Aggregator Merchant Entity
     * @param  string          $businessName   Merchant Business Name
     * @return App\Merchant\Entity Sub Merchant Entity
     */
    public static function createFromMerchant(Entity $aggregator, $businessName, $email)
    {
        $merchant = new static();

        $merchant->id       = Uuid::generate();
        $merchant->name     = $businessName;
        $merchant->email    = $email;

        // This password is never really used anywhere
        // We just have it for legacy reasons till we drop
        // the field entirely from our database
        // Logins run on top of User\password.
        $merchant->password = "invalid_password";

        // We mark the user as confirmed
        $merchant->confirm_token = null;

        // We tag the merchant as referred from the original merchant as well
        $merchant->tag("ref-{$aggregator->id}");

        return $merchant;
    }

    /**
     * An aggregator is defined as a merchant
     * Which can create other merchants without sending
     * them confirmation emails. All these merchants are also
     * created with the same email address
     * return boolean
     */
    public function isAggregator()
    {
        return in_array(self::AGGREGATOR, $this->tagNames());
    }

    /**
     * Take care while calling this method
     *
     * @param array $input array with new email address
     */
    public function changeEmail($input)
    {
        return $this->edit($input, 'changeEmail');
    }

    /**
     * Take care while calling this method
     * @param array $input array with new name
     */
    public function changeName($name)
    {
        return $this->edit([
            'name' => $name
        ], 'changeName');
    }

    /**
     * Determine if the merchant has any users.
     *
     * @return bool
     */
    public function hasUsers()
    {
        return count($this->users) > 0;
    }

    /**
     * Get all of the merchants for the user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllMerchantsForUser($user)
    {
        $merchants = $user->merchants()->with('owner')->get();

        foreach ($merchants as $merchant)
        {
            $merchant->owner->setVisible(['name']);
        }

        return $merchants;
    }

    /**
     * Get the owners of the merchant.
     */
    public function owners()
    {
        return $this->users()->where('role','owner')->get();
    }

    /**
     * Get the primary owner of the merchant.
     */
    public function primaryOwner()
    {
        return $this->owners()->first();
    }

    /**
     * Get all of the users that belong to the merchant.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function users()
    {
        return $this->belongsToMany(
            User\Entity::class, 'merchant_users', 'merchant_id', 'user_id'
        )->withPivot('role');
    }

    /**
     * Get all of the pending invitations for the merchant.
     */
    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class)
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Invite a user to the merchants by e-mail address.
     *
     * @param  string  $email
     * @return App\Merchant\Entity
     */
    public function inviteUserByEmailWithRole($email, $role)
    {
        // First try to find if a user account exists for the user
        $invitedUser = (new User\Entity)->where('email', $email)->first();

        $invitation = $this->invitations()->create([
            'user_id' => $invitedUser ? $invitedUser->id : null,
            'email' => $email,
            'token' => str_random(40),
            'role' => $role,
        ]);

        return $invitation;
    }

    /**
     * Attach a user to a given merchant based on their invitation.
     *
     * @param  App\Invitation\Entity  $invitation
     * @param  Models\User\Entity  $user
     * @return void
     */
    public static function attachUserToMerchantByInvitation(Invitation\Entity $invitation, User\Entity $user)
    {
        $user->joinMerchantByIdWithRole($invitation->merchant->id, $invitation->role);
        $user->switchToMerchant($invitation->merchant);

        $invitation->delete();
    }

    /**
     * Remove a user from the merchant by their ID.
     *
     * @param  int  $userId
     * @return void
     */
    public function removeUserById($userId)
    {
        $this->users()->detach([$userId]);

        $removedUser = (new User\Entity)->find($userId);

        if($removedUser)
        {
            $removedUser->refreshCurrentMerchant();
        }
    }

    /**
     * Generates UUid ID
     */
    public function generateId()
    {
        $this->setAttribute('id', Uuid::generate());
    }

    /**
     * Generates Confirmation token
     */
    public function generateConfirmToken()
    {
        $this->setAttribute(
            'confirm_token',
            bin2hex(openssl_random_pseudo_bytes(32/2)));
    }

    public function transactions()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Transaction'
        );
    }

    public function merchantDetails()
    {
        return $this->hasOne('App\MerchantDetails\Entity');
    }

    public function hasInvitiationForEmail($email)
    {
        return $this->invitations()
                        ->where('email', $email)
                        ->exists();
    }

    public static function getAggregations($data, $mode)
    {
        $data = \DB::table('aggregations')
                    ->where('merchant_id','=',$data['merchant_id'])
                    ->where('resource','=',$data['resource'])
                    ->where('mode','=',$mode)
                    ->first();
        return $data;
    }

    public static function getAllAggregations($mode, $resource, $sort)
    {
        $data = \DB::table('aggregations')
                    ->where('resource','=',$resource)
                    ->where('mode','=',$mode)
                    ->where('total_amount' , '>', 0)
                    ->orderBy($sort, 'DESC')
                    ->get();
        return $data;
    }

    public static function getPaymentAggregations($data, $mode)
    {
        $data = \DB::table('payment_aggregations')
                    ->where('merchant_id','=',$data['merchant_id'])
                    ->where('mode','=',$mode)
                    ->first();
        return $data;
    }

    public static function createAggregations($data, $mode)
    {
        $obj = array(
            'merchant_id'           =>  $data['merchant_id'],
            'total_amount'          =>  0,
            'successful_txn_count'  =>  0,
            'txn_count'             =>  0,
            'created_at'            =>  $data['updated_at'],
            'updated_at'            =>  $data['updated_at'],
            'resource'              =>  $data['resource'],
            'mode'                  =>  $mode
        );

        \DB::table('aggregations')->insert($obj);

        return static::getAggregations($data, $mode);
    }

    public static function createPaymentAggregations($data, $mode)
    {
        $obj = array(
            'merchant_id'           =>  $data['merchant_id'],
            'created_at'            =>  $data['updated_at'],
            'updated_at'            =>  $data['updated_at'],
            'mode'                  =>  $mode
        );

        \DB::table('payment_aggregations')->insert($obj);

        return static::getPaymentAggregations($data, $mode);
    }

    public static function updateAggregations($data, $merchant_details, $mode)
    {
        $obj = array(
            'total_amount'          =>  (int)$data['amount'] + (int)$merchant_details->total_amount,
            'successful_txn_count'  =>  (int)$merchant_details->successful_txn_count + 1,
            'txn_count'             =>  (int)$merchant_details->txn_count + 1,
            'updated_at'            =>  $data['created_at']
        );
        \DB::table('aggregations')
            ->where('merchant_id','=',$data['merchant_id'])
            ->where('resource','=',$data['resource'])
            ->where('mode', '=', $mode)
            ->update($obj);
    }

    public static function updatePaymentAggregations($data, $merchant_details, $mode)
    {
        $obj = [];

        $method = static::$api_mappings[$data['method']];

        $currentCount = $merchant_details->$method;

        $obj[static::$api_mappings[$data['method']]] =  $currentCount + 1;

        if(isset($data['network']))
        {
            $network = static::$api_mappings[$data['network']];

            $obj[static::$api_mappings[$data['network']]] = $currentCount + 1;
        }

        \DB::table('payment_aggregations')
            ->where('merchant_id','=',$data['merchant_id'])
            ->where('mode', '=', $mode)
            ->update($obj);
    }

    public static function getMerchantForConfirmation($token)
    {
        return static::where('confirm_token', '=', $token)->first();
    }

    /**
     * Generates data required for merchant registration with the API
     */
    public function generateApiData()
    {
        return array(
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email
        );
    }

    /**
     * Generates data required for merchant confirmation email
     */
    public function generateEmailData()
    {
        return array(
            'name'          => $this->name,
            'email'         => $this->email,
            'confirm_token' => $this->confirm_token
        );
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->getAttribute('remember_token');
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->setAttribute('remember_token', $value);
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get the e-mail address where password reminders are sent.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    public function isTestAccount()
    {
        return in_array($this->id, static::$test_merchant_ids);
    }

    public function isActive()
    {
        return ((int)$this->activated === 1);
    }

    public function confirm()
    {
        $this->confirm_token = null;
        $email = $this->email;
        $this->saveOrFail();

        // This is only to make sure that the user and merchants are in sync
        // for now. We will drop the method from Merchant\Entity and shift it
        // to User\Entity going ahead.
        if($this->hasUsers())
        {
            $user = $this->users()->where('email', $email)->first();
            if($user)
            {
                $user->confirm();
                (new User\Service)->subscribeToMailingList($user);
            }
        }
    }

    protected function getTagsAttribute()
    {
        return $this->tagNames();
    }

    public function getReferrerAttribute()
    {
        $tags = $this->getTagsAttribute();

        foreach ($tags as $tag)
        {
            $tag = strtolower($tag);
            if (substr($tag, 0,4) === 'ref-')
            {
                return substr($tag, 4);
            }
        }

        return null;
    }

    public function setCustomId()
    {
        switch ($this->email)
        {
            case 'shk@razorpay.com':
                $this->setAttribute('id', '100000Razorpay');
                break;
        }
    }

    public function archive()
    {
        $this->archived_at = time();
        $this->save();
    }
}
