import { merge } from 'rzp/utils/immutable';

const MODAL_OPEN = 'MODAL_OPEN';
const MODAL_CLOSE = 'MODAL_CLOSE';

export const openModal = payload => {
  return dispatch => {
    return dispatch({
      type: MODAL_OPEN,
      payload,
    });
  };
};

export const closeModal = payload => {
  return dispatch => {
    return dispatch({
      type: MODAL_CLOSE,
      payload,
    });
  };
};

let initialState = {};

export default (state = initialState, action) => {
  switch (action.type) {
    case MODAL_OPEN:
      return merge(state, action.payload);

    case MODAL_CLOSE:
      return initialState;

    default:
      return state;
  }
};
