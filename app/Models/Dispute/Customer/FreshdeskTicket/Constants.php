<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

class Constants
{
	const ACTION_CREATE_DISPUTE         = 'create_dispute';
	const ACTION_MERCHANT_DISABLED      = 'merchant_disabled';
	const ACTION_PAYMENT_DISPUTED       = 'payment_disputed';
	const ACTION_PAYMENT_FULLY_REFUNDED = 'payment_fully_refunded';
	const ACTION_PAYMENT_NOT_CAPTURED   = 'payment_not_captured';
	const ACTION_PAYMENT_FAILED         = 'payment_failed';
	const ACTION_PAYMENT_NOT_FOUND      = 'payment_not_found';

	const REFUND_BUFFER         = 10;
	const DISPUTE_EXPIRES_AFTER = 2;
}
