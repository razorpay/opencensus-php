#!/usr/bin/env php
<?php

$baseDir = dirname(__FILE__).'/../';
const API_HOME = '/home/ubuntu/api/';

$sampleApiEnv = include("$baseDir.env.sample.php");
$actualAPiEnv = include(API_HOME . ".env.php");

foreach ($sampleApiEnv as $key => $value)
{
	if (! array_key_exists($key, $actualAPiEnv))
	{
		// Exit with a non-zero code
		echo "Key Does not exist in .env.php: $key\n";
		exit(-1);
	}
}
echo "Environment Validated\n";
exit(0);
