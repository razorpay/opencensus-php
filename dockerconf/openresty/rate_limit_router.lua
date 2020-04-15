local M = {}

local version  = os.getenv("RESTY_TROTTLE_VERSION") or "v2"

local v1 = require 'rate_limit'
local v2 = require 'rate_limit_v2'

function M.rate_limit_ngx(ngx)
    if version == "v1" then
        v1.rate_limit_ngx(ngx)
    else
        local status, err = xpcall(v2.rate_limit_ngx, debug.traceback, ngx)
        if err then
            ngx.log(ngx.ERR, "rate limit route error : ", status, err)
        end
    end
end

function M.check_redis_connection(ngx)
    if version == "v1" then
        v1.check_redis_connection(ngx)
    else
        v2.check_redis_connection(ngx)
    end
end

return M