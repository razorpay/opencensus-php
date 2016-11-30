<?php

namespace RZP\Models\Admin;

class Action
{

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
