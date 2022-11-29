import { merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
const FETCH_SUPPORTED_PLUGINS = 'FETCH_SUPPORTED_PLUGINS';
const FETCH_MERCHANT_PLUGIN = 'FETCH_MERCHANT_PLUGIN';
const SAVE_MERCHANT_PLUGIN = 'SAVE_MERCHANT_PLUGIN';

export const fetchSupportedPlugins = () => {
  return {
    type: FETCH_SUPPORTED_PLUGINS,
    payload: merchantFetch({
      url: `onboarding/merchant/supported_plugins`,
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    }),
  };
};

export const fetchMerchantPlugin = ({ merchantId }) => {
  return {
    type: FETCH_MERCHANT_PLUGIN,
    payload: merchantFetch({
      url: `onboarding/merchants/${merchantId}/plugin`,
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    }),
  };
};

export const saveMerchantPlugin = ({ merchantId, data }) => {
  return {
    type: SAVE_MERCHANT_PLUGIN,
    payload: merchantFetch({
      url: `onboarding/merchants/${merchantId}/plugin`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      data,
    }),
  };
};

const initialState = {
  supported: {
    loading: true,
    items: {},
  },
  details: {
    loading: true,
    items: {},
  },
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${FETCH_SUPPORTED_PLUGINS}::SUCCESS`:
      return merge(state, {
        supported: {
          loading: false,
          items: (Array.isArray(action.payload.data) ? action.payload.data : []).reduce(
            (acc, curr) => ({ ...acc, [curr.name]: curr }),
            {},
          ),
        },
      });

    case `${FETCH_SUPPORTED_PLUGINS}::ERROR`:
      return merge(state, {
        supported: {
          loading: false,
          items: {},
        },
      });

    case `${FETCH_MERCHANT_PLUGIN}::SUCCESS`:
      return merge(state, {
        details: {
          loading: false,
          items: action.payload.data.reduce((acc, curr) => ({ ...acc, [curr.website]: curr }), {}),
        },
      });

    case `${FETCH_MERCHANT_PLUGIN}::ERROR`:
      return merge(state, {
        details: {
          loading: false,
          items: {},
        },
      });
    default:
      return state;
  }
};
