<?php

namespace Models\Manager;

class TransactionStatus
{
	const OPEN = 'open';
	const AUTH = 'auth';
	const CAPTURED = 'captured';
	const ENROLLED = 'enrolled';
	const NOT_ENROLLED = 'not enrolled';
	const FAILED = 'failed';
	const CAPTURE_FAILED = 'capture_failed';
	const REFUNDED = 'refunded';
	const PARTIALLY_REFUNDED = 'prefunded';
	const CHARGEBACK = 'chargeback';
	const SETTLEMENT_SENT = 'settlement_sent';
	const SETTLED = 'settled';
}
