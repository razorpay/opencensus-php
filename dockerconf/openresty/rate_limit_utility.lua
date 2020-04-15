-- rate_limit_utility.lua

-- This is the exposed module.
local M = {}

local redis_lib = require "resty.redis"
local sha1 = require "resty.sha1"
local sha256 = require "resty.sha256"
local str = require "resty.string"
local jwt = require "resty.jwt"

-- Cache Key prefix to fetch mid from merchant key
local merchant_key_prefix = "throttle:t:km:"

-- The environment variables below are made available via nginx's env
local redis_conf = {
    timeout_ms  = os.getenv("RESTY_REDIS_TIMEOUT_MS") or 100,
    host        = os.getenv("RESTY_REDIS_HOST") or "127.0.0.1",
    port        = os.getenv("RESTY_REDIS_PORT") or 6379,
    password    = os.getenv("RESTY_REDIS_PASSWORD") or nil,
    max_idle_ms = os.getenv("RESTY_REDIS_MAX_IDLE_MS") or 10000,
    pool_size   = os.getenv("RESTY_REDIS_POOL_SIZE") or 100,
}

-- get_redis_conn gets a new connection from redis pool of connections.
function M.get_redis_conn(ngx)
    local redis = redis_lib:new()
    redis:set_timeout(redis_conf.timeout_ms)
    ngx.log(ngx.DEBUG, "Lua Redis Host : ", redis_conf.host, redis_conf.port)
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

-- release_redis_conn releases redis connection. Hehe.
function M.release_redis_conn(redis)
    local ok, err = redis:set_keepalive(redis_conf.max_idle_ms, redis_conf.pool_size)
    return err
end

-- get_details_by_bearer_token method basically gets jwt Oauth Token details using jwt decode
function M.get_details_by_bearer_token(token, ngx)
    local tok_det = {
        mid    = nil,
        mode   = "live"
    }
    local jwt_obj = jwt:load_jwt(token)
    -- ngx.log(ngx.DEBUG, "jwt token : ", M.dump(jwt_obj))
    if jwt_obj ~= nil and jwt_obj["payload"] ~= nil then
        tok_det.mid = jwt_obj["payload"]["merchant_id"]
    end
    -- ngx.log(ngx.DEBUG, "get_details_by_bearer_token mid :  ", tok_det.mid)
    return tok_det, nil
end

-- get_details_by_oauth_token method basically gets Oauth Token details using redis
function M.get_details_by_oauth_token(redis, token, ngx)
    local tok_det = {
        mid    = nil,
        mode   = nil
    }

    local sha_256 = sha256:new()
    sha_256:update(token)
    local tokenHash = str.to_hex(sha_256:final())
    -- ngx.log(ngx.DEBUG, "token hash :  ", tokenHash)
    local res, err = redis:get("laravel:tag:auth_token_" .. tokenHash .. ":key")
    if err then
        return nil, "redis error, failed to fetch token tag"
    end
    -- ngx.log(ngx.DEBUG, "tag key value :  ", res)
    if res ~= ngx.null then
        local sha_1 = sha1:new()
        sha_1:update(string.match(res, "\"(.*)\""))
        local tagKeyHash = str.to_hex(sha_1:final())
        -- ngx.log(ngx.DEBUG, "tag key hash :  ", tagKeyHash)
        local res, err = redis:get("laravel:".. tagKeyHash .. ":rememberable:v1:auth_token:auth_token_" .. tokenHash)
        if err then
            return nil, "redis error, failed to fetch token"
        end
        -- ngx.log(ngx.DEBUG, "token object :  ", res)
        if res ~= ngx.null then
            tok_det.mid     = string.match(res, "\"merchant_id\";s:14:\"([^\"]+)")
            tok_det.mode    = string.match(res, "\"mode\";s:4:\"([^\"]+)")
        end
    end
    -- ngx.log(ngx.DEBUG, "get_details_by_oauth_token mid :  ", tok_det.mid)
    return tok_det, nil
end

function M.parse_mode_from_key(key)
    local mode = key and string.sub(key, 5, 8)
    if mode ~= "live" and mode ~= "test" then               -- defaulting mode to live
        mode = "live"
    end
    return mode
end

-- get_details_by_key method fetches merchant ID details for a key
function M.get_details_by_key(redis, key, ngx)
    local merchant = {
        mid = nil,
        mode = nil
    }
    local res, err = redis:get(merchant_key_prefix .. key)
    if err then
        return nil, "redis error, failed to fetch Merchant for given key"
    end
    if res ~= ngx.null then
        merchant.mid = res
    end
    merchant.mode = M.parse_mode_from_key(key)
    -- ngx.log(ngx.DEBUG, "get_details_by_key mid :  ", merchant.mid)
    return merchant, nil
end

-- get_details_by_proxy_key method fetches merchant details for a key
function M.get_details_by_proxy_key(key, ngx)
    local merchant = {
        mid = nil,
        mode = nil
    }
    merchant.mid    = string.sub(key, 10)
    merchant.mode   = M.parse_mode_from_key(key)
    -- ngx.log(ngx.DEBUG, "get_details_by_proxy_key mid :  ", merchant.mid)
    return merchant, nil
end

function M.dump(o)
    if type(o) == 'table' then
        local s = '{ '
        for k,v in pairs(o) do
            if type(k) ~= 'number' then k = '"'..k..'"' end
            s = s .. '['..k..'] = ' .. M.dump(v) .. ','
        end
        return s .. '} '
    else
        return tostring(o)
    end
end

return M
