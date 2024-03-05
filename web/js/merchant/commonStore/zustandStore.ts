import create from 'zustand';
import { devtools } from 'zustand/middleware';

import { getOpenModalState, getCloseModalState } from './stateActions/modals';
import { getShowNotificationState, getHideNotificationState } from './stateActions/notifications';

import type { Store } from './types';

const initialState = {
  session: {} as Store['session'],
  app: {} as Store['app'],
  notifications: {
    notifications: [],
  } as Store['notifications'],
  modal: {} as Store['modal'],
};

export const useStore = create<Store>(
  devtools((set) => ({
    ...initialState,
    showNotification: (payload) => {
      set((state) => ({
        notifications: getShowNotificationState(state.notifications, payload),
      }));
    },
    hideNotification: (payload) => {
      set((state) => ({
        notifications: getHideNotificationState(state.notifications, payload),
      }));
    },
    openModal: (payload) => {
      set((state) => ({
        modal: getOpenModalState(state.modal, payload),
      }));
    },
    closeModal: () => {
      set(() => ({
        modal: getCloseModalState(),
      }));
    },
  })),
);

export const getOrg = () => useStore.getState().session.org;
export const getUser = () => useStore.getState().session.user;
export const getPartnerMode = () => useStore.getState().session.partnerMode;
export const getMode = () => {
  if (useStore.getState().session.isUsingPartnerMode) {
    return getPartnerMode();
  }
  return useStore.getState().session.mode;
};

// For Unittests only
export const clearStore = () => useStore.setState({ ...initialState });
