import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import BasicSlabModal from 'merchant/views/MagicCheckout/PartialCOD/components/BasicSlabModal';
import { PREPAID_PAYMENY_AMOUNT_ITEM_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';
import { validateBasicSlab } from 'merchant/views/MagicCheckout/PartialCOD/helpers/validations';

const mockOnSave = jest.fn();
const mockOnClose = jest.fn();

jest.mock('merchant/views/MagicCheckout/PartialCOD/helpers/validations', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/PartialCOD/helpers/validations'),
  validateBasicSlab: jest.fn(),
}));

describe('BasicSlabModal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    validateBasicSlab.mockReturnValue(true);
  });

  const renderComponent = () =>
    render(<BasicSlabModal onSave={mockOnSave} onClose={mockOnClose} isOpen={true} />);

  it('should render modal correctly', () => {
    renderComponent();
    expect(screen.getByText('Set a custom value for partial COD')).toBeInTheDocument();
    expect(
      screen.getByText(
        /This is the percentage of total cart value that will be charged as partial COD payment. Cannot be more than 50%/,
      ),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('Value')).toBeInTheDocument();
    expect(screen.getByText('Save')).toBeInTheDocument();
  });

  it('should call onSave with correct values when valid input is provided', async () => {
    renderComponent();

    // Enter value
    const valueInput = screen.getByLabelText('Value');
    await userEvent.type(valueInput, '200');

    // Click Save
    const saveButton = screen.getByText('Save');
    await userEvent.click(saveButton);

    // Assert that validateBasicSlab was called
    expect(validateBasicSlab).toHaveBeenCalledWith(
      '200',
      PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE,
      expect.any(Function),
    );

    // Assert that onSave is called with correct value
    expect(mockOnSave).toHaveBeenCalledWith(200, PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE);
  });

  it('should display error when validation fails', async () => {
    validateBasicSlab.mockImplementation(() => false);
    renderComponent();

    // Enter invalid value
    const valueInput = screen.getByLabelText('Value');
    await userEvent.type(valueInput, '1000');

    // Click Save
    const saveButton = screen.getByText('Save');
    await userEvent.click(saveButton);

    // Validate that save was not called due to validation failure
    expect(mockOnSave).not.toHaveBeenCalled();
    expect(mockOnClose).not.toHaveBeenCalled();
  });

  it('should close the modal on dismiss', async () => {
    renderComponent();

    // Simulate closing modal
    const dismissButton = screen.getByLabelText('Close'); // Assuming there's a close button or icon with aria-label="Close"
    await userEvent.click(dismissButton);

    expect(mockOnClose).toHaveBeenCalled();
  });
});
