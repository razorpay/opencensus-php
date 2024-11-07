import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import ResetSlabsConfirmModal from 'merchant/views/MagicCheckout/PartialCOD/components/ResetSlabsConfirmModal';

const mockOnConfirm = jest.fn();
const mockOnClose = jest.fn();

describe('ResetSlabsConfirmModal', () => {
  const renderApp = () =>
    render(
      <ResetSlabsConfirmModal onConfirm={mockOnConfirm} onClose={mockOnClose} isOpen={true} />,
    );

  test('ResetSlabsConfirmModal component should be defined', () => {
    expect(ResetSlabsConfirmModal).toBeDefined();
  });

  test('renders the modal', () => {
    renderApp();
    // Check for modal title
    expect(screen.getByText('Delete and reset slabs?')).toBeInTheDocument();
  });

  test('should trigger closeModal when Cancel button is clicked', async () => {
    renderApp();

    // Find and click the Cancel button
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);

    // Assert that closeModal was called
    expect(mockOnClose).toHaveBeenCalled();
  });

  test('should trigger onConfirm fn when Reset button is clicked', async () => {
    renderApp();

    // Find and click the Reset button
    const resetButton = screen.getByRole('button', { name: 'Reset' });
    await userEvent.click(resetButton);

    // Assert that onConfirm was called
    expect(mockOnConfirm).toHaveBeenCalled();
  });
});
