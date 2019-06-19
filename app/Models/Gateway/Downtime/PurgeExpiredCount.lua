-- What it does?
--

--
redis.replicate_commands()

-- Gets keys and arguments from command
local key                   = KEYS[1]
local time                  = redis.call("time")
-- window length in microseconds
local windowLength          = tonumber(ARGV[1]) * 1000000

--timeInMicroseconds
local now    = time[1] * 1000000 + time[2]

local allKeys = redis.call('HKEYS', key)

-- loop though all the keys and start deleting if they are not needed.
local keysToDelete = {}
local found = false

for i, hashKey in ipairs(allKeys) do
    hashKey =  tonumber(hashKey)
    if hashKey <= (now - windowLength) then
        found = true
        table.insert(keysToDelete, hashKey)
    end
end

if found == true then
    redis.call('HDEL', key, unpack(keysToDelete))
end

return;
