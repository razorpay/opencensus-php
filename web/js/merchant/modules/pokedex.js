import ajax from 'merchant/utils/ajax';

export var pokeConfig = {
  merchantId: '',
};

export const fetch = (query, mode) => {
  /*
   * given query according to
   * PQL - https://docs.google.com/document/d/1sa8Us-sDYkTFYcWUKjT02-qvL-j9GEejiZyAs0MiAhs/edit , makes ajax call
   */

  let data = {
    route_name: 'merchant_analytics',
    body: query,
  };

  let url = '/user/generic';

  if (pokeConfig.merchantId) {
    mode = 'live';
    data.merchant_id = pokeConfig.merchantId;
    data.account_id = pokeConfig.merchantId;
    url = '/admin/generic';
  }

  Object.keys(query.aggregations).forEach(aggKey => {
    const aggDetails = query.aggregations[aggKey].details;

    aggDetails.mode = aggDetails.mode || mode;
  });

  let commonOptions = {
    method: 'post',
    headers: {
      'Content-Type': 'application/json',
    },
    contentType: 'application/json',
    data: JSON.stringify(data),
  };

  if (url === '/user/generic') {
    delete data.route_name;

    return merchantFetch({
      url: 'merchant/analytics',
      ...commonOptions,
    });
  }

  return ajax(url, {
    appendModeInURL: false,
    ...commonOptions,
  });
};
