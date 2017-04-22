import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';

const BATCH_UPLOADS_FETCH = 'BATCH_UPLOADS_FETCH';
const BATCH_UPLOAD = 'BATCH_UPLOAD';

export const fetchBatchUploads = params => {
  return dispatch => {
    return dispatch({
      type: BATCH_UPLOADS_FETCH,
      payload: ajax({
        url: '/batches',
      }),
    });
  };
};

export const uploadBatchRefunds = file => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', 'refund');

  return dispatch => {
    return dispatch({
      type: BATCH_UPLOAD,
      payload: ajax({
        url: '/batches',
        method: 'post',
        data: formData,
        processData: false,
        contentType: false,
      }),
    });
  };
};

let initialState = {
  loading: true,
  batchuploads: [],
  count: 0,
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${BATCH_UPLOADS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${BATCH_UPLOADS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        batchuploads: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      });

    case `${BATCH_UPLOADS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        batchuploads: initialState.batchuploads,
      });

    default:
      return state;
  }
}
