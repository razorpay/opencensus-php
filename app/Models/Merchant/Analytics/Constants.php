<?php

namespace RZP\Models\Merchant\Analytics;

use RZP\Models\Payment;

class Constants
{
    public const VALUE = 'value';
    public const TOTAL = 'total';
    public const RESULT = 'result';

    public const DETAILS = 'details';
    public const GROUP_BY = 'group_by';
    public const FILTER_KEY = 'filter_key';
    public const AGGREGATIONS = 'aggregations';

    public const GROUP_BY_FIELDS_FOR_OVERALL_CR = [Payment\Entity::STATUS];
    public const GROUP_BY_FIELDS_FOR_ERROR_METRICS = ['error_description', 'value'];


    public const NUMBER_OF_SUCCESSFUL_PAYMENTS = 'number_of_successful_payments';
    public const TOTAL_CHECKOUT_RENDERS = 'total_checkout_renders';

    public const CHECKOUT_METHOD_LEVEL_TOP_ERROR_REASONS = 'checkout_method_level_top_error_reasons';

    // Overall CR = CR * SR
    public const OVERALL_CR_RELATED_AGGREGATION_NAMES = [
        'checkout_overall_cr',
        'checkout_method_level_overall_cr',
        'checkout_instrument_level_overall_cr',
        'checkout_industry_level_overall_cr',
        'checkout_industry_method_level_overall_cr',
    ];

    public const INDUSTRY_LEVEL_QUERIES = [
        'checkout_industry_level_overall_cr',
        'checkout_industry_method_level_overall_cr',
    ];

    public const ERROR_METRICS_RELATED_AGGREGATION_NAMES = [
        'checkout_top_error_reasons',
        'checkout_method_level_top_error_reasons',
        'checkout_instrument_level_top_error_reasons',
    ];

    public const ERROR_SOURCE = 'error_source';
    public const ERROR_REASONS = 'error_reasons';
    public const ERROR_DESCRIPTION = 'error_description';
    public const ERROR_METRICS_LIMIT = 6;
    public const INTERNAL_ERROR_CODE = 'internal_error_code';
    public const LAST_SELECTED_METHOD = 'last_selected_method';

    public const METHOD_MAPPING = [
    ];

    public const CUSTOM_ERROR_DESCRIPTION_MAPPING = [
    ];
}
