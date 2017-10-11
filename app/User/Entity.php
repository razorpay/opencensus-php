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
     * Accessor for the currentMerchant method.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getCurrentMerchantAttribute()
    {
        return $this->currentMerchant();
    }

    public function getConfirmToken()
    {
        return $this->confirm_token;
    }

    /**
     * Get the merchant that user is currently viewing.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function currentMerchant()
    {
        $currentMerchantId = Session::get('current_merchant_id');

        if (is_null($currentMerchantId) && $this->hasMerchants())
        {
            $this->switchToMerchant($this->merchants->first());

            return $this->currentMerchant();
        }
        else if (is_null($currentMerchantId) === false)
        {
            $currentMerchant = $this->merchants->find($currentMerchantId);

            return $currentMerchant ?: $this->refreshCurrentMerchant();
        }
    }

    /**
     * Get the id of the merchant that user is currently viewing.
     *
     * @param  void
     * @return integer
     */
    public function getCurrentMerchantId()
    {
        if ($this->currentMerchant)
        {
            return $this->currentMerchant->id;
        }
        else
        {
            return null;
        }
    }

    /**
     * Switch the current merchant for the user.
     *
     * @param  \App\Merchant\Entity  $merchant
     * @return void
     */
    public function switchToMerchant($merchant)
    {
        Session::put('current_merchant_id',$merchant->id);
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
     * Get the user's role on a given merchant.
     *
     * @param  \App\Merchant\Entity  $merchant
     * @return string
     */
    public function getMerchantRole($merchant)
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
        $bytes = random_bytes($length/2);
        $token = bin2hex($bytes);

        return $token;
    }

    /**
     * Generates Confirmation token
     */
    protected function generateConfirmToken()
    {
        $this->setAttribute('confirm_token',$this->generateOneTimeUseToken(32));
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

    /**
     * Confirm a user account
     * @return self
     */
    public function confirm()
    {
        $this->confirm_token = null;

        $this->save();

        return $this;
    }

    public function getUserRoleWithCurrentMerchant()
    {
        $user = Auth::guard('user')->user();

        $currentMerchant = $user->currentMerchant;

        return $currentMerchant->pivot->role;
    }

    public static function getUserWithEmail($email)
    {
        return self::where('email', $email)->first();
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = mb_strtolower($value);
    }

    public function getConfirmedAttribute()
    {
        return ($this->confirm_token === null);
    }
}
