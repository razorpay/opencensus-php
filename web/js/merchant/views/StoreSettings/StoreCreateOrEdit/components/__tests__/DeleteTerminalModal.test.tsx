import React from 'react';

import DeleteTerminalModal from 'merchant/views/StoreSettings/StoreCreateOrEdit/components/DeleteTerminalModal';
import { act, screen, render, userEvent, waitFor } from 'test-utils';

describe('DeleteTerminalModal', () => {
  test('should render the component', async () => {
    const closeModal = jest.fn();
    const onSubmit = jest.fn();
    render(
      <DeleteTerminalModal
        modalProps={{ isOpen: true, onDismiss: closeModal }}
        onSubmit={onSubmit}
        isLoading={false}
      />,
    );
    expect(screen.getByText('Alert')).toBeInTheDocument();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cancelBtn);
    });
    await waitFor(() => {
      expect(closeModal).toHaveBeenCalledTimes(1);
    });
    const submitBtn = screen.getByRole('button', { name: 'Delete' });
    expect(submitBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(submitBtn);
    });
    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledTimes(1);
    });
  });
});
