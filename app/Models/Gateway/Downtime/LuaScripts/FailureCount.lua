-- Add Comment: What it does?
--

-- Add Comment: Why?
redis.replicate_commands()

-- Gets keys and arguments from command
local key                   = KEYS[1]
-- All sliding window lengths for a gateway in seconds
local windowLengths          = ARGV

local time                  = redis.call("time")
--timeInMicroseconds
local now                   = time[1] * 1000000 + time[2]

-- Add Comment: How is the Hash Map maintained?
local updateFailureCount = function(windowLength, now, failureAttemptsHashKey)
    -- Each sliding time window is divided into 100 fixed time windows.
    -- So: If 300 seconds is the window length and request is received at T=301,
    -- failure count is increased in fixed window=300 and so is for 300 and 302
    local fixedWindowParts      = 100

    -- in microseconds
    local fixedWindowLength     = windowLength / fixedWindowParts

    local windowHashKey         = now - (now % fixedWindowLength)

    redis.call('HINCRBY', failureAttemptsHashKey, windowHashKey, 1)
    redis.call('EXPIRE', failureAttemptsHashKey, tonumber(ARGV[1]))
end

-- What is happening below?
local getAllAttemptsAndFailureAttemptsInWindow = function(windowLength, now, allAttemptsHashKey, failureAttemptsHashKey)

    local allKeysOfAllAttempts = redis.call('HGETALL', allAttemptsHashKey)
    local allKeysOfFailureAttempts = redis.call('HGETALL', failureAttemptsHashKey)

    local totalAttempts = 0
    local totalFailureAttempts = 0

    local allAttemptsKeyCount = #allKeysOfAllAttempts
    local failureAttemptsKeyCount = #allKeysOfFailureAttempts

    -- Add Comment: Explain
    local lowestPointOfWindow = (now - windowLength)

    local i = 1
    while allAttemptsKeyCount > 0 and failureAttemptsKeyCount > 0 do
        local hashKeySuccess   =  tonumber(allKeysOfAllAttempts[i])
        -- Add Comment: Explain why hashKeySuccess > lowestPointOfWindow rather
        -- not hashKeySuccess >= lowestPointOfWindow
        if (hashKeySuccess > lowestPointOfWindow)
        then
            local hashValueSuccess =  tonumber(allKeysOfAllAttempts[i+1])
            totalAttempts = totalAttempts + hashValueSuccess
        end

        local hashKeyFailure   =  tonumber(allKeysOfFailureAttempts[i])
        if (hashKeyFailure > lowestPointOfWindow)
        then
            local hashValueFailure =  tonumber(allKeysOfFailureAttempts[i+1])
            totalFailureAttempts = totalFailureAttempts + hashValueFailure
        end

        i = i + 2
        allAttemptsKeyCount = allAttemptsKeyCount - 2
        failureAttemptsKeyCount = failureAttemptsKeyCount - 2
    end

    while allAttemptsKeyCount > 0 do
        local hashKey   =  tonumber(allKeysOfAllAttempts[i])
        if (hashKey > lowestPointOfWindow)
        then
            local hashValue =  tonumber(allKeysOfAllAttempts[i+1])
            totalAttempts = totalAttempts + hashValue
        end

        i = i + 2
        allAttemptsKeyCount = allAttemptsKeyCount - 2
    end

    while failureAttemptsKeyCount > 0 do
        local hashKeyFailure   =  tonumber(allKeysOfFailureAttempts[i])
        if (hashKeyFailure > lowestPointOfWindow)
        then
            local hashValueFailure =  tonumber(allKeysOfFailureAttempts[i+1])
            totalFailureAttempts = totalFailureAttempts + hashValueFailure
        end

        i = i + 2
        failureAttemptsKeyCount = failureAttemptsKeyCount - 2
    end

    return {totalAttempts, totalFailureAttempts}
end

local responseDictionary = {}

-- Loop through each window length passed in arguments and increment
-- the bucket count in failure hash of each window,
-- then return the failure and success count.
for i,v in ipairs(windowLengths) do
    -- window length in microseconds
    local windowLength          = tonumber(v) * 1000000

    -- Add Comment: How are keys stored?
    local successHashKey = key..':ALL_ATTEMPTS:'..windowLength
    local failureHashKey = key..':FAILURE_ATTEMPTS:'..windowLength

    -- First increment the failure count because we only check
    -- if circuit is open in case of failure
    updateFailureCount(windowLength, now, failureHashKey)

    table.insert(responseDictionary, getAllAttemptsAndFailureAttemptsInWindow(windowLength, now, successHashKey, failureHashKey));
end

return responseDictionary;
