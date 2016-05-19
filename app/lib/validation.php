<?php

Validator::resolver(function($translator, $data, $rules, $messages, $customAttributes)
{
    return new Razorpay\Spine\Validation\LaravelValidatorEx(
                    $translator, $data, $rules, $messages, $customAttributes);
});
