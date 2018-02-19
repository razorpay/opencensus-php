import axios from 'axios';
import { deepClone } from 'common/util';
import { notifyError } from 'common/modal';

export default function fetch(options, suppressError) {
  return axios(options)
    .then(({ data }) => {
      if (typeof data !== 'object') {
        // Eg- when data is .html template
        return data;
      }

      if (!data.success) {
        if (!suppressError) {
          notifyError(data.errors.join('\n'));
        } else {
          return data;
        }
      } else {
        return data.data || data; // Cases like retry settlement doesn't have data.data but have data.kotak
      }
    })
    .catch(e => notifyError(e));
}

// customUrl must start with '/'. In general, it starts with /admin/api
export function adminFetch(data, customUrl) {
  let url = _baseFetchWithParams(data, customUrl);

  return fetch({
    url,
  });
}

export function adminPost(data, customUrl) {
  let url = '/admin/api/';

  if (typeof data === 'string') {
    url += data; // If only url is passed
  } else {
    data = parseParams(data);
    url += data.url;
    delete data.url;
  }

  return fetch({
    url: customUrl ? customUrl : url,
    method: 'post',
    data,
  });
}

export function adminPut(data, customUrl) {
  let url = '/admin/api/';

  if (typeof data === 'string') {
    // If only url is passed
    url += data;
  } else {
    data = parseParams(data);
    url += data.url;
    delete data.url;
  }

  return fetch({
    url: customUrl ? customUrl : url,
    method: 'put',
    data,
  });
}

// customUrl must start with '/'. In general, it starts with /admin/api
export function adminDelete(data, customUrl) {
  let url = _baseFetchWithParams(data, customUrl);

  return fetch({
    url,
    method: 'delete',
  });
}
// customUrl must start with '/'. In general, it starts with /admin/api
export function adminPatch(data, customUrl) {
  let url = _baseFetchWithParams(data, customUrl);
  return fetch({
    url,
    method: 'patch',
  });
}

function _baseFetchWithParams(data, customUrl) {
  let url = customUrl ? customUrl : '/admin/api/';

  if (typeof data === 'string') {
    url += data; // If only url is passed
  } else {
    if (!customUrl && data.url) {
      url += data.url;
      delete data.url;
    }

    if (data.params) {
      url += '?' + constructQueryString(data.params); // Appends query params to url or customUrl, whatever is present
    }
  }

  return url;
}

export function adminFormUpload(form, customUrl) {
  //Let axios decide which "Content-Type" to send
  let url = customUrl ? customUrl : '/admin/admin';
  let fData = createFormData(form);

  return axios.post(url, fData);
}

//TODO: [CRITICAL] Merchant batch upload broke due to change in createFormData supporting array
export function adminFormUpload2(form, customUrl) {
  //Let axios decide which "Content-Type" to send
  let url = customUrl ? customUrl : '/admin/admin';
  let fData = createFormData2(form);

  return axios.post(url, fData);
}

function parseParams(origParams) {
  let params = deepClone(origParams);

  if (origParams.query_params) {
    params.query_params = JSON.stringify(origParams.query_params);
  }

  return params;
}

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    formData.append(key, form[key]);
  });
  return formData;
};

const createFormData2 = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    if (typeof form[key] === 'object') {
      Object.keys(form[key]).map(item => {
        formData.append(key + '[' + item + ']', form[key][item]); // Object, eg= type:{a:2,b:4} will be sent as type[a] = 2, type[b] = 4 separately
      });
    } else {
      formData.append(key, form[key]);
    }
  });
  return formData;
};

const constructQueryString = params => {
  let query = [];

  for (const k in params) {
    if (params.hasOwnProperty(k)) {
      let val = params[k];
      if (typeof val === 'object') {
        Object.keys(val).map(idx => query.push(`${k}[${idx}]=${val[idx]}`));
      } else {
        query.push(k + '=' + val);
      }
    }
  }

  query = query.join('&');

  return query;
};
