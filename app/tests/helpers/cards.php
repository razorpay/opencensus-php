<?php
//contain array of test cards
return [
	[
	'PAN' => '4012001036275556',
	'response' => 0,
	'type' => 'CC',
	'code' => "TIMEOUT",
	'message' => "Request timed out"
	],
	[
	'PAN' => '4012001038443335',
	'response' => 1,
	'type' => 'CC'
	],
	[
	'PAN' => '4012001038488884',
	'response' => 0,
	'type' => 'CC',
	'code' => "FSS0001",
	'message' => "Authentication Not Available"
	],
	[
	'PAN' => '4012001036298889',
	'response' => 0,
	'type' => 'CC',
	'code' => "FSS0001",
	'message' => "Authentication Not Available"

	],
	[
	'PAN' => '4012001036853337',
	'response' => 0,
	'type' => 'DC',
	'code' => "GV00007",
	'message' => "Signature Validation Failed"
	],
	[
	'PAN' => '4012001036983332',
	'response' => 0,
	'type' => 'DC',
	'code' => "GV00008",
	'message' => "Signature Validation Failed"
	],
	[
	'PAN' => '4012001037141112',
	'response' => 1,
	'type' => 'DC'
	],
	[
	'PAN' => '4005559876540',
	'response' => 1,
	'type' => 'DC'
	],
	[
	'PAN' => '4012001037167778',
	'response' => 1,
	'type' => 'DC'
	],
	[
	'PAN' => '4012001037461114',
	'response' => 0,
	'type' => 'DC',
	'code' => "GV00004",
	'message' => "PARes Status Not Sucessful"
	],
	[
	'PAN' => '4012001037484447',
	'response' => 0,
	'type' => 'DC',
	'code' => "FSS0001",
	'message' => "Authentication Not Available"
	],
	[
	'PAN' => '4012001037490006',
	'response' => 0,
	'type' => 'DC',
	'code' => "FSS0001",
	'message' => "Authentication Not Available"
	],
	[
	'PAN' => '4012001037490014',
	'response' => 1,
	'type' => 'DC'
	],
	[
	'PAN' => '4012001037141112',
	'response' => 1,
	'type' => 'DC'
	]
];
