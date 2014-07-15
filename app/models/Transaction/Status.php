<?php

namespace Models\Transaction;

class Status
{
	const OPEN = 'open';
	const AUTHORIZED = 'authorized';
	const CAPTURED = 'captured';
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PARTIALLY_REFUNDED = 'prefunded';
}
