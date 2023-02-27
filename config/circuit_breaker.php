<?php

return [
   'default' => [
        'time_window' => 60,
        'interval_to_half_open' => 20,
        'failure_rate_threshold' => 50
   ],

   'aadhaar_ekyc' => [
        'time_window' => 60,
        'interval_to_half_open' => 20,
        'failure_rate_threshold' => 50
   ],

   'worker_db' => [
        'time_window' => 60, // Time for an open circuit (seconds)
        'failure_rate_threshold' => 200, // Fail rate for open the circuit
        'interval_to_half_open' => 30,  // Half open time (seconds)
    ],

   'web_db' => [
        'time_window' => 60, // Time for an open circuit (seconds)
        'failure_rate_threshold' => 200, // Fail rate for open the circuit
        'interval_to_half_open' => 30,  // Half open time (seconds)
   ]
];
