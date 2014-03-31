<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayResult
{
	/**
	 * Result codes received in response for card enrollment
	 */
	const ENROLLED = 1;
	const NOT_ENROLLED = 2;
	const FAIL_ENROLLED = 0;
	const FSS0001_ENROLLED = -1;
	const UNKNOWN_ERROR_ENROLLED = -2;

	/**
	 * Result codes received in response for transaction
	 * authorization
	 */
	const CAPUTRED = 'CAPTURED';
	const APPROVED = 'APPROVED';
	const NOT_CAPTURED = 'NOT CAPTURED';
	const NOT_APPROVED = 'NOT APPROVED';
	const DENIED_BY_RISK = 'DENIED BY RISK';
	const HOST_TIMEOUT = 'HOST TIMEOUT';
}