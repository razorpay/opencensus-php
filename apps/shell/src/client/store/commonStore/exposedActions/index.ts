import { initialState, useStore } from '../zustandStore';

export const getOrg = () => useStore.getState().session.org;

export const getUser = () => useStore.getState().session.user;

export const getPartnerMode = () => useStore.getState().session.partnerMode;

export const getMode = () => {
  if (useStore.getState().session.isUsingPartnerMode) {
    return getPartnerMode();
  }
  return useStore.getState().session.mode;
};

export const clearStore = () => useStore.setState({ ...initialState });

// /**
//  * Directly call these inside utils, components, etc
//  */
// export const showNotification = useStore((state) => state.showNotification);
// export const openModal = useStore((state) => state.openModal);
// export const closeModal = useStore((state) => state.closeModal);
