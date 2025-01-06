import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { screen, render } from 'test-utils';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoresList from 'merchant/views/StoreSettings/StoresList';

// Mock 'useStoreGroupsStore' Zustand store
jest.mock('../containers/StoreGroupsContainer/stores/storeGroupsStore', () => ({
  useStoreGroupsStore: jest.fn(),
}));

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');
  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
  };
});

const STORE_GROUP = {
  id: '1',
  name: 'All Stores',
  description: 'Default all stores group',
  isActive: true,
  storesCount: 10,
};

describe('StoresList', () => {
  test("should render 'StoresList' component as expected", () => {
    const setSelectedStoreGroupInfo = jest.fn();
    const resetStoreGroupsList = jest.fn();
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
      modalStatus: null,
      storeGroupsList: [STORE_GROUP],
      resetStoreGroupsList,
      setSelectedStoreGroupInfo,
      updateModalStatus: jest.fn(),
      updateDefaultDeletedStoresGroup: jest.fn(),
    });

    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        data: {},
        isFetching: false,
      })
      .mockReturnValueOnce({
        data: {},
        isFetching: false,
      });

    render(<StoresList />);

    const storeGroupsAccordion = screen.getByText('Store Groups');
    expect(storeGroupsAccordion).toBeInTheDocument();

    // 'Store Groups' should be expanded by default
    expect(screen.getByText('Group Name')).toBeInTheDocument();
    expect(setSelectedStoreGroupInfo).toHaveBeenLastCalledWith(STORE_GROUP);
  });
});
