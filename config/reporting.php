<?php

return [

    //This array contains config Ids for which we want to skip email validation, because we have introduced this capability in
    //CA collab portal that we want to send emails to random email addresses.
    'config_ids_to_skip_email_validation'  => explode(',', env("REPORTING_SKIP_EMAIL_VALIDATION_CONFIGS", '')) ?? []
];
