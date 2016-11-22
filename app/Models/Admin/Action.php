<?php

namespace RZP\Models\Admin;

class Action
{
    const LOGIN                     = 'LOGIN';
    const LOGIN_FAIL                = 'LOGIN_FAIL';
    const LOGIN_OAUTH               = 'LOGIN_OAUTH';
    const LOGIN_FAIL_OUATH          = 'LOGIN_FAIL_OAUTH';
    const GENERATE_LOGIN_TOKEN      = 'GENERATE_LOGIN_TOKEN';
    const CREATE_ADMIN              = 'CREATE_ADMIN';
    const EDIT_ADMIN                = 'EDIT_ADMIN';
    const DELETE_ADMIN              = 'DELETE_ADMIN';
    const UPDATE_ADMIN_ROLES        = 'UPDATE_ADMIN_ROLES';
    const ADD_MERCHANT_TO_ADMIN     = 'ADD_MERCHANT_TO_ADMIN';
    const REVOKE_ADMIN_ROLE         = 'REVOKE_ADMIN_ROLE';
}