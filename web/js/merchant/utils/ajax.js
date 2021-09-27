import ajax from 'common/utils/ajax';
import { getMode } from 'merchant/store';

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

  if (window.RZP.appName === 'businessbanking') {
    params.headers = {
      ...params.headers,
      'X-Origin-Product': window.RZP.appHost,
    };
  }

  delete params.accountId;

  params.url = params.absUrl ? params.absUrl : `/merchant/api/${mode}/${params.url}`;

  if (params.absUrl) {
    delete params.absUrl;
  }

  return ajax(params);
}

// Replaces consecutive & trailing slashes from the URL
const normalizeUrl = (url) => {
  return url.replace(/([^:]\/)\/+/g, '$1').replace(/\/$/, '');
};

export default (url, params = {}, baseUrl = '') => {
  if (typeof url === 'object') {
    params = url;
  } else if (typeof url === 'string') {
    params.url = url;
  }

  const { appendModeInURL = true, appendModeInQueryParam, ...ajaxParams } = params;
  const mode = (params.data && params.data.mode) || getMode();
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
