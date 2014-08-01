<?php

return array(

    'multi' => array(
        'merchant' => array(
            'driver' => 'eloquent',
            'model' => 'Models\DAL\Merchant',
            'table' => 'merchants'
        )
    ),

    'reminder' => array(

        'email' => 'emails.auth.reminder',

        'table' => 'password_reminders',

        'expire' => 1440,

    )

);
