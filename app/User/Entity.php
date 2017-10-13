<?php

namespace App\User;

use Auth;
use Uuid;
use Session;
use App\Base;
use App\Merchant;
use App\Invitation;
use Illuminate\Auth\Authenticatable;
use RandomLib\Factory as RandomLibFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Entity extends Base\Entity implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

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
    protected $hidden = ['password', 'remember_token', 'confirm_token'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name','email','password', 'contact_mobile'];

    /**
     * The attributes that must be auto-generated.
     *
     * @var array
     */
    protected static $generators = array('id','confirm_token');

    protected $appends = ['confirmed'];

    /**
     * Get all of the merchants that the user belongs to.
     */
    public function merchants($suspendedAlso = false)
    {
        $query = $this->belongsToMany(Merchant\Entity::class, 'merchant_users', 'user_id', 'merchant_id')
                      ->withPivot(['role']);

        if ($suspendedAlso === false)
        {
            $query = $query->whereNull('suspended_at');
        }

        return $query->orderBy('name', 'asc');
    }

    /**
     * Join the merchant with the given ID and role.
     *
     * @param  string  $merchantId
     * @return void
     */
    public function joinMerchantByIdWithRole($merchantId, $role)
    {
        $this->merchants()->attach([$merchantId], ['role' => $role]);
    }

    /**
     * Refresh the current merchant for the user.
     *
     * @return  \App\Merchant\Entity
     */
    public function refreshCurrentMerchant()
    {
        Session::put('current_merchant_id', null);

        return $this->currentMerchant();
    }

    public function changePassword($input)
    {
        return $this->edit($input, 'changePassword');
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

    public static function getUserWithEmail($email)
    {
        return self::where('email', $email)->first();
    }
}
