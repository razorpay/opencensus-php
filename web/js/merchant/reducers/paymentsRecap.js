import { merge } from 'common/utils/immutable';

//reducer constant
const PAYMENTS_RECAP_MODAL = 'PAYMENTS_RECAP_MODAL';
const PAYMENTS_RECAP_BANNER = 'PAYMENTS_RECAP_BANNER';

// initial state
const initialState = {
  isOpen: false,
  showBanner: false,
};

export const togglePaymentsRecapModal = (flag) => {
  return {
    type: PAYMENTS_RECAP_MODAL,
    payload: { isOpen: flag },
  };
};

export const togglePaymentsRecapBannerVisibility = (flag) => {
  return {
    type: PAYMENTS_RECAP_BANNER,
    payload: { showBanner: flag },
  };
};

//reducers
export default function paymentsRecapReducer(state = initialState, action) {
  switch (action.type) {
    case PAYMENTS_RECAP_MODAL: {
      return merge(state, {
        isOpen: action.payload.isOpen,
      });
    }
    case PAYMENTS_RECAP_BANNER: {
      return merge(state, {
        showBanner: action.payload.showBanner,
      });
    }
    default:
      return state;
  }
}
