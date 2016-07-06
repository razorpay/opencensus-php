<?php

return array(

	/**
	 * You may wish for all e-mails sent with Mailgun to be sent from
	 * the same address. Here, you may specify a name and address that is
	 * used globally for all e-mails that are sent by Mailgun.
	 *
	 */
	'from' => array(
		'address' => 'support@razorpay.com',
		'name' => 'Razorpay Team'
	),


	/**
	 * Global reply-to e-mail address
	 *
	 */
	'reply_to' => 'support@razorpay.com',


	/**
	 * Mailgun (private) API key
	 *
	 */
	'api_key' => 'key-7231363487a56ca70808987512475881',

	/**
	 * Mailgun public API key
	 *
	 */
	'public_api_key' => 'pubkey-69fd2327dc8d9680275d619cc5ff90fe',

	/**
	 * Domain name registered with Mailgun
	 *
	 */
	'domain' => 'razorpay.com',

	/**
	 * Force the from address
	 *
	 * When your `from` e-mail address is not from the domain specified some
	 * e-mail clients (Outlook) tend to display the from address incorrectly
	 * By enabling this setting Mailgun will force the `from` address so the
	 * from address will be displayed correctly in all e-mail clients.
	 *
	 * Warning:
	 * This parameter is not documented in the Mailgun documentation
	 * because if enabled, Mailgun is not able to handle soft bounces
	 *
	 */
	'force_from_address' => false,


	/**
	 * Testing
	 *
	 * Catch All address
	 *
	 * Specify an email address that receives all emails send with Mailgun
	 * This email address will overwrite all email addresses within messages
	 */
	'catch_all' => "",


	/**
	 * Testing
	 *
	 * Mailgun's testmode
	 *
	 * Send messages in test mode by setting this setting to true.
	 * When you do this, Mailgun will accept the message but will
	 * not send it. This is useful for testing purposes.
	 *
	 * Note: Mailgun does charge your account for messages sent in test mode.
	 */
	'testmode' => $_ENV['MAIL_PRETEND']
);
