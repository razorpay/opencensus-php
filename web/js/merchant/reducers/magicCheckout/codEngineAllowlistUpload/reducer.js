import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/codEngineAllowlistUpload/actions';

export const initialState = {
  isLoading: false,
  items: [],
  total_records: 0,
  failed_records: 0,
  error: null,
};

export const codEngineAllowlistUploadReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_LIST_PENDING:
      return merge(state, { isLoading: true });
    case ACTIONS.FETCH_LIST_SUCCESS:
      return {
        isLoading: false,
        items: action.payload?.data?.locations || [],
        total_records: action.payload?.data?.count,
        failed_records: action.payload?.data?.failed,
        error: false,
      };
    case ACTIONS.FETCH_LIST_ERROR:
      return merge(state, { error: action.payload.errors, isLoading: false });
    case ACTIONS.VALIDATE_LIST_SUCCESS:
      return merge(state, {
        total_records: action.payload?.data?.count || 0,
        failed_records: action.payload?.data?.failed || 0,
      });
    default:
      return state;
  }
};
