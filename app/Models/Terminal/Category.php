<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    protected static $DEFAULT_METHOD = [
        METHOD::NETBANKING => 'ecommerce',
    ];

    protected static $DEFAULT_GATEWAY = [
        GATEWAY::AMEX   => 'retail_services',
    ];

    protected static $AMEX = [
        'entertainment',
        'government_business',
        'healthcare',
        'education',
        'education_services',
        'car_rental',
        'auto',
        'sup_hypermrkt_deptstore',
        'travel_agency',
        'utilities',
        'hospitals',
        'insurance',
        'lodging',
        'prof_and_financial_serv',
        'retail_jgwcag',
        'retail_services',
    ];

    protected static $NETBANKING = [
        'government',
        'ecommerce',
        'wallet',
        'education',
        'corporate',
    ];
}
