import { merge, set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { RCOD_APP_NAME, MAGIC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

export const REFRESH_MAGIC_CHECKOUT_STATUS = 'REFRESH_MAGIC_CHECKOUT_STATUS';
const FETCH_INTELLIGENCE_CONFIG = 'FETCH_INTELLIGENCE_CONFIG';
const RESET_INTELLIGENCE_CONFIG = 'RESET_INTELLIGENCE_CONFIG';
const UPDATE_APP_VIEW = 'TOGGLE_APP_VIEW';

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

export const fetchIntelligenceConfig = () => {
  return {
    type: FETCH_INTELLIGENCE_CONFIG,
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

export const updateAppView = (view = '') => {
  return {
    type: UPDATE_APP_VIEW,
    view,
  };
};

export const resetIntelligenceConfig = () => ({
  type: RESET_INTELLIGENCE_CONFIG,
});

const initialState = {
  loading: true,
  status: 'available',
  error: null,
  cod_intelligence: null,
  cod_order_control: null,
  one_cc_prepay_cod_conversion: null,
  one_cc_coupon_engine: null,
  rcod: false,
  dashboard_view: MAGIC_APP_NAME,
  apps_installed: [],
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
    case `${FETCH_INTELLIGENCE_CONFIG}::SUCCESS`:
      return merge(state, {
        loading: false,
        cod_intelligence: action.payload?.data?.cod_intelligence,
        cod_order_control: action.payload?.data?.manual_control_cod_order,
        one_cc_prepay_cod_conversion: action.payload?.data?.one_cc_prepay_cod_conversion,
        platform: action.payload?.data?.platform,
        one_cc_coupon_engine: action.payload?.data?.one_cc_coupon_engine,
        dashboard_view:
          (action.payload.data?.apps_installed || []).length === 1
            ? action.payload.data.apps_installed[0]
            : action.payload.data?.dashboard_view || MAGIC_APP_NAME,
        rcod:
          (action.payload?.data?.apps_installed || []).includes(RCOD_APP_NAME) &&
          ((action.payload?.data?.apps_installed || []).length === 1 ||
            action.payload?.data?.dashboard_view === RCOD_APP_NAME),
        apps_installed: action.payload?.data?.apps_installed || [],
      });
    case `${FETCH_INTELLIGENCE_CONFIG}::PENDING`:
      return set(state, 'loading', true);
    case `${FETCH_INTELLIGENCE_CONFIG}::ERROR`:
      return set(state, 'loading', false);
    case RESET_INTELLIGENCE_CONFIG:
      return merge(state, { cod_intelligence: null, cod_order_control: null });
    case UPDATE_APP_VIEW:
      return merge(state, {
        dashboard_view: action.view,
        rcod: state.apps_installed.includes(RCOD_APP_NAME) && action.view === RCOD_APP_NAME,
      });
    default:
      return state;
  }
}
