-- rate_limit.lua

-- This is the exposed module.
local M = {}

local redis_lib = require "resty.redis"
local rm_lib = require 'routes_meta'

-- See get_redis_script_sha.
local redis_script_sha
local redis_global_settings_key = "throttle:t"
local redis_key_prefix = "throttle:t:"
-- The environment variables below are made available via nginx's env
-- directive in http block.
local redis_conf = {
    timeout_ms  = os.getenv("RESTY_REDIS_TIMEOUT_MS") or 1000,
    host        = os.getenv("RESTY_REDIS_HOST") or "127.0.0.1",
    port        = os.getenv("RESTY_REDIS_PORT") or 6379,
    password    = os.getenv("RESTY_REDIS_PASSWORD") or nil,
    max_idle_ms = os.getenv("RESTY_REDIS_MAX_IDLE_MS") or 10000,
    pool_size   = os.getenv("RESTY_REDIS_POOL_SIZE") or 100,
}

-- get_redis_conn gets a new connection from redis pool of connections.
local function get_redis_conn()
    local redis = redis_lib:new()
    redis:set_timeout(redis_conf.timeout_ms)
    local ok, err = redis:connect(redis_conf.host, redis_conf.port)
    if err then
        return nil, "failed to connect to redis host: " .. err
    end
    if redis_conf.password ~= nil then
        local res, err = redis:auth(redis_conf.password)
        if err then
            return nil, "failed to authenticate to redis host: " .. err
        end
    end
    return redis, err
end

-- get_redis_script_sha gets the sha of stored script(in redis) if exists
-- or stores into redis first.
local function get_redis_script_sha(redis)
    if not redis_script_sha then
        local sha, err = redis:script("LOAD", require("leaky_bucket")())
        if err then
            return nil, err
        end
        redis_script_sha = sha
    end
    return redis_script_sha, nil
end

-- get_req_ctx parses request and gets contexts(api service specific).
local function get_req_ctx(ngx)
    local req_ctx = {
        user = nil,
        mode = nil,
        auth = nil,
        proxy = nil,
        route = nil,
    }

    -- Sets req_ctx.{route,auth}.
    for i = 0, rm_lib.routes_meta_count - 1 do
        if rm_lib.routes_meta[i].methods[ngx.var.request_method] then
            if string.find(ngx.var.uri, rm_lib.routes_meta[i].uri_regex) then
                req_ctx.route = rm_lib.routes_meta[i].name
                req_ctx.auth = rm_lib.routes_meta[i].auth
                break
            end
        end
    end

    -- Sets req_ctx.{proxy, user, mode}.
    -- Assumption: Dashboard backend sends this particular header during proxy requests.
    -- Assumption: We are only handling private and proxy auth rate limiting
    -- and that too for normal cases and not oauth and route etc.
    req_ctx.proxy = ngx.req.get_headers()['X-Dashboard-User-Id'] ~= nil
    local auth_base64 = ngx.var.http_authorization and string.sub(ngx.var.http_authorization, 7)
    if auth_base64 ~= nil then
        local auth_user_pass = ngx.decode_base64(auth_base64)
        if auth_user_pass ~= nil then
            req_ctx.user = string.gmatch(auth_user_pass, '([^:]+)')()
        end
    end
    local mode = req_ctx.user and string.sub(req_ctx.user, 5, 8)
    if mode == "live" or mode == "test" then
        req_ctx.mode = mode
    end

    return req_ctx, nil
end

