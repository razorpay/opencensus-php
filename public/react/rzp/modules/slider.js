import { merge } from 'rzp/utils/immutable';

const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';

export const openSlider = payload => {
  return dispatch => {
    if (payload.openURL) {
      location.hash = payload.openURL;
    }

    return dispatch({
      type: SLIDER_OPEN,
      payload: {
        ...payload,
        isOpen: true,
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
      payload: {
        ...payload,
        isOpen: false,
      },
    });
  };
};

let initialState = {};

export default (state = initialState, action) => {
  switch (action.type) {
    case SLIDER_OPEN:
      return merge(state, action.payload);

    case SLIDER_CLOSE:
      return merge(state, action.payload);

    default:
      return state;
  }
};
