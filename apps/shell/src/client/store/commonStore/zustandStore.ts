import create from 'zustand';
import { devtools } from 'zustand/middleware';
import { getMode } from '@libs/shared-utils';

import { getOpenModalState, getCloseModalState } from './stateActions/modals';
import { getShowNotificationState, getHideNotificationState } from './stateActions/notifications';

import type { Store } from './types';

export const initialState = {
  session: {
    user: window?.rzp_user,
    org: window?.rzp_org,
    mode: getMode(window?.rzp_user?.current),
    
  } as Store['session'],
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

// TODO:
// 1. Abstracted usage integration
// 2. Allow
