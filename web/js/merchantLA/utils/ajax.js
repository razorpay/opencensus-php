import ajax from 'rzp/utils/ajax';
import { getMode } from 'merchantLA/store';

export function merchantFetch(params) {
  if (typeof params === 'string') {
    params = {
      url: params,
    };
  }

  let mode = params.mode;
  if (mode) {
    delete params.mode;
  } else {
    mode = getMode();
  }

  if (params.accountId) {
    params.headers = {
      ...params.headers,
      'X-Razorpay-Account': params.accountId,
    };
  }
  delete params.accountId;

  params.url = `/merchant/api/${mode}/${params.url}`;

  return ajax(params);
}

export default (url, params = {}, baseUrl = '') => {
  if (typeof url === 'object') {
    params = url;
  } else if (typeof url === 'string') {
    params.url = url;
  }

  let {
    appendModeInURL = true,
    appendModeInQueryParam,
    ...ajaxParams
  } = params;
  let mode = (params.data && params.data.mode) || getMode();
  ajaxParams.url = normalizeUrl(params.url);
  if (appendModeInQueryParam) {
    ajaxParams.data.mode = mode;
  } else if (appendModeInURL) {
    ajaxParams.url = normalizeUrl(`${baseUrl}/${mode}/${params.url}`);
    // to clear mode from data if present
    if (params.data) {
      params.data.mode = undefined;
    }
  }

  return ajax(ajaxParams, getMode());
};

// Replaces consecutive & trailing slashes from the URL
const normalizeUrl = url => {
  return url.replace(/([^:]\/)\/+/g, '$1').replace(/\/$/, '');
};
