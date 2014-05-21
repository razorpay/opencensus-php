<?php

namespace Models\DAL;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class UuidDAL extends DAL
{

    /**
     * Indicates if the IDs are uuid.
     *
     * @var bool
     */
    public $uuid = true;

    public $incrementing = false;

    //Not required anymore, since it is moved to global.php as recommended in laravel issue #1181
    /*    
    protected static function boot()
    {
        parent::boot();

        static::creating(function($model)
        {
            $model->{$model->getKeyName()} = (string)Uuid::uuid1();
        });
    }*/

    public function generateUuid($model)
    {
       $model->{$model->getKeyName()} = str_replace("-", "", Uuid::uuid1());
    }
}