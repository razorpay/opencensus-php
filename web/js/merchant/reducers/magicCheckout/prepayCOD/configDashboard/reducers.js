import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/prepayCOD/configDashboard/action';

const initState = {
  isLoading: false,
  error: null,
  prepayCODConfigs: {},
  isPrepayCODEnabled: false,
};

export const magicPrepayCODConfigsReducer = (state = initState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_CONFIGS_PENDING:
      return merge(state, { isLoading: true, error: null });
    case ACTIONS.FETCH_CONFIGS_SUCCESS:
      return merge(state, {
        isLoading: false,
        prepayCODConfigs: action.payload?.data?.configs || {},
        isPrepayCODEnabled: action.payload?.data?.enabled,
        error: null,
      });
    case ACTIONS.FETCH_CONFIGS_ERROR:
      return merge(state, {
        isLoading: false,
        error: action?.payload?.errors[0],
        prepayCODConfigs: {},
        isPrepayCODEnabled: false,
      });
    case ACTIONS.UPDATE_CONFIGS_PENDING:
    case ACTIONS.UPDATE_CONFIGS_ERROR:
      return state;
    case ACTIONS.UPDATE_CONFIGS_SUCCESS:
      return merge(state, {
        prepayCODConfigs: action?.data?.one_cc_prepay_cod_conversion?.configs || {},
        isPrepayCODEnabled: action?.data?.one_cc_prepay_cod_conversion?.enabled || true,
        error: null,
      });
    case ACTIONS.RESET_CONFIGS:
      return merge(state, { ...initState });
    default:
      return state;
  }
};
