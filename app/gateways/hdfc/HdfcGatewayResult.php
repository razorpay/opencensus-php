<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayResult
{
	const CAPUTRED = 'CAPTURED';
	const APPROVED = 'APPROVED';
	const NOT_CAPTURED = 'NOT CAPTURED';
	const NOT_APPROVED = 'NOT APPROVED';
	const DENIED_BY_RISK = 'DENIED BY RISK';
	const HOST_TIMEOUT = 'HOST TIMEOUT';
}