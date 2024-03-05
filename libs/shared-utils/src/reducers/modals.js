import { merge } from '../immutable';

const MODAL_OPEN = 'MODAL_OPEN';
const MODAL_CLOSE = 'MODAL_CLOSE';
const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';

export const openModal = (payload) => {
  return {
    type: MODAL_OPEN,
    payload,
  };
};

export const closeModal = (payload) => {
  document.body.classList.remove('ReactModal__Body--open');
  return {
    type: MODAL_CLOSE,
    payload,
  };
};

export const openSlider = (payload) => {
  if (payload.openURL) {
    location.hash = payload.openURL;
  }

  return {
    type: SLIDER_OPEN,
    payload: {
      ...payload,
      slider: true,
    },
  };
};

export const closeSlider = (payload) => {
  if (payload && payload.closeURL) {
    location.hash = payload.closeURL;
  }
  return {
    type: SLIDER_CLOSE,
  };
};

const initialState = {};

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
