<?php

namespace RZP\Models\Admin;

class Action
{
    // const LOGIN                     = 'LOGIN';
    // const LOGIN_FAIL                = 'LOGIN_FAIL';
    // const LOGIN_OAUTH               = 'LOGIN_OAUTH';
    // const LOGIN_FAIL_OUATH          = 'LOGIN_FAIL_OAUTH';
    // const GENERATE_LOGIN_TOKEN      = 'GENERATE_LOGIN_TOKEN';
    // const CREATE_ADMIN              = 'CREATE_ADMIN';
    // const EDIT_ADMIN                = 'EDIT_ADMIN';
    // const DELETE_ADMIN              = 'DELETE_ADMIN';
    // const UPDATE_ADMIN_ROLES        = 'UPDATE_ADMIN_ROLES';
    // const ADD_MERCHANT_TO_ADMIN     = 'ADD_MERCHANT_TO_ADMIN';
    // const REVOKE_ADMIN_ROLE         = 'REVOKE_ADMIN_ROLE';

    // Did not use a hashmap because using Action::LOGIN
    // is cooler than Action::MAP['login']

    const LOGIN = [
        'category'  => 'Auth',
        'action'    => 'Login'
    ];

    const LOGIN_FAIL = [
        'category'  => 'Auth',
        'action'    => 'Login Fail'
    ];

    const LOGIN_OAUTH = [
        'category'  => 'Auth',
        'action'    => 'Login OAuth'
    ];

    const LOGIN_FAIL_OAUTH = [
        'category'  => 'Auth',
        'action'    => 'Login Fail OAuth'
    ];

    const GENERATE_LOGIN_TOKEN = [
        'category'  => 'Auth',
        'action'    => 'Generate Login Token'
    ];

    const CREATE_ADMIN = [
        'category'  => 'UAM',
        'label'     => 'Admin',
        'action'    => 'Create'
    ];

    const EDIT_ADMIN = [
        'category' => 'UAM',
        'label' => 'Admin',
        'action' => 'Edit'
    ];

    const DELETE_ADMIN = [
        'category' => 'UAM',
        'label' => 'Admin',
        'action' => 'Delete'
    ];

    const UPDATE_ADMIN_ROLES = [
        'category' => 'UAM',
        'label' => 'Admin Roles',
        'action' => 'Update'
    ];
}
