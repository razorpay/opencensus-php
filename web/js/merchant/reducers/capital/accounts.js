import { merge } from 'common/utils/immutable';
import { merchantFetch } from '../../utils/ajax';

const FETCH_ACCOUNT_PRODUCT_CONFIG = 'FETCH_ACCOUNT_PRODUCT_CONFIG';

const getInitialState = () => {
  return {
    productConfig: {
      loading: false,
      data: null,
      error: null,
    },
  };
};

const initialState = getInitialState();

export default function accountFunction(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_ACCOUNT_PRODUCT_CONFIG}::PENDING`:
      return merge(state, {
        productConfig: {
          loading: true,
        },
      });

    case `${FETCH_ACCOUNT_PRODUCT_CONFIG}::SUCCESS`:
      return merge(state, {
        productConfig: {
          loading: false,
          data: action.payload.data.account_product_config,
        },
      });

    case `${FETCH_ACCOUNT_PRODUCT_CONFIG}::ERROR`:
      return merge(state, {
        productConfig: {
          loading: false,
          error: action.payload.errors,
        },
      });

    default:
      return state;
  }
}

export const request = (url, data, progressTracker, { method = 'post', mode = 'live' } = {}) => {
  return merchantFetch({
    url,
    mode,
    method,
    data,
    onUploadProgress: progressTracker,
  });
};

export const resourceUrlPrefix = (domain, entity, endpoint) => {
  return `loc/service/twirp/rzp.capital.loc.${domain}.v1.${entity}/${endpoint}`;
};

export const fetchAccountProductConfigData = (data) => {
  return request(`${resourceUrlPrefix('account', 'AccountAPI', 'GetAccountProductConfig')}`, data);
};

export const fetchAccountProductConfig = (data) => {
  return {
    type: FETCH_ACCOUNT_PRODUCT_CONFIG,
    payload: fetchAccountProductConfigData(data),
  };
};
