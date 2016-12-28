<?php

namespace App\AdminLead;

use Mail;
use Uuid;

use App\Base;
use App\Admin;

class Entity extends Base\Entity
{
    public $incrementing = true;

    protected $table = 'admin_leads';

    protected $hidden = array('token');

    protected $fillable = array(
        'id',
        'admin_id',
        'token',
        'email',
        'form_data',
        'created_at',
        'updated_at',
    );

    // public function admin()
    // {
    //     return $this->belongsTo(Admin\Entity::class, 'admin_id');
    // }
}
