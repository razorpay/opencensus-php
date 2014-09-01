<?php

return array(

    'multi' => array(
        'merchant' => array(
            'driver' => 'eloquent',
            'model' => 'Models\DAL\Merchant',
            'table' => 'merchants',
            'email' => 'emails.auth.reminder'
        ),
        'admin' => array(
            'driver' => 'eloquent',
            'model' => 'Models\DAL\Admin',
            'table' => 'admins',
            'email' => 'emails.auth.reminder'
        )
    ),

    'reminder' => array(
        'email' => 'emails.auth.reminder',
        
        'table' => 'password_reminders',

        'expire' => 1440,

    )

);
