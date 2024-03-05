// This utility is used to open and close modal dialogs
// This is directly updating zustand store for modal actions

import { useStore } from 'shell/commonStore';
import { getOpenModalState, getCloseModalState } from 'merchant/commonStore/stateActions/modals';

const MODAL_OPEN = 'MODAL_OPEN';
const MODAL_CLOSE = 'MODAL_CLOSE';
const SLIDER_OPEN = 'SLIDER_OPEN';
const SLIDER_CLOSE = 'SLIDER_CLOSE';

const ZUSTAND_STORE_KEY = 'modal';

// Keeping the action response as it as to support
// existing redux flow in order to prevent any breaking changes

export const openModal = (payload) => {
  useStore.setState((state) => {
    return {
      [ZUSTAND_STORE_KEY]: getOpenModalState(state[ZUSTAND_STORE_KEY], payload),
    };
  });
  return {
    type: MODAL_OPEN,
    payload,
  };
};

export const closeModal = (payload) => {
  useStore.setState(() => {
    return {
      [ZUSTAND_STORE_KEY]: getCloseModalState(),
    };
  });
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

// deprecated not in use anymore
// TODO: remove this in future, keeping this for reference for now
// export default (state = initialState, action) => {
//   switch (action.type) {
//     case MODAL_OPEN:
//       return handleOpenModal(state, action);

//     case MODAL_CLOSE:
//       return initialState;

//     default:
//       return state;
//   }
// };
