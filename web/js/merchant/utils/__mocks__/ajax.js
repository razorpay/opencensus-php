// TODO: Remove this once cyclic dependency has been resolved in utils/ajax
import ajax from 'common/utils/ajax';

// Replaces consecutive & trailing slashes from the URL
const normalizeUrl = (url) => {
  return url.replace(/([^:]\/)\/+/g, '$1').replace(/\/$/, '');
};

const mockAjax = (url, params = {}, baseUrl = '') => {
  if (typeof url === 'object') {
    params = url;
  } else if (typeof url === 'string') {
    params.url = url;
  }

  const { appendModeInURL = true, appendModeInQueryParam, ...ajaxParams } = params;
  const mode = (params.data && params.data.mode) || 'test';
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

  return ajax(ajaxParams, 'test');
};

module.exports = (...args) => mockAjax(...args);
