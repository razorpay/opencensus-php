-- rate_limit.lua
-- Todo: Write comments.

local leaky_bucket_script_sha

function get_redis_conn(redis_conf)
    local redis_lib = require "resty.redis"
    local redis = redis_lib:new()
    local timeout = redis_conf.timeout
    redis:set_timeouts(timeout, timeout, timeout)
    local ok, err = redis:connect(redis_conf.host, redis_conf.port)

    return redis, err
end

function get_req_ctx(ngx)
    local req_ctx = {
        user = nil,
        mode = nil,
        auth = nil,
        proxy = nil,
        route = nil,
    }

    -- Sets req_ctx.{route,auth}.
    local method = ngx.var.request_method
    local uri = ngx.var.uri
    require 'routes_meta'
    for i=0,routes_meta_count-1 do
        if routes_meta[i].methods[method] then
            if string.find(uri, routes_meta[i].uri_regex) then
                req_ctx.route = routes_meta[i].name
                req_ctx.auth = routes_meta[i].auth
            end
        end
    end

    -- Sets req_ctx.{user, mode, proxy}.
    -- Assumption: Dashboard backend sends this particular header during proxy requests.
    local dashboard_user_id = ngx.req.get_headers()['X-Dashboard-User-Id']
    req_ctx.proxy = dashboard_user_id ~= nil
    local auth_base64 = string.sub(ngx.var.http_authorization, 7)
    if auth_base64 ~= nil then
        local auth_user_pass = ngx.decode_base64(auth_base64)
        if auth_user_pass ~= nil then
            req_ctx.user = string.gmatch(auth_user_pass, '([^:]+)')()
        end
    end

    -- Sets req_ctx.mode.
    local mode = string.sub(req_ctx.user, 5, 8)
    if mode == "live" or mode == "test" then
        req_ctx.mode = mode
    end

    return req_ctx, nil
end

function get_rate_limit_args(redis, req_ctx)
    local rate_limit_args = {
        skip = false,
        mock = true,
        identifier = nil,
        lrv = 2,
        lrd = 1,
        mbs = 30,
    }
    -- Todo: Implement this.

    return rate_limit_args, nil
end

function rate_limit(redis, rate_limit_args, now)
    leaky_bucket_script_sha, err = get_leaky_bucket_script_sha(redis)
    if err then
        return nil, err
    end

    local res, err = redis:evalsha(
        leaky_bucket_script_sha,
        1,
        rate_limit_args.identifier,
        rate_limit_args.mbs,
        rate_limit_args.lrv,
        rate_limit_args.lrd,
        math.ceil(rate_limit_args.mbs*rate_limit_args.lrd/rate_limit_args.lrv),
        rate_limit_args.now,
        1
    )
    if err then
        leaky_bucket_script_sha = nil
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

function release_redis_conn(redis, redis_conf)
    local ok, err = redis:set_keepalive(redis_conf.max_idle_ms, redis_conf.pool_size)

    return err
end

function get_leaky_bucket_script_sha(redis)
    if not leaky_bucket_script_sha then
        local sha, err = redis:script("LOAD", require("leaky_bucket")())
        if err then
            return nil, err
        end

        leaky_bucket_script_sha = sha
    end

    return leaky_bucket_script_sha, nil
end

function rate_limit_ngx(ngx)
    redis_conf = {
        timeout = os.getenv("RESTY_REDIS_TIMEOUT") or 1000,
        host = os.getenv("RESTY_REDIS_HOST") or "127.0.0.1",
        port = os.getenv("RESTY_REDIS_PORT") or 6379,
        max_idle_ms = os.getenv("RESTY_REDIS_MAX_IDLE_MS") or 10000,
        pool_size = os.getenv("RESTY_REDIS_POOL_SIZE") or 100,
    }

    local err

    redis, err = get_redis_conn(redis_conf)
    if err then
        ngx.log(ngx.ERR, "failed to get redis conn: ", err)
        return
    end

    req_ctx, err = get_req_ctx(ngx)
    if err then
        ngx.log(ngx.ERR, "failed to get request context: ", err)
        return
    end

    rate_limit_args, err = get_rate_limit_args(redis, req_ctx)
    if err then
        ngx.log(ngx.ERR, "failed to get rate_limit args: ", err)
        return
    end

    if rate_limit_args.skip then
        return
    end
    rate_limit_args.now = ngx.now()
    rate_limit_res, err = rate_limit(redis, rate_limit_args)
    if err then
        ngx.log(ngx.ERR, "failed to rate_limit: ", err)
        return
    end

    err = release_redis_conn(redis, redis_conf)
    if err then
        ngx.log(ngx.ERR, "failed to release redis conn: ", err)
    end

    if not rate_limit_res.allowed then
        if rate_limit_args.mock then
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
