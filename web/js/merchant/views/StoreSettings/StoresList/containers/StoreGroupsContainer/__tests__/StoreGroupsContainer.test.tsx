import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoreGroupsContainer from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/StoreGroupsContainer';
import { DEFAULT_STORE_GROUPS } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { screen, render, userEvent } from 'test-utils';

// Mock 'useStoreGroupsStore' Zustand store
jest.mock('../stores/storeGroupsStore', () => ({
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

const updateModalStatus = jest.fn();
const setSelectedStoreGroupInfo = jest.fn();
const STORE_GROUPS_STORE = {
  modalStatus: null,
  storeGroupsList: DEFAULT_STORE_GROUPS,
  updateModalStatus,
  setSelectedStoreGroupInfo,
  updateDefaultDeletedStoresGroup: jest.fn(),
};

describe('StoreGroupsContainer', () => {
  test("should render 'StoreGroupsContainer' component as expected", async () => {
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue(STORE_GROUPS_STORE);

    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        data: {},
        isFetching: false,
      })
      .mockReturnValueOnce({
        data: {},
        isFetching: false,
      });

    render(<StoreGroupsContainer isAccordionExpanded />);
    expect(screen.getByText('Group Name')).toBeInTheDocument();
    const addNewGroupButton = screen.getByRole('button', { name: 'Add New Group' });
    expect(addNewGroupButton).toBeInTheDocument();
    await userEvent.click(addNewGroupButton);
    expect(updateModalStatus).toHaveBeenLastCalledWith('create');

    // Default StoreGroupCards
    expect(screen.getByText('All Stores')).toBeInTheDocument();
    expect(screen.getByText('Default all stores group')).toBeInTheDocument();

    expect(screen.getByText('Deleted Stores')).toBeInTheDocument();
    expect(screen.getByText('A collection of all deleted stores')).toBeInTheDocument();

    expect(screen.getAllByText('0 stores')).toHaveLength(2);
  });

  test("should render loader when 'getAllStoreGroups' call is fetching", () => {
    const updateModalStatus = jest.fn();
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue(STORE_GROUPS_STORE);

    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        data: {},
        isFetching: true,
      })
      .mockReturnValueOnce({
        data: {},
        isFetching: true,
      });

    render(<StoreGroupsContainer isAccordionExpanded />);
    expect(screen.getByText('Group Name')).toBeInTheDocument();
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
});
