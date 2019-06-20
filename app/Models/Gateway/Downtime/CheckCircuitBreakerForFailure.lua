-- Add Comment: What it does?
--

-- Add Comment: Why?
redis.replicate_commands()

-- Gets keys and arguments from command
local key                   = KEYS[1]
-- window length in microseconds
local windowLength          = tonumber(ARGV[1]) * 1000000

local time                  = redis.call("time")
--timeInMicroseconds
local now                   = time[1] * 1000000 + time[2]

-- Add Comment: How are keys stored?
local successHashKey = key..":SUCCESS"
local failureHashKey = key..":FAILURE"

-- Add Comment: How is the Hash Map maintained?
local updateFailureCount = function(windowLength, now, failureHashKey)
    -- Each sliding time window is divided into 100 fixed time windows.
    -- So: If 300 seconds is the window length and request is received at T=301,
    -- failure count is increased in fixed window=300 and so is for 300 and 302
    local fixedWindowParts      = 100

    -- in microseconds
    local fixedWindowLength     = windowLength / fixedWindowParts

    local windowHashKey         = now - (now % fixedWindowLength)

    redis.call('HINCRBY', failureHashKey, windowHashKey, 1)
    redis.call('EXPIRE', failureHashKey, tonumber(ARGV[1]))
end

-- First increment the failure count because we only check
-- if circuit is open in case of failure
updateFailureCount(windowLength, now, failureHashKey)

-- What is happening below?

local allKeysSuccess = redis.call('HGETALL', key..":SUCCESS")
local allKeysFailure = redis.call('HGETALL', key..":FAILURE")

local totalSuccess = 0
local totalFailure = 0

local successKeyCount = #allKeysSuccess
local failureKeyCount = #allKeysFailure

-- Add Comment: Explain
local lowestPointOfWindow = (now - windowLength)

local i = 1
while successKeyCount > 0 and failureKeyCount > 0 do
    local hashKeySuccess   =  tonumber(allKeysSuccess[i])
    -- Add Comment: Explain why hashKeySuccess > lowestPointOfWindow rather
    -- not hashKeySuccess >= lowestPointOfWindow
    if (hashKeySuccess > lowestPointOfWindow)
    then
        local hashValueSuccess =  tonumber(allKeysSuccess[i+1])
        totalSuccess = totalSuccess + hashValueSuccess
    end

    local hashKeyFailure   =  tonumber(allKeysFailure[i])
    if (hashKeyFailure > lowestPointOfWindow)
    then
        local hashValueFailure =  tonumber(allKeysFailure[i+1])
        totalFailure = totalFailure + hashValueFailure
    end

    i = i + 2
    successKeyCount = successKeyCount - 2
    failureKeyCount = failureKeyCount - 2
end

while successKeyCount > 0 do
    local hashKey   =  tonumber(allKeysSuccess[i])
    if (hashKey > lowestPointOfWindow)
    then
        local hashValue =  tonumber(allKeysSuccess[i+1])
        totalSuccess = totalSuccess + hashValue
    end

    i = i + 2
    successKeyCount = successKeyCount - 2
end

while failureKeyCount > 0 do
    local hashKeyFailure   =  tonumber(allKeysFailure[i])
    if (hashKeyFailure > lowestPointOfWindow)
    then
        local hashValueFailure =  tonumber(allKeysFailure[i+1])
        totalFailure = totalFailure + hashValueFailure
    end

    i = i + 2
    failureKeyCount = failureKeyCount - 2
end

return {totalSuccess, totalFailure}
