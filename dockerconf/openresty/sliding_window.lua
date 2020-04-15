-- leaky_bucket.lua
-- Refer: https://github.com/razorpay/hodor.

local sliding_window_script = [==[
local key               = KEYS[1]
local rc                = tonumber(ARGV[1])
local rcw               = tonumber(ARGV[2])
local now               = tonumber(redis.call('time')[1])
redis.call('zremrangebyscore', key, 0, (now - (rcw*1000))
local current = tonumber(redis.call('zcount', key, 0, now))
if current < rc then
    redis.call('zadd', key, now, now)
    redis.call('expire', key, rcw)
end
local allow_attempt = current <= rc
local remaning = rc - current
return {
      allow_attempt,
      current,
      remaning
}
]==]

return function ()
    return sliding_window_script
end
