-- rate_limit_v2.lua

-- This is the exposed module.
local M = {}

local rm_lib                = require 'routes_meta'
local utility               = require "rate_limit_utility"
local fixed_window_script   = require("fixed_window")()

-- local sliding_window_script = require("sliding_window")()       -- Sliding window script is not in use for now.


-- Refer this doc for structure of various rate limit related configurations stored in redis:
-- https://docs.google.com/document/d/1y6pg6S4ofkspLiLTaOYJFbUdDu4EpdNWZ-FBXvflxu0/edit

-- Settings Key Prefix
local route_setting_prefix      = "throttle:{route:"
local merchant_setting_prefix   = "throttle:{merchant:"

-- Prefix for keys used as rate limit identifiers.
local route_identifier_prefix       = "throttle:ri:"
local merchant_identifier_prefix    = "throttle:mi:"

-- Default Route Constant
-- - In case of merchant config it means aggregate of all remaining route not defined at merchant level.
-- - In case of route config it means configuration applicable individually to all the remaining route not defined at route level.
local default_route = "default_route"


-- get_req_ctx parses request and gets contexts(api service specific).
local function get_req_ctx(redis, ngx)
    local req_ctx = {
        mid             = nil,
        auth            = nil,
        route           = nil,
        throttle_skip   = 0,
        mode            = nil
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

    -- Extracting autherization header and Key_id query param
    local auth_scheme, auth_token, key, auth_header
    auth_header = ngx.var.http_authorization
    if auth_header ~=null then
        auth_scheme = string.sub(auth_header, 1, 6)
        if auth_scheme == "Bearer" then
            auth_token = string.sub(auth_header, 8)                                 -- Extracting bearer token from auth header
            if auth_token then
                auth_token = string.match(auth_token, "^%s*(.-)%s*$")
            end
        elseif auth_scheme == "Basic " then
            auth_token = string.sub(auth_header, 7)
            if auth_token then
                auth_token = string.match(auth_token, "^%s*(.-)%s*$")
                key = string.gmatch((ngx.decode_base64(auth_token) or ""), '([^:]+)')()         -- Extracting key from basic auth header
            end
        end
    else
        key = ngx.var.arg_key_id                                                    -- Extracting key_is from query params
    end

    local details, err
    -- supporting throttling for private and public routes as of now
    if req_ctx.auth == "private" or req_ctx.auth == "public" then
        if key ~= nil and ( string.match(key, "^rzp_live_(.-)$") or string.match(key, "^rzp_test_(.-)$") ) then
            if string.find(key, "partner") ~= nil then                              -- Skip for partner auth as not handled.
                req_ctx.throttle_skip = 1
            elseif ngx.req.get_headers()['X-Dashboard-User-Id'] ~= nil then         -- proxy Auth
                details, err = utility.get_details_by_proxy_key(key, ngx)
            elseif string.find(key, "_oauth_") ~= nil then                          -- Oauth Public Auth
                details, err = utility.get_details_by_oauth_token(redis, key, ngx)
            else
                details, err = utility.get_details_by_key(redis, key, ngx)
            end
        elseif auth_token ~= nil and auth_scheme == "Bearer" then                   -- Oauth Bearer Auth
            details, err = utility.get_details_by_bearer_token(auth_token, ngx)
        else
            req_ctx.throttle_skip = 1
        end
    else
        req_ctx.throttle_skip = 1
    end

    if err then
        req_ctx.throttle_skip = 1
    elseif details ~= nil then
        req_ctx.mid     = details.mid
        req_ctx.mode    = details.mode
    end

    ngx.log(ngx.DEBUG, "rate limit request context : ", utility.dump(req_ctx))
    return req_ctx, nil
end

-- get_rate_limit_args method basically gets parameter needed for applying rate
-- limit logic and also the identifier on which it needs to be applied too- all given the request context.
local function get_rate_limit_args(redis, req_ctx, ngx)
    local rate_limit_args = {
        skip        = 0,
        mock        = 0,
        identifier  = nil,
        rc          = 0,
        rcw         = 1
    }

    if req_ctx.throttle_skip == 1 or req_ctx.mid == nil then
        return {skip = 1}, nil
    end

    local mid = req_ctx.mid

    local settings = {}
    -- Fetch redis settings in order of:
    -- - Route setting
    -- - Default route setting
    -- - Merchant setting
    local start_time = ngx.now()
    local metric_label = "pipelined_HGETALL_2"
    redis:init_pipeline()
    redis:hgetall(route_setting_prefix .. req_ctx.route .. "}")
    redis:hgetall(route_setting_prefix .. default_route .. "}")
    if mid ~= nil then
        metric_label = "pipelined_HGETALL_3"
        redis:hgetall(merchant_setting_prefix .. mid .. "}")
    end
    local raw_settings, err = redis:commit_pipeline()
    utility.log_redis_latency_metric(start_time, metric_label, err)
    if err then
        return nil, "failed to get settings from redis: " .. err
    end

    -- Formats redis response in settings key<>value pair for ease use further.
    for k, v in pairs(raw_settings) do
        if type(v) == "table" then
            settings[k] = {}
            for i = 1, #v, 2 do
                settings[k][v[i]] = v[i + 1]
            end
        end
    end
    -- ngx.log(ngx.DEBUG, "settings for rate_limit_args : ", utility.dump(settings))

    local merchant_settings = settings[3] or {}
    local route_settings = ((next(settings[1]) ~= nil) and settings[1]) or settings[2]  -- Route specific Setting if present else default route setting

    local key, rc, rcw, scope

    if next(merchant_settings) ~= nil and merchant_settings[req_ctx.route .. ":request_count"] ~= nil then
        key = merchant_identifier_prefix .. req_ctx.route .. ":" .. mid
        rc  = merchant_settings[req_ctx.route .. ":request_count"]
        rcw = merchant_settings[req_ctx.route .. ":request_count_window"]
    elseif next(merchant_settings) ~= nil and merchant_settings[default_route .. ":request_count"] ~= nil then
        key = merchant_identifier_prefix .. default_route .. ":" .. mid
        rc  = merchant_settings[default_route .. ":request_count"]
        rcw = merchant_settings[default_route .. ":request_count_window"]
    elseif next(route_settings) ~= nil then
        scope = route_settings[req_ctx.route .. ":type"] or route_settings[default_route .. ":type"]
        key   = route_identifier_prefix .. req_ctx.route .. ((scope ~= "org") and (":" .. mid) or "")
        rc    = route_settings[req_ctx.route .. ":request_count"] or route_settings[default_route .. ":request_count"]
        rcw   = route_settings[req_ctx.route .. ":request_count_window"] or route_settings[default_route .. ":request_count_window"]
    else
        rate_limit_args.skip = 1
    end

    rate_limit_args.identifier  = key
    rate_limit_args.rc          = rc
    rate_limit_args.rcw         = rcw

    if (rc ~= nil and tonumber(rc) < 1) or rc == nil or rcw == nil or key == nil then
        rate_limit_args.skip = 1
    end
    ngx.log(ngx.DEBUG, "rate limit args : ", utility.dump(rate_limit_args))
    return rate_limit_args, nil
end

-- Execute/Apply rate limit given the rate limit argument
local function rate_limit(redis, rate_limit_args)

    if rate_limit_args.skip == 1 then
        return nil, nil
    end

    local start_time = ngx.now()
    local res, err = redis:eval(
        fixed_window_script,
        1,
        rate_limit_args.identifier,
        rate_limit_args.rc,
        rate_limit_args.rcw
    )
    utility.log_redis_latency_metric(start_time, "EVAL", err)
    if err then
        return nil, err
    end

    return {
        allowed   = res[1],
        current   = res[2],
        remaining = res[3]
    }
end

-- rate_limit_ngx is the exposed method via module and it gets nginx context
-- and either terminates the request with 429 or does nothing and lets it proxy
-- pass to upstream.
function M.rate_limit_ngx(ngx)
    local redis, err = utility.get_redis_conn(ngx)
    if err then
        ngx.log(ngx.ERR, "failed to get redis conn: ", err)
        return
    end
    local req_ctx, err = get_req_ctx(redis, ngx)
    if err then
        ngx.log(ngx.ERR, "failed to get request context: ", err)
        return
    end

    local rate_limit_args, err = get_rate_limit_args(redis, req_ctx, ngx)
    if err then
        ngx.log(ngx.ERR, "failed to get rate_limit args: ", err)
        return
    end

    rate_limit_args.now = ngx.now()
    local rate_limit_res, err = rate_limit(redis, rate_limit_args)
    if err then
        ngx.log(ngx.ERR, "failed to rate_limit: ", err)
        return
    end

    if rate_limit_res ~= nil and rate_limit_res.allowed ~= 1 then
        if rate_limit_args.mock == 1 then
            -- Todo: Log warn with contextual information.
            return
        end
        ngx.log(ngx.INFO, "merchant request throttled : ", utility.dump(rate_limit_args))
        ngx.header["X-RateLimit-Limit"] = rate_limit_args.rc
        ngx.header["X-RateLimit-Current"] = rate_limit_res.current
        ngx.header["X-RateLimit-Remaining"] = rate_limit_res.remaining
        ngx.exit(ngx.HTTP_TOO_MANY_REQUESTS)
    end
end


--Readiness probe for the application , basically checks if the app is able to establish redis connection
function M.check_redis_connection(ngx)
    local redis, err = utility.get_redis_conn(ngx)
    if err then
        ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
    else
        ngx.exit(ngx.HTTP_OK)
    end
end

return M
