import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import TogglePartialCODConfirmModal from 'merchant/views/MagicCheckout/PartialCOD/components/TogglePartialCODConfirmModal';

const mockOnConfirm = jest.fn();
const mockOnClose = jest.fn();

describe('TogglePartialCODConfirmModal', () => {
  it('should render the modal with "Enable Partial COD" title when isEnabled is false', () => {
    render(
      <TogglePartialCODConfirmModal
        onConfirm={mockOnConfirm}
        isEnabled={false}
        isOpen={true}
        onClose={mockOnClose}
      />,
    );

    expect(screen.getByText('Enable Partial COD?')).toBeInTheDocument();
  });

  it('should render the modal with "Disable Partial COD" title when isEnabled is true', () => {
    render(
      <TogglePartialCODConfirmModal
        onConfirm={mockOnConfirm}
        isEnabled={true}
        isOpen={true}
        onClose={mockOnClose}
      />,
    );

    expect(screen.getByText('Disable Partial COD?')).toBeInTheDocument();
  });

  it('should call onConfirm and onClose when "Enable" button is clicked', async () => {
    render(
      <TogglePartialCODConfirmModal
        onConfirm={mockOnConfirm}
        isEnabled={false}
        isOpen={true}
        onClose={mockOnClose}
      />,
    );

    const enableButton = screen.getByRole('button', { name: 'Enable' });
    await userEvent.click(enableButton);

    expect(mockOnConfirm).toHaveBeenCalled();
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('should call onConfirm and onClose when "Disable" button is clicked', async () => {
    render(
      <TogglePartialCODConfirmModal
        onConfirm={mockOnConfirm}
        isEnabled={true}
        isOpen={true}
        onClose={mockOnClose}
      />,
    );

    const disableButton = screen.getByRole('button', { name: 'Disable' });
    await userEvent.click(disableButton);

    expect(mockOnConfirm).toHaveBeenCalled();
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('should close the modal when the "Cancel" button is clicked', async () => {
    render(
      <TogglePartialCODConfirmModal
        onConfirm={mockOnConfirm}
        isEnabled={false}
        isOpen={true}
        onClose={mockOnClose}
      />,
    );

    const cancelButton = screen.getByText('Cancel');
    await userEvent.click(cancelButton);

    expect(mockOnClose).toHaveBeenCalled();
    expect(mockOnConfirm).not.toHaveBeenCalled();
  });
});
