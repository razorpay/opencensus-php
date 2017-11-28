import ajax from 'merchant/utils/ajax';

export const fetch = query => {
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
