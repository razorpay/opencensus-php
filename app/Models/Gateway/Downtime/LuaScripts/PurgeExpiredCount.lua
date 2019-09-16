-- What it does?
--

-- Why?
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


local purgeCount = 0

local purgeKeys = function(windowLength, now, hashKey)
    local allKeys = redis.call('HKEYS', hashKey)

    -- loop though all the keys and start deleting if they are not needed.
    local keysToDelete = {}
    local found = false

    for i, hashKey in ipairs(allKeys) do
        hashKey =  tonumber(hashKey)
        if hashKey <= (now - windowLength) then
            purgeCount = purgeCount + 1
            found = true
            table.insert(keysToDelete, hashKey)
        end
    end

    if found == true then
        --TODO: move it outside to delete together for all windows.
        redis.call('HDEL', hashKey, unpack(keysToDelete))
    end
end

-- Loop through each window length passed in arguments and
-- purge the keys that are not needed any more for that gateway,
-- that includes both all attempts and failure attempts keys.
for i,v in ipairs(windowLengths) do
    -- window length in microseconds
    local windowLength          = tonumber(v) * 1000000

    -- Add Comment: How are keys stored?
    local purgeAllAttemptsKey = key..":ALL_ATTEMPTS:"..windowLength
    local purgeFailureAttemptsKey = key..":FAILURE_ATTEMPTS:"..windowLength

    purgeKeys(windowLength, now, purgeAllAttemptsKey)
    purgeKeys(windowLength, now, purgeFailureAttemptsKey)
end

return purgeCount
