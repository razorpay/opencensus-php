import ajax from 'merchant/utils/ajax';

export var pokeConfig = {
  merchantId: ''
}

export const fetch = (query, mode) => {
  /*
   * given query according to
   * PQL - https://docs.google.com/document/d/1sa8Us-sDYkTFYcWUKjT02-qvL-j9GEejiZyAs0MiAhs/edit , makes ajax call
   */

  Object.keys(query.aggregations).forEach((aggKey) => {
  
    const aggDetails = query.aggregations[aggKey]
                            .details;

    aggDetails.mode = aggDetails.mode || mode;
  });

  let data = {
    route_name: 'merchant_analytics',
    body: query,
  }

  if (pokeConfig.merchantId) {
    query.merchant_id = pokeConfig.merchantId;
  }

  return ajax('/user/generic', {
    appendModeInURL: false,
    method: 'post',
    headers: {
      'Content-Type': "application/json"
    },
    contentType: 'application/json',
    data: JSON.stringify(data),
  });
};
