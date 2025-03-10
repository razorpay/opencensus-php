import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { DeleteBlockModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/DeleteBlockModal';
describe('DeleteBlockModal', () => {
  const mockOnClose = jest.fn();
  const mockOnDelete = jest.fn();
  const blockName = 'Test Block';

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render correctly when isOpen is true', () => {
    render(
      <DeleteBlockModal
        isOpen={true}
        onClose={mockOnClose}
        blockName={blockName}
        onDelete={mockOnDelete}
      />,
    );

    expect(screen.getByText(`Delete “${blockName}” custom block?`)).toBeInTheDocument();
    expect(
      screen.getByText(
        `This action will permanently delete “${blockName}” custom block. This custom block will no longer be accessible or available to you.`,
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Cancel/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Delete/i })).toBeInTheDocument();
  });

  test('should not render when isOpen is false', () => {
    render(
      <DeleteBlockModal
        isOpen={false}
        onClose={mockOnClose}
        blockName={blockName}
        onDelete={mockOnDelete}
      />,
    );

    expect(screen.queryByText(`Delete “${blockName}” custom block?`)).not.toBeInTheDocument();
  });

  test('should call onClose when Cancel button is clicked', () => {
    render(
      <DeleteBlockModal
        isOpen={true}
        onClose={mockOnClose}
        blockName={blockName}
        onDelete={mockOnDelete}
      />,
    );

    fireEvent.click(screen.getByRole('button', { name: /Cancel/i }));
    expect(mockOnClose).toHaveBeenCalledTimes(1);
  });

  test('should call onDelete when Delete button is clicked', () => {
    render(
      <DeleteBlockModal
        isOpen={true}
        onClose={mockOnClose}
        blockName={blockName}
        onDelete={mockOnDelete}
      />,
    );

    fireEvent.click(screen.getByRole('button', { name: /Delete/i }));
    expect(mockOnDelete).toHaveBeenCalledTimes(1);
  });
});
