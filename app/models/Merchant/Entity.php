<?php

namespace Models\Merchant;

use Models\Base;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class Entity extends Base\Entity implements UserInterface, RemindableInterface
{
    public $incrementing = false;

    protected $table = 'merchants';

    protected $hidden = array('password', 'remember_token');

    protected $fillable = array(
        'id',
        'name',
        'email',
        'password',
        'confirm_token',
        'activated'
    );

    public function transactions()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Transaction'
        );
    }

    public function merchantDetails()
    {
        return $this->hasOne('Models\MerchantDetails\Entity');
    }

    public function changePassword($input)
    {
        return $this->edit($input, 'changePassword');
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

    public static function createAggregations($data, $mode)
    {
        $obj = array(
            'merchant_id'           =>  $data['merchant_id'],
            'total_amount'          =>  $data['amount'],
            'successful_txn_count'  =>  1,
            'txn_count'             =>  1,
            'created_at'            =>  $data['updated_at'],
            'updated_at'            =>  $data['updated_at'],
            'resource'              =>  $data['resource'],
            'mode'                  =>  $mode
        );

        \DB::table('aggregations')->insert($obj);
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

    public static function getMerchantForConfirmation($token)
    {
        return static::where('confirm_token', '=', $token)->first();
    }

    /**
     * Confirms a merchant
     */
    public function confirm()
    {
        $this->confirm_token = null;
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

    public function isActive()
    {
        return ((int)$this->activated === 1);
    }
}
