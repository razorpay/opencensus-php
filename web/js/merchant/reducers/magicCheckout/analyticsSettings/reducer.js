import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import {
  addAccount,
  updateEventConfigs,
} from 'merchant/reducers/magicCheckout/analyticsSettings/utils';

const initialState = {
  isLoading: {
    authConfigs: false,
    saveEvents: false,
    createAccount: false,
    fetchOauth: false,
  },
  hasError: null,
  merchantAnalyticsConfigs: {},
};

export const magicAnalyticsSettingsReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_CONFIGS_PENDING:
      return merge(state, {
        isLoading: { ...state.isLoading, authConfigs: true },
        hasError: null,
      });
    case ACTIONS.FETCH_CONFIGS_SUCCESS:
      return merge(state, {
        isLoading: { ...state.isLoading, authConfigs: false },
        merchantAnalyticsConfigs: action?.payload?.data,
        hasError: null,
      });
    case ACTIONS.FETCH_CONFIGS_ERROR:
      return merge(state, {
        isLoading: { ...state.isLoading, authConfigs: false },
        hasError: action?.payload?.errors?.[0] || null,
      });
    case ACTIONS.DELETE_CONFIGS_PENDING:
    case ACTIONS.DELETE_CONFIGS_SUCCESS:
      return merge(state, { hasError: null });
    case ACTIONS.DELETE_CONFIGS_ERROR:
      return merge(state, { hasError: action?.payload?.errors[0] });
    case ACTIONS.UPDATE_EVENT_CONFIGS_PENDING:
      return merge(state, {
        isLoading: { ...state.isLoading, saveEvents: true },
        hasError: null,
      });
    case ACTIONS.UPDATE_EVENT_CONFIGS_SUCCESS: {
      const updatedConfigs = updateEventConfigs(action, state);
      return merge(state, {
        isLoading: { ...state.isLoading, saveEvents: false },
        merchantAnalyticsConfigs: updatedConfigs,
        hasError: null,
      });
    }
    case ACTIONS.UPDATE_EVENT_CONFIGS_ERROR:
      return merge(state, {
        isLoading: { ...state.isLoading, saveEvents: false },
        hasError: null,
      });
    case ACTIONS.ADD_ACCOUNT_PENDING:
      return merge(state, {
        isLoading: { ...state.isLoading, createAccount: true },
        hasError: null,
      });
    case ACTIONS.ADD_ACCOUNT_SUCCESS: {
      const updatedAccountConfigs = addAccount(action, state);
      return merge(state, {
        isLoading: { ...state.isLoading, createAccount: false },
        merchantAnalyticsConfigs: updatedAccountConfigs,
        hasError: null,
      });
    }
    case ACTIONS.ADD_ACCOUNT_ERROR:
      return merge(state, {
        isLoading: { ...state.isLoading, createAccount: false },
        hasError: action?.payload?.errors?.[0] || null,
      });
    case ACTIONS.OAUTH_API_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, fetchOauth: true } });
    case ACTIONS.OAUTH_API_SUCCESS:
    case ACTIONS.OAUTH_API_ERROR:
      return merge(state, { isLoading: { ...state.isLoading, fetchOauth: false } });
    case ACTIONS.RESET_ANALYTICS_CONFIGS:
      return merge(state, { ...initialState });
    default:
      return state;
  }
};
