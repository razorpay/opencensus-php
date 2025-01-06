import React from 'react';

import DeleteStoreModal from 'merchant/views/StoreSettings/StoreDetails/components/DeleteStoreModal';
import { screen, render, userEvent } from 'test-utils';

describe('DeleteStoreModal', () => {
  test("should render 'DeleteStoreModal' component as expected", async () => {
    const closeModal = jest.fn();
    const onSubmit = jest.fn();

    render(
      <DeleteStoreModal
        modalProps={{
          isOpen: true,
          onDismiss: closeModal,
        }}
        onSubmit={onSubmit}
        isLoading={false}
      />,
    );
    expect(screen.getByText('Heads Up!')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Store cannot be recovered once deleted. Are you sure you want to delete this store?',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'This will also impact the attached products, roles and permissions, reporting and billing.',
      ),
    ).toBeInTheDocument();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    expect(closeModal).toHaveBeenCalledTimes(1);

    const deleteBtn = screen.getByRole('button', { name: 'Delete' });
    expect(deleteBtn).toBeInTheDocument();
    await userEvent.click(deleteBtn);
    expect(onSubmit).toHaveBeenCalledTimes(1);
  });
});
