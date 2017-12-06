import ajax from 'merchant/utils/ajax';

export const fetch = query => {
  /*
   * given query according to
   * PQL - https://docs.google.com/document/d/1sa8Us-sDYkTFYcWUKjT02-qvL-j9GEejiZyAs0MiAhs/edit , makes ajax call
   */

  return ajax('/user/generic', {
    appendModeInURL: false,
    method: 'post',
    contentType: 'application/json',
    data: JSON.stringify({
      route_name: 'merchant_analytics',
      body: query,
    }),
  });
};
