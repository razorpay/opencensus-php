import { merge, set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

export const REFRESH_MAGIC_CHECKOUT_STATUS = 'REFRESH_MAGIC_CHECKOUT_STATUS';
const FETCH_COD_INTELLIGENCE_CONFIG = 'FETCH_COD_INTELLIGENCE_CONFIG';
const RESET_COD_INTELLIGENCE_CONFIG = 'RESET_COD_INTELLIGENCE_CONFIG';

export const fetchMagicCheckoutStatus = (params) => {
  const url = 'merchant/checkout_details';

  const payload = merchantFetch({
    url,
    params,
  });

  return {
    type: REFRESH_MAGIC_CHECKOUT_STATUS,
    payload,
  };
};

export const fetchCODIntelligenceConfig = () => {
  return {
    type: FETCH_COD_INTELLIGENCE_CONFIG,
    payload: merchantFetch({
      url: '1cc/merchant/configs',
    }),
  };
};

export const updateMagicCheckoutStatus = (data, params) => {
  const url = 'merchant/checkout_details';

  const payload = merchantFetch({
    url,
    params,
    data: {
      status_1cc: data.status,
      merchant_id: data.merchant_id,
    },
    method: 'post',
  });

  return {
    type: REFRESH_MAGIC_CHECKOUT_STATUS,
    payload,
  };
};

export const resetCODIntelligenceConfig = () => ({
  type: RESET_COD_INTELLIGENCE_CONFIG,
});

const initialState = {
  loading: true,
  status: 'available',
  error: null,
  cod_intelligence: null,
};

export default function magicCheckoutReducer(state = initialState, action) {
  switch (action.type) {
    case `${REFRESH_MAGIC_CHECKOUT_STATUS}::SUCCESS`:
      return merge(state, {
        loading: false,
        status: action.payload?.data?.status_1cc || 'available',
        error: null,
      });
    case `${REFRESH_MAGIC_CHECKOUT_STATUS}::PENDING`:
      return set(state, 'loading', true);
    case `${FETCH_COD_INTELLIGENCE_CONFIG}::SUCCESS`:
      return merge(state, {
        loading: false,
        cod_intelligence: action.payload?.data?.cod_intelligence,
      });
    case `${FETCH_COD_INTELLIGENCE_CONFIG}::PENDING`:
      return set(state, 'loading', true);
    case `${FETCH_COD_INTELLIGENCE_CONFIG}::ERROR`:
      return set(state, 'loading', false);
    case RESET_COD_INTELLIGENCE_CONFIG:
      return set(state, 'cod_intelligence', null);
    default:
      return state;
  }
}
