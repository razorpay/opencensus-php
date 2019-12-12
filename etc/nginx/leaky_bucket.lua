-- leaky_bucket.lua
-- Refer: https://github.com/razorpay/hodor.

local leaky_bucket_script = [==[
local key               = KEYS[1]
local mbs               = tonumber(ARGV[1])
local lrv               = tonumber(ARGV[2])
local lrd               = tonumber(ARGV[3])
local lft               = tonumber(ARGV[4])
local now               = tonumber(ARGV[5])
local cost              = tonumber(ARGV[6])
local retry_after       = -1
local leak              = 0
local allow_attempt     = false
local last_updated_key  = 'last_updated'
local bucket_size_key   = 'bucket_size'
-- Read value of hash with given key, extracts last updated and bucket size.
local current       = redis.call('hmget', key, last_updated_key, bucket_size_key)
local last_updated  = tonumber(current[1]) or 0
local bucket_size   = tonumber(current[2]) or 0
-- Calculates units that should have been leaked between now and the time key got last updated.
leak = math.floor(math.max(0, now - last_updated) * lrv / lrd)
last_updated = now
-- Calculates new bucket size.
-- 1. Handles case when max bucket size is changed in subsequent limiter requests.
bucket_size = math.min(bucket_size, mbs)
-- 2. Now leaks from current bucket size.
bucket_size = math.max(0, bucket_size - leak)
-- Determine now if attempt for cost should be allowed or not.
-- If allowed fill in cost unit in the bucket and change related vars accordingly.
allow_attempt = bucket_size + cost <= mbs
if allow_attempt then
      bucket_size = bucket_size + cost
else
      retry_after = now + lrd
end
-- Finally set the values and TTL for the redis hash.
redis.call('hmset', key, last_updated_key, last_updated, bucket_size_key, bucket_size)
redis.call('expire', key, lft)
-- Returns-
-- - Whether to allow attempt.
-- - Max bucket size i.e. limit.
-- - Remaining allowed attempts.
-- - Reset time.
-- - Minimum retry after time.
return {
      allow_attempt,
      mbs,
      mbs - bucket_size,
      now + lft,
      retry_after
}
]==]

return function ()
	return leaky_bucket_script
end
