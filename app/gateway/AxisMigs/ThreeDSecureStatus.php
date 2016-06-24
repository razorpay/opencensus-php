<?php

namespace Gateway\AxisMigs;

use Gateway\AxisMigs;

class ThreeDSecureStatus
{
	const SUCCESS = 'success';
	const FAILURE = 'failure';
	const SKIPPED = 'skipped';

	// 'Y' - 3d secure auth succeeded
	// 'N' - 3d secure auth failed
	// 'A' - Attempted authentication
    // 'U' - Unavailable for checking
	protected static $vpc_3DSstatus_map = array(self::SUCCESS => array('Y'), 
                                        self::FAILURE => array('N'), 
                                        self::SKIPPED => array('U', 'A'));

	public static function isAuthSucceeded($status)
	{
		return in_array($status, self::$vpc_3DSstatus_map[self::SUCCESS], true);
	}

	public static function isAuthFailed($status)
	{
		return in_array($status, self::$vpc_3DSstatus_map[self::FAILURE], true);
	}

	public static function isAuthSkipped($status)
	{
		return in_array($status, self::$vpc_3DSstatus_map[self::SKIPPED], true);
	}
}