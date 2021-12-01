import { merge } from 'common/utils/immutable';

const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';
const SLIDER_EXPAND = 'SLIDER_EXPAND';
const SLIDER_COMPACT = 'SLIDER_COMPACT';

export const expandSlider = (payload) => {
  return {
    type: SLIDER_EXPAND,
    payload: {
      expanded: true,
      ...payload,
    },
  };
};

export const compactSlider = (payload) => {
  return {
    type: SLIDER_COMPACT,
    payload: {
      expanded: false,
      ...payload,
    },
  };
};

export const openSlider = (payload) => {
  return {
    type: SLIDER_OPEN,
    payload: {
      isOpen: true,
      expanded: false,
      ...payload,
    },
  };
};

export const closeSlider = (payload = {}) => {
  return {
    type: SLIDER_CLOSE,
    payload: {
      isOpen: false,
      ...payload,
    },
  };
};

const initialState = {
  isOpen: false,
  onOpenURL: null,
  onCloseURL: null,
  expanded: false,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case SLIDER_OPEN:
    case SLIDER_EXPAND:
    case SLIDER_COMPACT:
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
