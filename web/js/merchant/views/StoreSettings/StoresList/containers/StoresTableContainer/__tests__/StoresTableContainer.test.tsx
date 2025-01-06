import React from 'react';

import { DEFAULT_STORE_GROUPS } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoresTableContainer from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';
import {
  STORES_TABLE_STORE_MOCK,
  STORE_GROUPS_STORE_MOCK,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/__tests__/mocks';
import { screen, render, userEvent } from 'test-utils';

// Mock 'useStoreGroupsStore' Zustand store
jest.mock('../../StoreGroupsContainer/stores/storeGroupsStore', () => ({
  useStoreGroupsStore: jest.fn(),
}));

// Mock 'useStoresTablePayloadStore' Zustand store
jest.mock('../stores/storesTablePayloadStore', () => ({
  useStoresTablePayloadStore: jest.fn(),
}));

const App = ({ props }) => <StoresTableContainer {...props} />;

describe('StoresTableContainer', () => {
  test("should render loader when 'isStoreGroupsActive' prop is false", () => {
    (useStoresTablePayloadStore as unknown as jest.Mock).mockReturnValue(STORES_TABLE_STORE_MOCK);
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue(STORE_GROUPS_STORE_MOCK);

    render(<App props={{ isStoreGroupsActive: false }} />);
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  test("should render 'StoresTableContainer' component as expected", async () => {
    const updateModalStatus = jest.fn();

    const setStoresFilterSearchTerm = jest.fn();
    (useStoresTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      ...STORES_TABLE_STORE_MOCK,
      setStoresFilterSearchTerm,
    });

    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
      modalStatus: null,
      updateModalStatus,
      selectedStoreGroupInfo: {
        id: '123',
        name: 'Test Group',
      },
    });
    const props = {
      isStoreGroupsActive: true,
    };

    render(<App props={props} />);
    expect(screen.getByText('Test Group')).toBeInTheDocument();

    // Edit Group Button
    const editGroupBtn = screen.getByRole('button', { name: 'Edit Group' });
    expect(editGroupBtn).toBeInTheDocument();
    await userEvent.click(editGroupBtn);
    expect(updateModalStatus).toHaveBeenLastCalledWith('update');

    // Delete Group Button
    const deleteGroupBtn = screen.getByRole('button', { name: 'Delete Group' });
    expect(deleteGroupBtn).toBeInTheDocument();
    await userEvent.click(deleteGroupBtn);
    expect(updateModalStatus).toHaveBeenLastCalledWith('delete');

    // StoresSearchComponent
    expect(screen.getByPlaceholderText('Search')).toBeInTheDocument();

    // StoresTableComponent
    expect(screen.getByText('Store Name')).toBeInTheDocument();

    // 'New Store' CTA button should not be displayed when Store Group selected is not 'All Stores'
    expect(screen.queryByRole('button', { name: /New Store/ })).not.toBeInTheDocument();

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    await userEvent.type(searchField, '12');
    expect(setStoresFilterSearchTerm).toHaveBeenCalledTimes(2);
  });

  test.each([
    { type: 'All Stores', value: DEFAULT_STORE_GROUPS[0] },
    { type: 'Deleted Stores', value: DEFAULT_STORE_GROUPS[1] },
  ])(
    "should not render 'Edit Group' and 'Delete Group' buttons when active Store Group is %type",
    ({ type, value }) => {
      (useStoresTablePayloadStore as unknown as jest.Mock).mockReturnValue(STORES_TABLE_STORE_MOCK);
      (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
        ...STORE_GROUPS_STORE_MOCK,
        selectedStoreGroupInfo: value,
      });

      render(<App props={{ isStoreGroupsActive: true }} />);
      expect(screen.getByText(type)).toBeInTheDocument();

      // Edit Group Button
      const editGroupBtn = screen.queryByRole('button', { name: 'Edit Group' });
      expect(editGroupBtn).not.toBeInTheDocument();

      // Delete Group Button
      const deleteGroupBtn = screen.queryByRole('button', { name: 'Delete Group' });
      expect(deleteGroupBtn).not.toBeInTheDocument();

      // StoresSearchComponent
      expect(screen.getByPlaceholderText('Search')).toBeInTheDocument();

      // StoresTableComponent
      expect(screen.getByText('Store Name')).toBeInTheDocument();

      // 'New Store' CTA button should be displayed when Store Group selected is 'All Stores'
      if (type === 'All Stores') {
        expect(screen.getByRole('button', { name: /New Store/ })).toBeInTheDocument();
      } else {
        expect(screen.queryByRole('button', { name: /New Store/ })).not.toBeInTheDocument();
      }
    },
  );

  test("should invoke 'setStoresFilterOffset' with offset as '0', when current page is not '1' during search", async () => {
    const setStoresFilterOffset = jest.fn();
    const setStoresFilterSearchTerm = jest.fn();
    (useStoresTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      ...STORES_TABLE_STORE_MOCK,
      setStoresFilterOffset,
      setStoresFilterSearchTerm,
    });

    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue(STORE_GROUPS_STORE_MOCK);
    render(<App props={{ isStoreGroupsActive: true }} />);

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    await userEvent.type(searchField, '12');
    await userEvent.type(searchField, '{enter}');
    expect(setStoresFilterSearchTerm).toHaveBeenCalledTimes(2);
    expect(setStoresFilterOffset).toHaveBeenLastCalledWith(0);
  });
});
