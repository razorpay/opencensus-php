-- What it does?
--

--
redis.replicate_commands()

-- Gets keys and arguments from command

-- Example for KEYS[1] argument are
-- GatewayDowntime:HDFC, GatewayDowntime:FSS.
local key                   = KEYS[1]
-- All sliding window lengths for a gateway in seconds
local windowLengths          = ARGV

local time                  = redis.call("time")
--timeInMicroseconds
local now    = time[1] * 1000000 + time[2]

local updateCount = function(windowLength, now, hashKey)
    -- Each sliding time window is divided into 100 fixed time windows.
    -- So: If 300 seconds is the window length and request is received at T=301,
    -- failure count is increased in fixed window=300 and so is for 300 and 302
    local fixedWindowParts      = 100

    -- in microseconds
    local fixedWindowLength     = windowLength / fixedWindowParts

    local windowHashKey         = now - (now % fixedWindowLength)

    redis.call('HINCRBY', hashKey, windowHashKey, 1)
    redis.call('EXPIRE', hashKey, tonumber(ARGV[1]))
end

-- Loop through each window length passed in arguments and
-- increment the bucket count in each.
for i,v in ipairs(windowLengths) do
    -- window length in microseconds
    local windowLength          = tonumber(v) * 1000000

    -- Add Comment: How are keys stored?
    local allAttemptsHashKey = key..':ALL_ATTEMPTS:'..windowLength

    updateCount(windowLength, now, allAttemptsHashKey)
end

return;
