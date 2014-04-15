<?php

namespace Models\Manager;

class TransactionStatus
{
	const OPEN = 'open';
	const PENDING = 'pending';
	const HOLD = 'hold';
	const CAPTURED = 'captured';
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PARTIALLY_REFUNDED = 'prefunded';
	const CHARGEBACK = 'chargeback';
}
