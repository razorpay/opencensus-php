<?php

return array(

    'multi' => array(
        'merchant' => array(
            'driver' => 'eloquent',
            'model' => 'Models\Merchant\Entity',
            'table' => 'merchants',
            'email' => 'emails.auth.reminder'
        ),
        'admin' => array(
            'driver' => 'eloquent',
            'model' => 'Models\Admin\Entity',
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
