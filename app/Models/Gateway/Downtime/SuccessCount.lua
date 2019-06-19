-- What it does?
--

--
redis.replicate_commands()

-- Gets keys and arguments from command
local key                   = KEYS[1]
local time                  = redis.call("time")

--timeInMicroseconds
local now    = time[1] * 1000000 + time[2]

-- window length in microseconds
local windowLength          = tonumber(ARGV[1]) * 1000000
local fixedWindowParts      = 100

-- in microseconds
local fixedWindowLength     = windowLength / fixedWindowParts

local windowHashKey         = now - (now % fixedWindowLength)

redis.call('HINCRBY', key, windowHashKey, 1)
redis.call('EXPIRE', key, tonumber(ARGV[1]))
return;
