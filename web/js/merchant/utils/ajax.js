import { commonAjax } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';

import { merchantFetch } from './merchantFetch';

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

  return commonAjax(ajaxParams, getMode());
};

export function merchantFetchWithContentType(params, contentType = 'application/json') {
  params.headers = params.headers || {};
  return merchantFetch({
    ...params,
    headers: {
      ...params.headers,
      'Content-Type': contentType,
    },
  });
}

export { merchantFetch };
