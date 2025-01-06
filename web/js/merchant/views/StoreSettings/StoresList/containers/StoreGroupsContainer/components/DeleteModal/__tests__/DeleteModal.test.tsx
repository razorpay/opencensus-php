import React from 'react';
import { useMutation } from '@tanstack/react-query';
import DeleteModal from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/DeleteModal/DeleteModal';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import { screen, render, userEvent, act } from 'test-utils';

// Mock 'useMutation'
jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

// Mock 'useStoreGroupsStore' Zustand store
jest.mock('../../../stores/storeGroupsStore', () => ({
  useStoreGroupsStore: jest.fn(),
}));

describe('DeleteModal', () => {
  test("should render 'DeleteModal' component as expected", async () => {
    const updateModalStatus = jest.fn();
    const mutate = jest.fn();

    (useMutation as jest.Mock).mockReturnValue({
      mutate,
    });

    (useStoreGroupsStore as unknown as jest.Mock).mockReturnValue({
      modalStatus: 'delete',
      selectedStoreGroupInfo: {
        id: '123',
      },
      updateModalStatus,
      setSelectedStoreGroupInfo: jest.fn(),
      setStoreGroupsListOffset: jest.fn(),
    });

    render(<DeleteModal />);
    expect(screen.getByText('Alert')).toBeInTheDocument();
    expect(screen.getByText('Group once deleted cannot be recovered.')).toBeInTheDocument();
    expect(screen.getByText('Note: The store data will not be deleted.')).toBeInTheDocument();
    expect(screen.getByText('Are you sure you want to delete this group?')).toBeInTheDocument();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cancelBtn);
    });
    expect(updateModalStatus).toHaveBeenCalledWith(null);

    const deleteBtn = screen.getByRole('button', { name: 'Delete' });
    expect(deleteBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(deleteBtn);
    });
    expect(mutate).toHaveBeenCalledTimes(1);
  });
});
