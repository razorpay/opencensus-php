import { set, merge } from 'common/utils/immutable';
import Affordability from 'merchant/models/Affordability';

const AFFORDABILITY_WIDGET_FETCH = 'AFFORDABILITY_WIDGET_FETCH';
const UPDATE_ENABLE = 'UPDATE_ENABLE';

export const fetchAffordabilityWidget = () => {
  const affordability = new Affordability();
  return {
    type: AFFORDABILITY_WIDGET_FETCH,
    payload: affordability.fetch(''),
  };
};

export const updateWidgetStatus = (status, lastAction) => {
  return {
    type: UPDATE_ENABLE,
    payload: {
      enabled: status,
      lastAction,
    },
  };
};

const initialState = {
  loading: true,
  affordability: {},
  error: null,
};

const affordabilitySelfServeReducer = (state = initialState, action) => {
  switch (action.type) {
    case `${AFFORDABILITY_WIDGET_FETCH}::PENDING`: {
      return set(state, 'loading', true);
    }

    case `${AFFORDABILITY_WIDGET_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        affordability: action.payload,
        error: null,
      });
    }

    case `${AFFORDABILITY_WIDGET_FETCH}::ERROR`: {
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        affordability: initialState.affordability,
      });
    }

    case UPDATE_ENABLE: {
      return merge(state, {
        affordability: {
          ...state.affordability,
          ...action.payload,
        },
      });
    }

    default:
      return state;
  }
};

export default affordabilitySelfServeReducer;
