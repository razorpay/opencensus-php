import ajax from 'rzp/utils/ajax';
import store from '../store';

export default (url, params = {}) => {
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
  let mode = (params.data && params.data.mode) || store.getState().session.mode;
  ajaxParams.url = normalizeUrl(params.url);

  if (appendModeInQueryParam) {
    ajaxParams.data.mode = mode;
  } else if (appendModeInURL) {
    ajaxParams.url = normalizeUrl(`/${mode}/${params.url}`);
  }

  return ajax(ajaxParams);
};

// Replaces consecutive & trailing slashes from the URL
const normalizeUrl = url => {
  return url.replace(/([^:]\/)\/+/g, '$1').replace(/\/$/, '');
};
