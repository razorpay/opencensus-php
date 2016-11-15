<?php

namespace App\Admin;

use App\Base;
use App\AdminLead;

use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Entity extends Base\Entity implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = 'admins';

    protected $fillable = array(
        'name',
        'username',
        'password',
        'email',
        'superadmin'
    );

    protected $hidden = array('password');

    protected $guarded = array('id');

    public function changePassword($input)
    {
        return $this->edit($input, 'changePassword');
    }

    /**
     * Get the unique identifier for the Academic institution
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

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function isSuperAdmin()
    {
        return ($this->superadmin == 1);
    }

    /**
     * Promotes a user to a superadmin
     */
    public function promote()
    {
        $this->superadmin = 1;
        $this->save();
    }
}
