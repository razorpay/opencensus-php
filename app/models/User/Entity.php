<?php

namespace Models\User;

use Uuid;
use Session;
use Models\Base;
use Models\Merchant;
use Illuminate\Auth\UserInterface;
use RandomLib\Factory as RandomLibFactory;

class Entity extends Base\Entity implements UserInterface
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
     * Generate the user instance from the merchant instance
     */
    public static function createFromMerchant($merchant)
    {
        $user = new static();
        $user->timestamps = false;

        $user->id = Uuid::generate();
        $user->name = $merchant->name;
        $user->email = $merchant->email;
        
        $user->password = $merchant->password;
        $user->confirm_token = $merchant->confirm_token;
        $user->created_at = $merchant->created_at;
        $user->updated_at = $merchant->updated_at;

        return $user;
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
     * Join the merchant with the given ID and role.
     *
     * @param  int  $merchantId
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
        $current_merchant_id = Session::get('current_merchant_id');
        
        if (is_null($current_merchant_id) && $this->hasMerchants()) 
        {
            $this->switchToMerchant($this->merchants->first());

            return $this->currentMerchant();
        } 
        elseif (! is_null($current_merchant_id)) 
        {
            $currentMerchant = $this->merchants->find($current_merchant_id);

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
        return $this->currentMerchant->id;
    }

    /**
     * Switch the current merchant for the user.
     *
     * @param  \Models\Merchant\Entity  $merchant
     * @return void
     */
    public function switchToMerchant($merchant)
    {
        Session::put('current_merchant_id',$merchant->id);
    }

    /**
     * Refresh the current merchant for the user.
     *
     * @return  \Models\Merchant\Entity
     */
    public function refreshCurrentMerchant()
    {
        Session::put('current_merchant_id', null);

        return $this->currentMerchant();
    }

    /**
     * Determine if the given merchant is owned by the user.
     *
     * @param  \Models\Merchant\Entity  $merchant
     * @return bool
     */
    public function ownsMerchant($merchant)
    {
        $merchants = $this->merchants()->where('email',$email)
                                       ->where('role','owner')
                                       ->first();

        return is_null($merchant) ? false : true;
    }

    /**
     * Take care while calling this method
     * @param array $input array with new email address
     */
    public function changeEmail($input)
    {
        return $this->edit($input, 'changeEmail');
    }

    public function changePassword($input)
    {
        return $this->edit($input, 'changePassword');
    }

    /**
     * Get the user's role on a given merchant.
     *
     * @param  \Models\Merchant\Entity  $merchant
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
}