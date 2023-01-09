import { merge } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/rtoHistoryUpload/action';

export const initialState = {
  fileId: null,
  loading: false,
  items: [],
  error: null,
  isUploadAllowed: true,
};

export const rtoHistoryUploadReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_RTO_HISTORY_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.FETCH_RTO_HISTORY_SUCCESS:
      return merge(state, {
        loading: false,
        items: action.payload.data.merchant_file_upload_audits || action.payload.data,
        isUploadAllowed: action.payload?.data?.create_allowed,
      });
    case ACTIONS.FETCH_RTO_HISTORY_ERROR:
      return merge(state, { error: action.payload.error, loading: false });
    case ACTIONS.VALIDATE_RTO_HISTORY_SUCCESS:
      return merge(state, { fileId: action.payload.data.file_id });
    case ACTIONS.RESET_FILE_ID:
      return merge(state, { fileId: action.payload });
    default:
      return state;
  }
};
