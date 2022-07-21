import { set, merge } from 'common/utils/immutable';
import { FETCHING_APM_FORM, SET_APM_FORM_DATA, SET_APM_FORM_ERROR } from './constants';

function apmFormDataReducer(state = { isLoading: false, data: {}, error: null }, action) {
  switch (action.type) {
    case FETCHING_APM_FORM: {
      return set(state, 'isLoading', true);
    }
    case SET_APM_FORM_ERROR: {
      return merge(state, {
        isLoading: false,
        error: action.payload,
      });
    }
    case SET_APM_FORM_DATA: {
      return merge(state, {
        isLoading: false,
        data: action.payload,
      });
    }
    default:
      return state;
  }
}

export default apmFormDataReducer;
