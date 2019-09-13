-- Add Comment: What it does?
--

-- Add Comment: Why?
redis.replicate_commands()

-- Gets keys and arguments from command
local key                   = KEYS[1]

local time                  = redis.call("time")
--timeInMicroseconds
local now                   = time[1] * 1000000 + time[2]

-- What is happening below?
local getAllAttemptsAndFailureAttemptsInWindow = function(windowLength, allAttemptsHashKey, failureAttemptsHashKey)

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
for i,v in ipairs(ARGV) do

    -- window length in microseconds
    local windowLength          = tonumber(v) * 1000000

    -- Add Comment: How are keys stored?
    local allAttemptsHashKey = key..':ALL_ATTEMPTS:'..windowLength
    local failureHashKey = key..':FAILURE_ATTEMPTS:'..windowLength

    table.insert(responseDictionary, getAllAttemptsAndFailureAttemptsInWindow(windowLength, allAttemptsHashKey, failureHashKey));
end

return responseDictionary;
