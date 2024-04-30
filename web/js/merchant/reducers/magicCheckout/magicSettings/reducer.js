import { merge } from 'common/utils/immutable';
import { transformToComponentFormat } from 'merchant/reducers/magicCheckout/magicSettings/utils';
import { ACTIONS } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { RCOD_APP_NAME, MAGIC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

const FETCH_STATUS = {
  IDLE: 'idle',
  LOADING: 'loading',
  ERROR: 'error',
};

export const NESTED_VIEW_TYPE = {
  PLATFORM_SELECTION: 'platform_selection',
  SETTINGS: 'settings',
};

const DEFAULT_SELECTED_PLATFORM = 'select';

const initialState = {
  status: FETCH_STATUS.IDLE,
  shipping_info: '',
  list_promotions: '',
  apply_promotion: '',
  cod_slabs: [],
  platform: DEFAULT_SELECTED_PLATFORM,
  shop_id: '',
  error: null,
  has_saved_config: false,
  cod_intelligence: false,
  nested_view_type: NESTED_VIEW_TYPE.PLATFORM_SELECTION,
  codSlabsSet: false,
  nestedTabsStatus: FETCH_STATUS.IDLE,
  showTabHeading: true,
  manualControlCodOrder: false,
  one_cc_coupon_engine: null,
  rcodEnabled: false,
  apps_installed: [],
  dashboard_view: MAGIC_APP_NAME,
  rcod: {
    enabled: false,
  },
};

export default function magicSettingsReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.FETCH_MAGIC_SETTINGS_PENDING:
      return merge(state, { status: FETCH_STATUS.LOADING });
    case ACTIONS.FETCH_MAGIC_SETTINGS_SUCCESS: {
      const dashboardView =
        (action.payload.data?.apps_installed || []).length === 1
          ? action.payload.data.apps_installed[0]
          : action.payload.data?.dashboard_view || MAGIC_APP_NAME;

      const rcodEnabled =
        (action.payload.data?.apps_installed || []).includes(RCOD_APP_NAME) &&
        ((action.payload.data?.apps_installed || []).length === 1 ||
          action.payload.data?.dashboard_view === RCOD_APP_NAME);

      return merge(state, {
        status: FETCH_STATUS.IDLE,
        ...action.payload.data,
        manualControlCodOrder: rcodEnabled ? false : action.payload.data.manual_control_cod_order,
        platform: action.payload?.data?.platform || DEFAULT_SELECTED_PLATFORM,
        has_saved_config: !!action.payload.data?.platform,
        cod_slabs: transformToComponentFormat(action.payload.data?.cod_slabs),
        cod_engine: action.payload.data.cod_engine,
        rcodEnabled,
        apps_installed: action.payload.data?.apps_installed || [],
        dashboard_view: dashboardView,
      });
    }
    case ACTIONS.FETCH_MAGIC_SETTINGS_ERROR:
      return merge(state, { status: FETCH_STATUS.ERROR, error: action.payload });
    case ACTIONS.UPDATE_MAGIC_SETTINGS_PENDING: {
      let status = state.status;
      let nestedTabsStatus;
      if (action.data.showLoader) {
        status = FETCH_STATUS.LOADING;
      } else {
        nestedTabsStatus = FETCH_STATUS.LOADING;
      }
      return merge(state, { status, nestedTabsStatus });
    }
    case ACTIONS.UPDATE_MAGIC_SETTINGS_SUCCESS:
      return merge(state, {
        status: FETCH_STATUS.IDLE,
        ...action.data,
        manualControlCodOrder: action.data.manual_control_cod_order,
        has_saved_config: true,
        cod_slabs: action.data?.cod_slabs ? transformToComponentFormat(action.data.cod_slabs) : [],
        nested_view_type: NESTED_VIEW_TYPE.SETTINGS,
        nestedTabsStatus: FETCH_STATUS.IDLE,
        rcodEnabled: action.data?.dashboard_view
          ? action.data?.dashboard_view === RCOD_APP_NAME
          : state.rcodEnabled,
      });
    case ACTIONS.UPDATE_MAGIC_SETTINGS_ERROR:
      return merge(state, {
        status: FETCH_STATUS.ERROR,
        nestedTabsStatus: FETCH_STATUS.ERROR,
        error: action.payload,
      });
    case ACTIONS.DISABLE_MAGIC_CHECKOUT_PENDING: {
      const status = action.data.showLoader ? FETCH_STATUS.LOADING : state.status;
      return merge(state, { status });
    }
    case ACTIONS.DISABLE_MAGIC_CHECKOUT_SUCCESS:
      return merge(state, { status: FETCH_STATUS.IDLE, one_click_checkout: false });
    case ACTIONS.DISABLE_MAGIC_CHECKOUT_ERROR:
      return merge(state, { status: FETCH_STATUS.IDLE });
    case ACTIONS.UPDATE_PAGE_VIEW:
      return merge(state, { nested_view_type: action.payload.view });
    case ACTIONS.UPDATE_DOMAIN_DETAIL:
      return merge(state, { domain: action.payload.domain });
    case ACTIONS.UPDATE_SOPC_METAFIELDS:
      return merge(state, { sopc_metafields: action.metafields });
    default:
      return state;
  }
}
