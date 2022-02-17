import { merge } from 'common/utils/immutable';
import { transformToComponentFormat } from 'merchant/reducers/magicCheckout/magicSettings/utils';
import { ACTIONS } from 'merchant/reducers/magicCheckout/magicSettings/actions';

const FETCH_STATUS = {
  IDLE: 'idle',
  LOADING: 'loading',
  ERROR: 'error',
};

const DEFAULT_SELECTED_PLATFORM = 'woocommerce';

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
};

export default function magicSettingsReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.FETCH_MAGIC_SETTINGS_PENDING:
      return merge(state, { status: FETCH_STATUS.LOADING });
    case ACTIONS.FETCH_MAGIC_SETTINGS_SUCCESS:
      return merge(state, {
        status: FETCH_STATUS.IDLE,
        ...action.payload.data,
        platform: action.payload?.data?.platform || DEFAULT_SELECTED_PLATFORM,
        has_saved_config: !!action.payload.data?.platform,
        cod_slabs: transformToComponentFormat(action.payload.data?.cod_slabs),
      });
    case ACTIONS.FETCH_MAGIC_SETTINGS_ERROR:
      return merge(state, { status: FETCH_STATUS.ERROR, error: action.payload });
    case ACTIONS.UPDATE_MAGIC_SETTINGS_PENDING:
      return merge(state, { status: FETCH_STATUS.LOADING });
    case ACTIONS.UPDATE_MAGIC_SETTINGS_SUCCESS:
      return merge(state, {
        status: FETCH_STATUS.IDLE,
        ...action.data,
        has_saved_config: true,
        cod_slabs: action.data?.cod_slabs ? transformToComponentFormat(action.data.cod_slabs) : [],
      });
    case ACTIONS.UPDATE_MAGIC_SETTINGS_ERROR:
      return merge(state, { status: FETCH_STATUS.ERROR, error: action.payload });
    default:
      return state;
  }
}
