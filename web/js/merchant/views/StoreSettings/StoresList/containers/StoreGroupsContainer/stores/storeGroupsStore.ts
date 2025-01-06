import create from 'zustand';

import { DEFAULT_STORE_GROUPS } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';

import type {
  StoreGroupForListing,
  StoreGroupModalStatus,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

type StoreGroupsStateType = {
  modalStatus: StoreGroupModalStatus;
  selectedStoreGroupInfo: StoreGroupForListing | null;
  storeGroupsList: StoreGroupForListing[];
  updateModalStatus: (status: StoreGroupModalStatus) => void;
  setSelectedStoreGroupInfo: (info: StoreGroupForListing | null) => void;
  updateStoreGroupsList: (storeGroups: StoreGroupForListing[], totalCount: number) => void;
  resetStoreGroupsList: () => void;
  updateDefaultAllStoresGroup: (defaultStoreGroup: StoreGroupForListing) => void;
  updateDefaultDeletedStoresGroup: (deletedStoreGroup: StoreGroupForListing) => void;
  totalCount: number;
};

export const useStoreGroupsStore = create<StoreGroupsStateType>((set) => ({
  modalStatus: null,
  selectedStoreGroupInfo: null,
  storeGroupsList: DEFAULT_STORE_GROUPS,
  totalCount: 0,
  updateModalStatus: (status) => set((state) => ({ ...state, modalStatus: status })),
  setSelectedStoreGroupInfo: (info) => set((state) => ({ ...state, selectedStoreGroupInfo: info })),
  updateStoreGroupsList: (storeGroups, totalCount) =>
    set((state) => {
      const lastIndex = state.storeGroupsList.length - 1;
      return {
        ...state,
        totalCount,
        storeGroupsList: [
          ...state.storeGroupsList.slice(0, lastIndex),
          ...storeGroups,
          ...state.storeGroupsList.slice(lastIndex),
        ],
      };
    }),
  resetStoreGroupsList: () =>
    set((state) => ({
      ...state,
      totalCount: 0,
      storeGroupsList: [
        ...state.storeGroupsList.slice(0, 1),
        ...state.storeGroupsList.slice(state.storeGroupsList.length - 1),
      ],
    })),
  updateDefaultAllStoresGroup: (defaultStoreGroup) =>
    set((state) => ({
      ...state,
      storeGroupsList: [defaultStoreGroup, ...state.storeGroupsList.slice(1)],
    })),
  updateDefaultDeletedStoresGroup: (defaultStoreGroup) =>
    set((state) => ({
      ...state,
      storeGroupsList: [
        state.storeGroupsList[0],
        ...state.storeGroupsList.slice(1, state.storeGroupsList.length - 1),
        defaultStoreGroup,
      ],
    })),
}));
