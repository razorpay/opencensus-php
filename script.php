<?php

$data = [
    "Fields::MERCH_CHAN_ID" => "raozorAPP",
    "Fields::ORDER_ID"      => "PAYMENT_ID",
    "Fields::CREDIT_VPA"    => "raozrpay@axis",
];

$dataStr = implode('', $data);

var_dump($dataStr);



// $events = [];

// // $gateway = 'cybs';
// // $success = true;
// // $errorcode = null;
// // $events = array_merge_recursive($events, [
// //     $gateway => [
// //         $success => [
// //             $errorcode => 1,
// //         ]
// //     ]
// // ]);

// // s($events);
// // $success = false;
// // $errorcode = "BAD_REQUEST_PAYMENT_FAILED";

// // $events = array_merge_recursive($events, [
// //     $gateway => [
// //         $success => [
// //             $errorcode => 1,
// //         ]
// //     ]
// // ]);

// // s($events);