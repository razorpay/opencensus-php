<?php

return [

	/*
	|--------------------------------------------------------------------------
	| oAuth Config
	|--------------------------------------------------------------------------
	*/

	/**
	 * Storage
	 */
	'storage' => '\\OAuth\\Common\\Storage\\Session',

	/**
	 * Consumers
	 */
	'consumers' => [

		'Google' => [
			'client_id'     => '938088475836-cf2sa26m5nhr7uv058l017t2ucpnt9mu.apps.googleusercontent.com',
			'client_secret' => 'DwBJFjYIYInl7xolmTu29tRg',
			'scope'         => ['userinfo_email', 'userinfo_profile'],
		],

	],

	'userinfo_url' => 'https://www.googleapis.com/oauth2/v1/userinfo'

];