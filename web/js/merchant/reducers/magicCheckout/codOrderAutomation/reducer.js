import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/codOrderAutomation/action';

const initialState = {
  isLoading: false,
  ruleConfigs: null,
  isPending: false,
  error: null,
};

export const magicCODOrdersAutomationReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_CONFIGS_PENDING:
      return merge(state, { isLoading: true, error: null });
    case ACTIONS.FETCH_CONFIGS_SUCCESS:
      return merge(state, {
        isLoading: false,
        ruleConfigs: action?.payload?.data?.rule_config?.[0],
        error: null,
      });
    case ACTIONS.FETCH_CONFIGS_ERROR:
      return merge(state, {
        isLoading: false,
        error: action?.payload?.errors[0],
        ruleConfigs: null,
      });
    case ACTIONS.UPDATE_CONFIGS_PENDING:
      return merge(state, { isPending: true, error: null });
    case ACTIONS.UPDATE_CONFIGS_SUCCESS:
      return merge(state, {
        isPending: false,
        ruleConfigs: action?.payload?.data?.rule_config?.[0],
        error: null,
      });
    case ACTIONS.UPDATE_CONFIGS_ERROR:
      return merge(state, { isPending: false, error: action?.payload?.errors[0] });
    default:
      return state;
  }
};
