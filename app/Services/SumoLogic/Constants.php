<?php


namespace RZP\Services\SumoLogic;


class Constants
{
    const JOB_DEFAULT_TIMEOUT   = 10 * 60;  // 10 mins (in seconds)

    const RETRY_WAIT_TIME = 5; // in seconds

    // sumo search job states
    const GATHERING_RESULTS         = "GATHERING RESULTS";
    const DONE_GATHERING_RESULTS    = "DONE GATHERING RESULTS";
}
