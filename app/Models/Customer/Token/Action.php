<?php


namespace RZP\Models\Customer\Token;


class Action
{
    const CRYPTOGRAM        = 'cryptogram';
    const CREATE            = 'create';
    const MIGRATE           = 'migrate';
    const FETCH             = 'fetch';
    const DELETE            = 'delete';
    const UPDATE            = 'update';
    const PAR_API           = 'par_api';
    const FETCH_FINGERPRINT = 'fetch_fingerprint';
    const FETCH_MERCHANTS   = 'fetch_merchants';
    const FETCH_MERCHANTS_SR   = 'fetch_merchants_sr';
    const TOKEN_PUSH        = 'token_push';
    const TOKEN_PUSH_FETCH  = 'token_push_fetch';
    const TOKEN_PUSH_SR     = 'token_push_sr';
    const TOKEN_MIGRATE     = 'token_migrate';
}
