import { set } from 'common/utils/immutable';
import getMobileDetect from 'common/utils/mobileDetect';
import { merchantFetch } from 'merchant/utils/ajax';

const ROW_LUMINATE_ADD = 'ROW_LUMINATE_ADD';
const ROW_LUMINATE_REMOVE = 'ROW_LUMINATE_REMOVE';
const LOCATION_UPDATE = 'LOCATION_UPDATE';
const ENTITY_UPDATE = 'ENTITY_UPDATE';
const SEC_ENTITY_UPDATE = 'SEC_ENTITY_UPDATE';
const SET_ACTIVE_PAGE_NAME = 'SET_ACTIVE_PAGE_NAME';
const TOGGLE_MOBILE_MENU = 'TOGGLE_MOBILE_MENU';
const RESIZE_WINDOW = 'RESIZE_WINDOW';

const isMobileResolution = (width) => {
  return width <= 768;
};

export const updateMerchantLiveTransactionFlag = (id) => {
  return merchantFetch({
    url: 'merchant_mtu_update_dashboard',
    method: 'post',
    data: {
      merchants: [id],
      live_transaction_done: 2,
    },
  });
};

const initialState = {
  luminateRowId: null,
  windowWidth: window.innerWidth,
  windowHeight: window.outerHeight,
  isMobileResolution: isMobileResolution(window.innerWidth),
  isWebView: getMobileDetect().isWebView(),
};

export const setBaseLocation = (location) => {
  return {
    type: LOCATION_UPDATE,
    payload: location,
  };
};

export const setActiveEntity = (id) => {
  return {
    type: ENTITY_UPDATE,
    payload: id,
  };
};

export const setActivePageName = (name) => {
  return {
    type: SET_ACTIVE_PAGE_NAME,
    payload: name,
  };
};

export const toggleMobileMenu = () => {
  return {
    type: TOGGLE_MOBILE_MENU,
  };
};

export const resizeWindow = () => {
  return {
    type: RESIZE_WINDOW,
    payload: {
      windowWidth: window.innerWidth,
      windowHeight: window.outerHeight,
      isMobileResolution: isMobileResolution(window.innerWidth),
    },
  };
};

// Usage: If dual view slider is opened then 2 rows will be highlighted in the scene as per activeEntityId and activeSecEntityId
export const setSecActiveEntity = (id) => {
  return {
    type: SEC_ENTITY_UPDATE,
    payload: id,
  };
};

export const luminateRow = (id) => {
  return (dispatch) => {
    dispatch({
      type: ROW_LUMINATE_ADD,
      payload: { id },
    });

    setTimeout(() => {
      dispatch({
        type: ROW_LUMINATE_REMOVE,
      });
    }, 6000);
  };
};

export default (state = initialState, action) => {
  switch (action.type) {
    case ENTITY_UPDATE:
      return set(state, 'activeEntityId', action.payload);

    case SEC_ENTITY_UPDATE:
      return set(state, 'activeSecEntityId', action.payload);

    case LOCATION_UPDATE:
      return set(state, 'baseLocation', action.payload);

    case ROW_LUMINATE_ADD:
      return set(state, 'luminateRowId', action.payload.id);

    case ROW_LUMINATE_REMOVE:
      return set(state, 'luminateRowId', null);

    case SET_ACTIVE_PAGE_NAME:
      return set(state, 'activePageName', action.payload);

    case TOGGLE_MOBILE_MENU:
      return set(state, 'showMobileMenu', !state.showMobileMenu);

    case RESIZE_WINDOW: {
      const { windowWidth, windowHeight, isMobileResolution } = action.payload;

      state = set(state, 'windowWidth', windowWidth);
      state = set(state, 'windowHeight', windowHeight);
      state = set(state, 'isMobileResolution', isMobileResolution);

      // forcing not to show mobile menu in desktop resolution
      state = set(state, 'showMobileMenu', !isMobileResolution ? false : state.showMobileMenu);
      return state;
    }

    default:
      return state;
  }
};
