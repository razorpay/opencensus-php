<?php

namespace App\User;

use Uuid;
use Session;
use App\Base;
use App\Merchant;
use App\Invitation;
use Auth;
use RandomLib\Factory as RandomLibFactory;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
// use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
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
    public function merchants($archivedAlso = false)
    {
        $query = $this->belongsToMany(Merchant\Entity::class, 'merchant_users', 'user_id', 'merchant_id')
            ->withPivot(['role']);

        if ($archivedAlso === false)
        {
            $query = $query->whereNull('archived_at');
        }

        return $query->orderBy('name', 'asc');
    }

    /**
     * Get all of the pending invitations for the user.
     */
    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class);
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

        $this->currentMerchant();
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
     * Returns the first merchant owned by this user
     * @return Merchant\Entity
     */
    public function getOwnerMerchant()
    {
        return $this->merchants()
            ->where('email', $this->email)
            ->where('role', 'owner')
            ->first();
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

    /**
     * Determine if the given merchant is owned by the user.
     *
     * @param  \App\Merchant\Entity  $merchant
     * @return bool
     */
    public function ownsMerchant($merchant)
    {
        $merchants = $this->merchants()->where('email',$email)
                                       ->where('role','owner')
                                       ->first();

        return is_null($merchant) ? false : true;
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
        $factory = new RandomLibFactory;
        $generator = $factory->getLowStrengthGenerator();
        $token = bin2hex($generator->generate($length/2));

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

        $currentMerchant = $user->getCurrentMerchantAttribute();
        return $currentMerchant->pivot->role;
    }

    public static function getUserWithEmail($email)
    {
        return self::where('email', $email)->first();
    }
}
