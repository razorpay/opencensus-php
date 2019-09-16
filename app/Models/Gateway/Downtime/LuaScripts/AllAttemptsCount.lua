-- What it does?
--

--
redis.replicate_commands()

-- Gets keys and arguments from command

-- Example for KEYS[1] argument are
-- config:downtime:detection:test:HDFC, config:downtime:detection:test:FSS.
local key                   = KEYS[1]
-- number of gateway request that are success
local count                 = ARGV[1]

local time                  = redis.call("time")
--timeInMicroseconds
local now    = time[1] * 1000000 + time[2]

local updateCount = function(windowLength, hashKey, expiry)
    -- Each sliding time window is divided into 100 fixed time windows.
    -- So: If 300 seconds is the window length and request is received at T=301,
    -- failure count is increased in fixed window=300 and so is for 300 and 302
    local fixedWindowParts      = 100

    -- in microseconds
    local fixedWindowLength     = windowLength / fixedWindowParts

    local windowHashKey         = now - (now % fixedWindowLength)

    redis.call('HINCRBY', hashKey, windowHashKey, count)
    redis.call('EXPIRE', hashKey, expiry)
end

-- Loop through each window length passed in arguments and
-- increment the bucket count in each.
for i,v in ipairs(ARGV) do

    -- Ignore first argument because first one is success count.
    if (i > 1)
    then
        -- window length in seconds will be expiry of hash
        local expiry = tonumber(v)

        -- window length in microseconds
        local windowLength          = tonumber(v) * 1000000

        -- Add Comment: How are keys stored?
        local allAttemptsHashKey = key..':ALL_ATTEMPTS:'..windowLength

        updateCount(windowLength, allAttemptsHashKey, expiry)
    end
end

return;
