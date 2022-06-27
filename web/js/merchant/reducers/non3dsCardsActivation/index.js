import { set, merge } from 'common/utils/immutable';

import {
  EVENTS,
  NON_3DS_CARD_WORKFLOW_STATUS,
  NON_3DS_CARDS_ACTIVATION_FETCH,
  NON_3DS_CARDS_ACTIVATION_ENABLE,
  NON_3DS_CARDS_ACTIVATION_STATUS,
  NON_3DS_CARDS_ACTIVATION_DISABLE,
  NON_3DS_CARDS_ACTIVATION_REMOVE_ERROR,
  NON_3DS_CARDS_ACTIVATION_STATUS_MAPPING,
} from './constants';

// initial state
const initialState = {
  isLoading: false,
  isEnabling: false,
  isFetched: false, // used to check if data is already fetched can cached in reducer
  canRequestForEnable: false,
  showDisabledBanner: false, // used on home page to show banner for disabled non-3ds cards
  workflowStatus: '',
  activationStatus: '',
  rejectionReason: '',
  updatedAt: '',
  success: null,
  error: null,
};

/**
 * Non3dsCardsActivation reducer
 * @param {initialState} state initialState for enable non 3ds cards transactions
 * @param {{ type: string, payload: * }} action to update the state
 * @returns {initialState} updated state of reducer
 */
export default function non3dsCardsActivationReducer(state = initialState, action) {
  switch (action.type) {
    case `${NON_3DS_CARDS_ACTIVATION_FETCH}::PENDING`: {
      return set(state, 'isLoading', true);
    }
    case `${NON_3DS_CARDS_ACTIVATION_ENABLE}::PENDING`: {
      return set(state, 'isEnabling', true);
    }
    case `${NON_3DS_CARDS_ACTIVATION_DISABLE}::PENDING`: {
      return set(state, 'isEnabling', true);
    }
    case `${NON_3DS_CARDS_ACTIVATION_FETCH}::SUCCESS`: {
      /**
       * if allow_only_3ds:
       *  canRequestForEnable = true
       *  activationStatus = disabled
       * else if workflow_exists:
       *  activationStatus = workflow_status
       * else:
       *  activationStatus = enabled
       *  workflowStatus = approved
       */
      const { data } = action.payload || {};
      if (data) {
        let activationStatus = state.activationStatus;

        const workflowStatus = data.workflow_status;

        activationStatus = data.allow_only_3ds
          ? NON_3DS_CARDS_ACTIVATION_STATUS.DISABLED
          : NON_3DS_CARDS_ACTIVATION_STATUS.ENABLED;

        if (
          data.workflow_exists &&
          NON_3DS_CARDS_ACTIVATION_STATUS_MAPPING[workflowStatus] ===
            NON_3DS_CARDS_ACTIVATION_STATUS.REQUESTED
        ) {
          activationStatus = NON_3DS_CARDS_ACTIVATION_STATUS.REQUESTED;
        }

        return {
          isLoading: false,
          isEnabling: false,
          isFetched: true,
          workflowStatus,
          activationStatus,
          error: state.error,
          updatedAt: data.updated_at,
          rejectionReason: data.rejection_reason_message,
          showDisabledBanner: NON_3DS_CARD_WORKFLOW_STATUS.REJECTED === workflowStatus, // used on home page to show banner for disabled non-3ds cards
          canRequestForEnable: NON_3DS_CARDS_ACTIVATION_STATUS.DISABLED === activationStatus,
        };
      }
      return state;
    }
    case `${NON_3DS_CARDS_ACTIVATION_ENABLE}::SUCCESS`: {
      return set(state, 'isEnabling', false);
    }
    case `${NON_3DS_CARDS_ACTIVATION_DISABLE}::SUCCESS`: {
      return merge(state, {
        isEnabling: false,
        success: 'Support for Non 3D Secure transactions disabled successfully',
      });
    }
    case `${NON_3DS_CARDS_ACTIVATION_FETCH}::ERROR`: {
      return merge(state, {
        isLoading: false,
        isEnabling: false,
        error: action.payload,
      });
    }
    case `${NON_3DS_CARDS_ACTIVATION_ENABLE}::ERROR`: {
      const { errors } = action.payload || {};
      const error = Array.isArray(errors) ? errors[0] : null;
      return merge(state, {
        isEnabling: false,
        error,
      });
    }
    case `${NON_3DS_CARDS_ACTIVATION_DISABLE}::ERROR`: {
      const { errors } = action.payload || {};
      const error = Array.isArray(errors) ? errors[0] : null;
      return merge(state, {
        isEnabling: false,
        error,
      });
    }
    case NON_3DS_CARDS_ACTIVATION_REMOVE_ERROR: {
      return merge(state, {
        error: null,
        success: null,
      });
    }
    default: {
      return state;
    }
  }
}

export { EVENTS, NON_3DS_CARDS_ACTIVATION_STATUS, NON_3DS_CARD_WORKFLOW_STATUS };
