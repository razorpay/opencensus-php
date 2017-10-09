<?php

namespace App\Merchant;

use Mail;
use Uuid;
use App\Base;
use App\User;
use App\Invitation;

class Entity extends Base\Entity
{
    public $incrementing = false;

    protected $table = 'merchants';

    protected $hidden = ['remember_token'];

    protected $fillable = array(
        'id',
        'name',
        'email',
        'activated',
        'archived_at',
        'suspended_at'
    );

    const ID_LENGTH = 14;
    const EMAIL     = 'email';

    protected static $generators = array('id');

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
    const UPI   = 'UPI';
    const NETBANKING = 'NETBANKING';
    const WALLET  = 'WALLET';
    const UNKNOWN = 'UNKNOWN';

    const AGGREGATOR    = 'Aggregator';
    const MARKETPLACE   = 'Marketplace';

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
        'upi'               =>  self::UPI,
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

        return $merchant;
    }

    /**
     * Create sub-merchant accounts
     * @param  App\Merchant\Entity $aggregator Aggregator Merchant Entity
     * @param  string          $businessName   Merchant Business Name
     * @return App\Merchant\Entity Sub Merchant Entity
     */
    public static function createFromMerchant(Entity $aggregator, $businessName, $email, $isLinkedAccount = false)
    {
        $merchant = new static();

        $merchant->id       = Uuid::generate();
        $merchant->name     = $businessName;
        $merchant->email    = $email;

        return $merchant;
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

    public function transactions()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Transaction'
        );
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

    public static function getTransactionAggregations($mode, $sort, $filterTimestamp, $type, $merchantId)
    {
        $data = \DB::table('transactions')
                    ->select(
                        \DB::raw(
                            'transactions.merchant_id,
                            SUM(transactions.amount) as total_amount,
                            SUM(transactions.count) as total_count'
                        )
                    )
                    ->where('transactions.mode', '=', $mode)
                    ->where('transactions.type', '=', $type)
                    ->where('transactions.created_at', '>=', $filterTimestamp)
                    ->where('transactions.merchant_id', '=', $merchantId)
                    ->orderBy($sort, 'DESC')
                    ->get();

        return $data;
    }

    public static function getAllTransactionAggregations($mode, $sort, $count, $filterTimestamp, $type)
    {
        $data = \DB::table('transactions')
                    ->select(
                        \DB::raw(
                            'transactions.merchant_id,
                            SUM(transactions.amount) as total_amount,
                            SUM(transactions.count) as total_count'
                        )
                    )
                    ->where('transactions.mode', '=', $mode)
                    ->where('transactions.type', '=', $type)
                    ->where('transactions.created_at', '>=', $filterTimestamp)
                    ->groupBy('transactions.merchant_id')
                    ->orderBy($sort, 'DESC')
                    ->simplePaginate($count);

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
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
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

    public function suspend()
    {
        $this->suspended_at = time();
        $this->save();
    }
}
