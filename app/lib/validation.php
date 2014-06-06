<?php

Validator::extend('alpha_space', function($attribute, $value, $parameters)
{
    return preg_match('/^[\pL ]+$/u', $value);
});