import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

//reducer constant
const FETCH_GENERIC_FEATURE_STATUS = 'FETCH_GENERIC_FEATURE_STATUS';

// initial state
const initialState = {
  loading: false,
  error: null,
  features: {},
};

export const fetchGenericFeatureStatus = (id, feature) => {
  return {
    type: FETCH_GENERIC_FEATURE_STATUS,
    resource: {
      feature,
    },
    payload: merchantFetch(`feature/merchant/${id}/${feature}`),
  };
};

//reducers
export default (state = initialState, action) => {
  switch (action.type) {
    case `${FETCH_GENERIC_FEATURE_STATUS}::PENDING`: {
      return set(state, 'loading', true);
    }
    case `${FETCH_GENERIC_FEATURE_STATUS}::SUCCESS`: {
      const {
        resource: { feature },
        payload: {
          data: { status },
        },
      } = action;
      return merge(state, {
        loading: false,
        features: {
          ...state.features,
          [feature]: status,
        },
        error: null,
      });
    }
    case `${FETCH_GENERIC_FEATURE_STATUS}::ERROR`:
      return merge(state, {
        loading: false,
        features: {
          ...state.features,
        },
        error: action.payload.errors,
      });
    default:
      return state;
  }
};
