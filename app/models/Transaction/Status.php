<?php

namespace Models\Transaction;

class Status
{
	const OPEN = 'open';
	const AUTH = 'auth';
	const CAPTURED = 'captured';
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PARTIALLY_REFUNDED = 'prefunded';
	const SETTLEMENT_SENT = 'settlement_sent';
	const SETTLED = 'settled';
}