-- get_rate_limit_args method basically gets parameter needed for applying rate
-- limit logic and also the identifier on which it needs to be applied too- all
-- given the request context.
local function get_rate_limit_args(redis, req_ctx)
    local rate_limit_args = {
        skip = 1,
        mock = 1,
        identifier = nil,
        lrv = 2,
        lrd = 1,
        mbs = 30,
    }

    -- Finds mid.
    -- Assumption: We are only handling private and proxy auth rate limiting
    -- and that too for normal cases and not oauth and route etc.
    local mid
    if req_ctx.auth == "private" then
        -- If request is proxy then user definitely has mid.
        if req_ctx.proxy then
            mid = string.sub(req_ctx.user, 10)
        -- Else if is partner etc just skip throttling. Not handling intentionally.
        elseif string.find(req_ctx.user, "partner") ~= nil then
            return {skip = 1}, nil
        -- Else remap public key id to mid.
        else
            local res, err = redis:get(redis_key_prefix .. "km:" .. req_ctx.user)
            if err then
                return nil, "failed to get key<>mid mapping from redis: " .. err
            end
            if res == ngx.null then
                return nil, "key<>mid mapping does not exists"
            end
            mid = res
        end
    else
        return {skip = 1}, nil
    end

    -- Sets rate_limit_args.identifier.
    rate_limit_args.identifier = req_ctx.route .. ":" .. req_ctx.mode .. ":" .. req_ctx.auth .. ":" .. (req_ctx.proxy and 1 or 0) .. "::" .. mid .. "::"

    -- Loads global and mid specific settings.
    redis:init_pipeline()
    redis:hgetall(redis_global_settings_key)
    redis:hgetall(redis_key_prefix .. mid)
    local raw_res, err = redis:commit_pipeline()
    if err then
        return nil, "failed to get settings from redis: " .. err
    end

    -- Formats redis response in settings key<>value pair for ease use further.
    local res = {}
    for k, v in pairs(raw_res) do
        if type(v) == "table" then
            res[k] = {}
            for i = 1, #v, 2 do
                res[k][v[i]] = v[i + 1]
            end
        end
    end

    -- In cascading fashion reads the settings out for each key.
    local settings_keys = {"skip", "mock", "lrv", "lrd", "mbs"}
    for i, k in pairs(settings_keys) do
        rate_limit_args[k] = tonumber(
            -- Value for given mid, mode, auth & route
            (res[2] and res[2][req_ctx.mode .. ":" .. req_ctx.auth .. ":" .. (req_ctx.proxy and 1 or 0) .. ":" .. req_ctx.route .. ":" .. k]) or
            -- Value for given mid, mode & auth
            (res[2] and res[2][req_ctx.mode .. ":" .. req_ctx.auth .. ":" .. (req_ctx.proxy and 1 or 0) .. ":" .. k]) or
            -- Value for given mid & mode
            (res[2] and res[2][req_ctx.mode .. ":" .. k]) or
            -- Value for given mid
            (res[2] and res[2][k]) or
            -- Value for given mode, auth & route
            (res[1] and res[1][req_ctx.mode .. ":" .. req_ctx.auth .. ":" .. (req_ctx.proxy and 1 or 0) .. ":" .. req_ctx.route .. ":" .. k]) or
            -- Value for given mode & auth
            (res[1] and res[1][req_ctx.mode .. ":" .. req_ctx.auth .. ":" .. (req_ctx.proxy and 1 or 0) .. ":" .. k]) or
            -- Value for given mode
            (res[1] and res[1][req_ctx.mode .. ":" .. k]) or
            -- Finally, global default value
            (res[1] and res[1][k]) or
            -- Again finally, the default:)
            rate_limit_args[k])
    end

    return rate_limit_args, nil
end

-- rate_limit method rocks!
local function rate_limit(redis, rate_limit_args, now)
    local redis_script_sha, err = get_redis_script_sha(redis)
    if err then
        return nil, err
    end

    local res, err = redis:evalsha(
        redis_script_sha,
        1,
        redis_key_prefix .. rate_limit_args.identifier,
        rate_limit_args.mbs,
        rate_limit_args.lrv,
        rate_limit_args.lrd,
        math.ceil(rate_limit_args.mbs*rate_limit_args.lrd/rate_limit_args.lrv),
        rate_limit_args.now,
        1
    )
    if err then
        redis_script_sha = nil
        return nil, err
    end

    return {
        allowed = res[1],
        limit = res[2],
        remaining = res[3],
        reset_at = res[4],
        retry_after = res[5],
    }
end

-- release_redis_conn releases redis connection. Hehe.
local function release_redis_conn(redis)
    local ok, err = redis:set_keepalive(redis_conf.max_idle_ms, redis_conf.pool_size)
    return err
end

-- rate_limit_ngx is the exposed method via module and it gets nginx context
-- and either terminates the request with 429 or does nothing and lets it proxy
-- pass to upstream.
function M.rate_limit_ngx(ngx)
    local redis, err = get_redis_conn()
    if err then
        ngx.log(ngx.ERR, "failed to get redis conn: ", err)
        return
    end

    local req_ctx, err = get_req_ctx(ngx)
    if err then
        ngx.log(ngx.ERR, "failed to get request context: ", err)
        return
    end

    local rate_limit_args, err = get_rate_limit_args(redis, req_ctx)
    if err then
        ngx.log(ngx.ERR, "failed to get rate_limit args: ", err)
        return
    end

    if rate_limit_args.skip == 1 then
        return
    end
    rate_limit_args.now = ngx.now()
    local rate_limit_res, err = rate_limit(redis, rate_limit_args)
    if err then
        ngx.log(ngx.ERR, "failed to rate_limit: ", err)
        return
    end

    err = release_redis_conn(redis)
    if err then
        ngx.log(ngx.ERR, "failed to release redis conn: ", err)
    end

    if not rate_limit_res.allowed then
        if rate_limit_args.mock == 1 then
            -- Todo: Log warn with contextual information.
            return
        end
        ngx.header["X-RateLimit-Limit"] = rate_limit_res.limit
        ngx.header["X-RateLimit-Remaining"] = rate_limit_res.remaining
        ngx.header["X-RateLimit-ResetAt"] = rate_limit_res.reset_at
        ngx.header["X-RateLimit-RetryAfter"] = rate_limit_res.retry_after
        ngx.exit(ngx.HTTP_TOO_MANY_REQUESTS)
    end
end

return M
