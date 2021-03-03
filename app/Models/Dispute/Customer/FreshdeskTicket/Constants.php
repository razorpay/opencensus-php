<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

class Constants
{
    const BASE_VIEW_TEMPLATE           = 'dispute.customer.base';
    const PARTIAL_VIEW_TEMPLATE_PREFIX = 'partials.dispute.customer';

	const ACTION_CREATE_DISPUTE          = 'create_dispute';
	const ACTION_MERCHANT_DISABLED       = 'merchant_disabled';
	const ACTION_PAYMENT_DISPUTED        = 'payment_disputed';
	const ACTION_PAYMENT_FULLY_REFUNDED  = 'payment_fully_refunded';
	const ACTION_PAYMENT_NOT_CAPTURED    = 'payment_not_captured';
	const ACTION_PAYMENT_FAILED          = 'payment_failed';
	const ACTION_PAYMENT_NOT_FOUND       = 'payment_not_found';
	const ACTION_DISPUTE_CREATION_EXPIRY = 'dispute_creation_expiry';

	const REFUND_BUFFER         = 10;
	const DISPUTE_EXPIRES_AFTER = 2;

	// FD Tags
	const FD_TAGS_AUTOMATED_DISPUTE_FLOW        = 'automated_dispute_flow';
	const FD_TAGS_DISPUTE_CREATED               = 'dispute_created';
	const FD_TAGS_PENDING_WITH_DISPUTES         = 'pending_with_disputes';
	const FD_TAGS_TRIGGERED_BY_RZP_DISPUTE_FLOW = 'triggered_by_rzp_dispute_flow';
	const FD_TAGS_PAYMENT_OLDER_THAN_SIX_MONTHS = 'payment_older_than_six_months';

	// FD Status
	const FD_TICKET_STATUS_OPEN                     = 2;
	const FD_TICKET_STATUS_PENDING                  = 3;
	const FD_TICKET_STATUS_RESOLVED                 = 4;
	const FD_TICKET_STATUS_CLOSED                   = 5;
	const FD_TICKET_STATUS_WAITING_ON_CUSTOMER      = 6;
	const FD_TICKET_STATUS_NEW                      = 8;
	const FD_TICKET_STATUS_PENDING_WITH_CHILD       = 9;
	const FD_TICKET_STATUS_PENDING_WITH_TECH_JIRA   = 10;
	const FD_TICKET_STATUS_PENDING_WITH_THIRD_PARTY = 11;

	const MAX_ALLOWED_DISPUTE_CREATION_WINDOW_IN_SECS = 15552000; // 180 days
}
