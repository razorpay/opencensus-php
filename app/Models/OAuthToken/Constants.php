<?php


namespace RZP\Models\OAuthToken;


class Constants
{
    const CLIENT_ID               = 'client_id';

    const CLIENT_DETAILS          = 'client_details';

    const PROD                    = 'prod';

    const ID                      = 'id';

    const PREVIOUS_TOKEN_REVOKED  = 'previous_token_revoked';

    const ACCESS_TOKEN            = 'access_token';

    const TOKEN_REVOKED           = 'Token Revoked';

    const APPLE_WATCH_TOKEN = [
        'TYPE'          =>  'apple_watch',
        'SCOPE'         =>  'apple_watch_read_write',
        'GRANT_TYPE'    =>  'client_credentials',
        'MODE'          =>  'live'
    ];
}
