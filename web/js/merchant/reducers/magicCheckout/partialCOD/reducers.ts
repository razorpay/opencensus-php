import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/partialCOD/actions';
import { State } from 'merchant/views/MagicCheckout/PartialCOD/types';

const initState: State = {
  isLoading: false,
  error: null,
  partialCODConfigs: {},
  isPartialCODEnabled: false,
};

export const magicPartialCODReducer = (state = initState, action): State => {
  switch (action.type) {
    case ACTIONS.FETCH_PARTIAL_COD_PENDING:
      return merge(state, { isLoading: true, error: null });
    case ACTIONS.FETCH_PARTIAL_COD_SUCCESS: {
      const { configs, enabled } = action.payload?.data || {};
      return merge(state, {
        isLoading: false,
        partialCODConfigs: configs || {},
        isPartialCODEnabled: enabled,
        error: null,
      });
    }

    case ACTIONS.FETCH_PARTIAL_COD_ERROR: {
      const errorMessage =
        (action?.payload?.errors && action.payload.errors[0]) ||
        'Something went wrong. Please try again';
      return merge(state, {
        isLoading: false,
        error: errorMessage,
        partialCODConfigs: {},
        isPartialCODEnabled: false,
      });
    }

    case ACTIONS.UPDATE_CONFIGS_PENDING:
    case ACTIONS.UPDATE_CONFIGS_ERROR:
      return state;

    case ACTIONS.UPDATE_CONFIGS_SUCCESS: {
      const { configs, enabled } = action.data?.one_cc_partial_payments_cod || {};
      return merge(state, {
        partialCODConfigs: configs || state.partialCODConfigs,
        isPartialCODEnabled: enabled,
        error: null,
      });
    }

    case ACTIONS.RESET_PARTIAL_COD:
      return merge(state, { ...initState });

    default:
      return state;
  }
};
