import fetch from 'common/fetch';

/*
  For all custom rexFetch, Post, /etc helpers, payload must have relative url to "/admin/api/"
  Eg: rexFetch({url: '{mode}/your_url'}), or rexFetch('{mode}/your_url')
*/
export const rexFetch = payload => fetch(_makePayload(payload, 'get'));
export const rexPost = payload => fetch(_makePayload(payload, 'post'));
export const rexPut = payload => fetch(_makePayload(payload, 'put'));
export const rexDelete = payload => fetch(_makePayload(payload, 'delete'));
export const rexPatch = payload => fetch(_makePayload(payload, 'patch'));

function _makePayload(payload, type) {
  let reqPayload = {
    method: type,
  };

  if (typeof payload === 'string') {
    reqPayload.url = payload;
  } else {
    reqPayload = { ...reqPayload, ...payload };
  }
  // TODO: Construct payload here as per makeapicall:
  // http://dashboard.razorpay.in/makeapicall/service/razorx?service_path=experiments
  /*
  {
    mode: 'live',
    body: {""}
    params: // To be attached in URL itself
  }
*/

  reqPayload.url = '/makeapicall/service/razorx?' + reqPayload.url; // final url is "/admin/api/+url"

  return reqPayload;
}
