import { merge } from 'rzp/utils/immutable';

const MODAL_OPEN = 'MODAL_OPEN';
const MODAL_CLOSE = 'MODAL_CLOSE';
const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';

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

export const openSlider = payload => {
  return dispatch => {
    if (payload.openURL) {
      location.hash = payload.openURL;
    }

    return dispatch({
      type: SLIDER_OPEN,
      payload: {
        ...payload,
        slider: true,
      },
    });
  };
};

export const closeSlider = payload => {
  return dispatch => {
    if (payload && payload.closeURL) {
      location.hash = payload.closeURL;
    }

    return dispatch({
      type: SLIDER_CLOSE,
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
