<?php

/**
 * Contains:
 * - Which driver to use by default for metrics service?
 * - And the corresponding configuration for any initialization of specific driver etc.
 */
return [

    // Implemented drivers: mock|newrelic|prometheus
    'driver'     => 'prometheus', // TODO: Move to env.

    'mock'       => [],

    'newrelic'   => [],

    'prometheus' => [],
];
