-- fixed_window.lua

local fixed_window_script = [==[
local key   = KEYS[1]
local rc    = tonumber(ARGV[1])
local rcw   = tonumber(ARGV[2])
local current = tonumber(redis.call('incr', key))
-- Returned value 1 means key was set for the first time and so set proper TTL.
if current == 1 then
    rcw = math.max(rcw, 1)
    redis.call('expire', key, rcw)
end
local remaining = rc - current
local allow_attempt = remaining > -1
return {
  allow_attempt,
  current,
  remaining
}
]==]

return function ()
    return fixed_window_script
end
