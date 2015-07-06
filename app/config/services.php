<?php

return array(
    'mailgun' => array(
        'domain' => \Config::get('applications.mailgun.url'),
        'secret' => $_ENV['MAILGUN_API_KEY'],
    ),
);
