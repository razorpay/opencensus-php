<?php

return [
   "default" =>
       [
           'driver' => 'redis',
           'exceptions_on' => false,
           'time_window' => 60,
           'time_out_open' => 30,
           'time_out_half_open' => 20,
           'total_failures' => 50
       ],

   "aadhaar_ekyc" =>
       [
           'driver' => 'redis',
           'exceptions_on' => false,
           'time_window' => 60,
           'time_out_open' => 30,
           'time_out_half_open' => 20,
           'total_failures' => 50
   ],
];
