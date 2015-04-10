<?php

Validator::extend('alpha_space', function($attribute, $value, $parameters)
{
    return preg_match('/(^[A-Za-z0-9 ]+$)+/', $value);
});