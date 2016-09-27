<?php

$password = Hash::make('123456');

$factory('App\Merchant\Entity', [
    'id'  =>  $faker->word,
    'name'  =>  $faker->word,
    'email'  =>  $faker->email,
    'password'  =>  $password
]);

$factory('App\User\Entity', [
    'id'  =>  $faker->word,
    'name'  =>  $faker->word,
    'email'  =>  $faker->email,
    'password'  =>  $password
]);

$factory('App\Admin\Entity', [
    'name'  => $faker->word,
    'username'  => $faker->word,
    'password'  => $password,
    'email' => 'testoauth@testcases.com',
    'superadmin'    => '1'
]);

$factory('App\MerchantDetails\Entity', [
    'merchant_id'   => '10000000000000',
    'contact_name'  => $faker->word,
    'contact_email' => $faker->email,
    'transaction_report_email'=>$faker->email,
    'contact_mobile'    => 9000000000,
    'contact_landline'  => 1234567890,
    'business_type' => 2,
    'business_name' => $faker->word,
    'business_dba'  => $faker->word,
    'business_website'  => $faker->url,
    'business_international'    => 1,
    'business_paymentdetails'   => $faker->text,
    'business_registered_address'   => $faker->text,
    'business_registered_state' => $faker->word,
    'business_registered_city'  => $faker->word,
    'business_registered_pin'   => $faker->word,
    'business_operation_address'    => $faker->text,
    'business_operation_state'  => $faker->word,
    'business_operation_city'   => $faker->word,
    'business_operation_pin'    => $faker->word,
    'business_doe'  => "1990-11-01",
    'company_cin'   => $faker->word,
    'company_pan'   => $faker->word,
    'company_pan_name'  => $faker->word,
    'business_model'    => $faker->text,
    'transaction_volume'    => 2,
    'transaction_value' => $faker->randomNumber(4),
    'promoter_pan'  => $faker->word,
    'promoter_pan_name' => $faker->word,
    'bank_name' => $faker->word,
    'bank_account_number'   => 'RZP100000000',
    'bank_account_name' => substr(md5(time()), 0, 5),
    'bank_account_type' => $faker->word,
    'bank_branch'   => $faker->word,
    'bank_branch_ifsc'  => 'KKBK0000261',
    'bank_beneficiary_address1' => $faker->word,
    'bank_beneficiary_address2' => $faker->word,
    'bank_beneficiary_address3' => $faker->word,
    'bank_beneficiary_city' => $faker->word,
    'bank_beneficiary_state'    => 'RJ',
    'bank_beneficiary_pin'  => '123456',
    'business_proof_url'    => $faker->url,
    'business_pan_url'  => $faker->url,
    'promoter_pan_url'  => $faker->url,
    'address_proof_url' => $faker->url,
    'steps_finished'    => "[1,3,5]",
    'submitted' => 1
]);

?>
