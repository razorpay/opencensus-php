import fetch from 'common/fetch';

export const razorxFetch = payload => fetch(_makePayload(payload, 'get'));
export const razorxPost = payload => fetch(_makePayload(payload, 'post'));
export const razorxPut = payload => fetch(_makePayload(payload, 'put'));
export const razorxDelete = payload => fetch(_makePayload(payload, 'delete'));
export const razorxPatch = payload => fetch(_makePayload(payload, 'patch'));

export function _makePayload(payload, type) {
  let reqPayload = {
    method: type,
  };

  if (typeof payload === 'string') {
    reqPayload.url = payload;
  } else {
    reqPayload = { ...reqPayload, ...payload };
  }

  reqPayload.url = 'http://razorx.razorpay.com/' + reqPayload.url;

  return reqPayload;
}
