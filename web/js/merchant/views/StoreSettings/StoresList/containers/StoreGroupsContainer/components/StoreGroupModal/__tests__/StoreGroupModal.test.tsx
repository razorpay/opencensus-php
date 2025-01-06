import React from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';

import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoreGroupModal from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal/StoreGroupModal';
import {
  STATES_AND_CITIES_MOCK_RESPONSE,
  STORE_GROUP_INFO,
  STORE_INFO,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal/__tests__/mocks';
import { StoreGroupModalStatus } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { screen, render, userEvent, act } from 'test-utils';

// Mock 'useMutation'
jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

// Mock 'useStoreGroupsStore' Zustand store
jest.mock('../../../stores/storeGroupsStore', () => ({
  useStoreGroupsStore: jest.fn(),
}));

const App = ({ props }) => <StoreGroupModal {...props} />;

describe('StoreGroupModal', () => {
  test("should render 'StoreGroupModal' for create operation", async () => {
    (useQuery as jest.Mock).mockReturnValueOnce(STATES_AND_CITIES_MOCK_RESPONSE).mockReturnValue({
      data: {
        stores: {
          limit: 5,
          offset: 0,
          total: 10,
          stores: [STORE_INFO],
        },
      },
      isFetching: false,
      error: false,
      refetch: jest.fn(),
      isSuccess: true,
    });

    const updateModalStatus = jest.fn();
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
      modalStatus: StoreGroupModalStatus.CREATE,
      updateModalStatus,
      selectedStoreGroupInfo: {},
    });

    const createMutate = jest.fn();
    (useMutation as jest.Mock).mockReturnValue({ mutate: createMutate });

    const props = {
      showModal: true,
      title: 'Add New Group',
      submitBtnText: 'Add',
    };

    render(<App props={props} />);

    // Header
    expect(screen.getByText('Add New Group')).toBeInTheDocument();
    expect(screen.getByText('Group your stores for better filtering of data.')).toBeInTheDocument();

    // Content
    const groupNameField = screen.getByPlaceholderText('Enter Group Name');
    expect(groupNameField).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter Group Description')).toBeInTheDocument();

    // Name field - maxCharacters validation
    await act(async () => {
      await userEvent.type(
        groupNameField,
        'Test Store Group Name field with a lengthy name more than 50 characters',
      );
    });
    expect(
      screen.getByText('Store Group name cannot be more than 50 characters'),
    ).toBeInTheDocument();

    await act(async () => {
      await userEvent.clear(groupNameField);
    });
    expect(
      screen.queryByText('Store Group name cannot be more than 50 characters'),
    ).not.toBeInTheDocument();

    const addButton = screen.getByRole('button', { name: 'Add' });
    // Submit button should be disabled when no store is selected and store group name field is empty
    expect(addButton).toBeDisabled();

    await act(async () => {
      await userEvent.type(groupNameField, 'Test');
    });
    expect(addButton).toBeDisabled();

    // Footer
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelButton).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cancelButton);
    });
    expect(updateModalStatus).toHaveBeenLastCalledWith(null);
  });

  test("should render 'StoreGroupModal' for update operation", async () => {
    const updateModalStatus = jest.fn();
    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
      modalStatus: StoreGroupModalStatus.UPDATE,
      updateModalStatus,
      selectedStoreGroupInfo: STORE_GROUP_INFO,
    });

    (useQuery as jest.Mock).mockReturnValue({
      data: {
        storeGroupById: { ...STORE_GROUP_INFO, isActive: true },
      },
      isFetching: false,
      refetch: jest.fn(),
    });

    const updateMutate = jest.fn();
    (useMutation as jest.Mock).mockReturnValue({ mutate: updateMutate });

    render(
      <App
        props={{
          showModal: true,
          title: 'Edit Group',
          submitBtnText: 'Save',
        }}
      />,
    );

    // Header
    expect(screen.getByText('Edit Group')).toBeInTheDocument();
    expect(screen.getByText('Group your stores for better filtering of data.')).toBeInTheDocument();

    // Content
    const groupNameField = screen.getByPlaceholderText('Enter Group Name');
    expect(groupNameField).toHaveValue('Test Store Group Name');
    const groupDescriptionField = screen.getByPlaceholderText('Enter Group Description');
    expect(groupDescriptionField).toHaveValue('Test Store Group Description');

    // Footer
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelButton).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cancelButton);
    });
    expect(updateModalStatus).toHaveBeenLastCalledWith(null);
    const saveButton = screen.getByRole('button', { name: 'Save' });
    expect(saveButton).toBeEnabled();

    await act(async () => {
      await userEvent.click(saveButton);
    });
    expect(updateMutate).toHaveBeenCalledTimes(1);

    // Submit button should be disabled when store group name field is empty
    await act(async () => {
      await userEvent.clear(groupNameField);
    });
    expect(groupNameField).toHaveValue('');
    expect(screen.getByText('Name is a mandatory field')).toBeInTheDocument();
    expect(saveButton).toBeDisabled();
  });
});
