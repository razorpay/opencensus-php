<?php

namespace RZP\Models\Payee;

use RZP\Models\Base;

use RZP\Models\Base\Traits\NotesTrait;

/**
 * Class Entity
 *
 * @package RZP\Models\Payee
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    protected $fillable = [
        //
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        //
    ];

    protected $public = [
        //
    ];

    protected $casts = [
       //
    ];

    protected $amounts = [
       //
    ];

    protected $appends = [
        //
    ];

    protected static $generators = [
        //
    ];

    protected $publicSetters = [
        //
    ];

    protected $dates = [
        //
    ];

    protected static $sign = 'payee';

    protected $entity = 'payee';

    // --------------- Getters ---------------


    // ------------- End Getters -------------


    // --------------- Setters ---------------


    // ------------- End Setters -------------


    // -------------- Relations --------------


    // ------------ End Relations ------------


    // -------------- Mutators ---------------


    // ------------ End Mutators -------------


    // -------------- Accessors --------------


    // ------------ End Accessors ------------
}
