<?php

namespace Models\User;

use Uuid;
use Hash;
use Models\Base;
use Models\Merchant;
use Models\Invitation;
use Illuminate\Database\Eloquent\Model;
use RandomLib\Factory as RandomLibFactory;


class Entity extends Base\Entity
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array('password', 'remember_token');

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = array('name','email','password');

    /**
     * The attributes that must be auto-generated.
     *
     * @var array
     */
    protected static $generators = array('id','confirm_token');

    /**
     * Generates Uuid ID
     */
    public function generateId()
    {
        $this->setAttribute('id', Uuid::generate());
    }

    /**
     * Determine if the user is a member of any merchants.
     *
     * @return bool
     */
    public function hasMerchants()
    {
        return count($this->merchants) > 0;
    }

    /**
     * Get all of the merchants that the user belongs to.
     */
    public function merchants()
    {
        return $this->belongsToMany(Merchant\Entity::class, 'merchant_users', 'user_id', 'merchant_id')
                    ->withPivot(['role'])
                    ->orderBy('name', 'asc');
    }

    /**
     * Get the user's role on a given merchant.
     *
     * @param  \Razorpay\Models\merchant  $merchant
     * @return string
     */
    public function merchantRole($merchant)
    {
        $merchant = $this->merchants->find($merchant->id);

        if($merchant) 
        {
            return $merchant->pivot->role;
        }
    }

    /**
     * Generates a one time use token of the given length
     */
    protected function generateOneTimeUseToken($length)
    {
        $factory = new RandomLibFactory;
        $generator = $factory->getLowStrengthGenerator();
        $token = bin2hex($generator->generate($length/2));

        return $token;
    }

    /**
     * Generates Confirmation token
     */
    public function generateConfirmToken()
    {
        $this->setAttribute('confirm_token',$this->generateOneTimeUseToken(32));
    }
}