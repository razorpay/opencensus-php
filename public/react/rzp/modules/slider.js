import { merge } from 'rzp/utils/immutable';

const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';

export const openSlider = payload => {
  return dispatch => {
    return dispatch({
      type: SLIDER_OPEN,
      payload: {
        isOpen: true,
        ...payload,
      },
    });
  };
};

export const closeSlider = (payload = {}) => {
  return dispatch => {
    return dispatch({
      type: SLIDER_CLOSE,
      payload: {
        isOpen: false,
        ...payload,
      },
    });
  };
};

let initialState = {
  isOpen: false,
  onOpenURL: null,
  onCloseURL: null,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case SLIDER_OPEN:
      return merge(state, action.payload);

    case SLIDER_CLOSE:
      return merge(state, {
        ...action.payload,
        ...initialState,
      });

    default:
      return state;
  }
};
