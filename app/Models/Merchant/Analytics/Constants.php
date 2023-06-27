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

    public const SUBMIT_EVENT = 'behav_submit_event';
    public const OPEN_EVENT = 'render_checkout_open_event';

    public const GROUP_BY_FIELDS_FOR_CR = [self::SUBMIT_EVENT, self::OPEN_EVENT];
    public const GROUP_BY_FIELDS_FOR_SR = [Payment\Entity::STATUS];
    public const GROUP_BY_FIELDS_FOR_ERROR_METRICS = ['error_description', 'value'];

    public const TOTAL_NUMBER_OF_SUBMIT_EVENTS = 'total_number_of_submit_events';
    public const TOTAL_NUMBER_OF_OPEN_EVENTS = 'total_number_of_open_events';

    public const NUMBER_OF_SUCCESSFUL_PAYMENTS = 'number_of_successful_payments';
    public const NUMBER_OF_TOTAL_PAYMENTS = 'number_of_total_payments';

    public const CHECKOUT_METHOD_LEVEL_TOP_ERROR_REASONS = 'checkout_method_level_top_error_reasons';

    public const SR_RELATED_AGGREGATION_NAMES = [
        'checkout_overall_sr',
        'checkout_method_level_sr',
        'checkout_instrument_level_sr',
        'checkout_industry_level_sr',
    ];
    public const CR_RELATED_AGGREGATION_NAMES = [
        'checkout_overall_cr',
        'checkout_method_level_cr',
        'checkout_instrument_level_cr',
        'checkout_industry_level_cr',
    ];

    public const INDUSTRY_LEVEL_QUERIES = [
        'checkout_industry_level_sr',
        'checkout_industry_level_cr',
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
