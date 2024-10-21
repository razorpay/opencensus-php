import { INITIAL_STATE, ACTIONS } from './constants';

export const checkoutFeatureReducer = (
  state = INITIAL_STATE,
  action: { type: keyof typeof ACTIONS; payload: any },
) => {
  switch (action.type) {
    case ACTIONS.SET_CONFIG:
      return {
        ...state,
        config: {
          ...state.config,
          ...action.payload,
        },
      };
    case ACTIONS.SET_VALUES:
      return {
        ...state,
        values: {
          ...state.values,
          ...action.payload,
        },
      };
    case ACTIONS.SET_IS_SAVING:
      return {
        ...state,
        isSaving: action.payload,
      };

    case ACTIONS.SET_IS_SAVING_TITLE_MODAL_CHANGE:
      return {
        ...state,
        isSavingTitleModalChange: action.payload,
      };
    case ACTIONS.SET_IS_LOADING:
      return {
        ...state,
        isLoading: action.payload,
      };
    case ACTIONS.SET_LAST_SAVED:
      return {
        ...state,
        lastSaved: action.payload,
      };
    case ACTIONS.SET_VALUE_MODIFIED:
      return {
        ...state,
        isValueModified: action.payload,
      };
    default: {
      return state;
    }
  }
};
