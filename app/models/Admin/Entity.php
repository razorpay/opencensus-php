<?php

namespace Models\DAL;

use Illuminate\Auth\UserInterface;

class Admin extends DAL implements UserInterface
{
    protected $table = 'admins';

    protected $fillable = array(
        'name',
        'username',
        'password',
        'email',
        'superadmin'
    );

    protected $guarded = array('id');


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
}
