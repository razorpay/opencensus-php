import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import AdvancedSlabModal from 'merchant/views/MagicCheckout/PartialCOD/components/AdvancedSlabModal';
import { PREPAID_PAYMENY_AMOUNT_ITEM_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';
import { validateCreateAdvancedSlab } from 'merchant/views/MagicCheckout/PartialCOD/helpers/validations';

const mockCloseModal = jest.fn();
const mockOnConfirm = jest.fn();

jest.mock('merchant/views/MagicCheckout/PartialCOD/helpers/validations', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/PartialCOD/helpers/validations'),
  validateCreateAdvancedSlab: jest.fn(),
}));

describe('AdvancedSlabModal', () => {
  beforeEach(() => {
    validateCreateAdvancedSlab.mockReturnValue(true);
  });

  test('should render the modal', () => {
    render(<AdvancedSlabModal onConfirm={mockOnConfirm} isOpen={true} onClose={mockCloseModal} />);
    expect(screen.getByText('New Partial COD Slab')).toBeInTheDocument();
    expect(screen.getByText('Min ₹')).toBeInTheDocument();
    expect(screen.getByText('Max ₹')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter amount')).toBeInTheDocument();
    expect(screen.getByText('Customer Risk')).toBeInTheDocument();
  });

  test('should trigger closeModal when Cancel button is clicked', async () => {
    render(<AdvancedSlabModal onConfirm={mockOnConfirm} isOpen={true} onClose={mockCloseModal} />);

    // Find and click the Cancel button
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);

    expect(mockCloseModal).toHaveBeenCalled();
  });

  it('should call onConfirm with the correct data when the Confirm button is clicked', async () => {
    const slabData = {
      type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
      value: '200',
      rules: {
        min_order_amount: '500',
        max_order_amount: '1000',
        customer_risk_category: ['LOW'],
      },
    };

    render(<AdvancedSlabModal onConfirm={mockOnConfirm} configData={slabData} isOpen={true} />);
    const confirmButton = screen.getByRole('button', { name: 'Confirm' });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(validateCreateAdvancedSlab).toHaveBeenCalled();
      expect(mockOnConfirm).toHaveBeenCalledWith(
        {
          type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
          value: 200,
          rules: {
            min_order_amount: 500,
            max_order_amount: 1000,
            customer_risk_category: ['LOW'],
          },
        },
        expect.any(Object),
      );
    });
  });

  it('should display validation errors when validation fails', async () => {
    const slabData = {
      type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
      value: '200',
      rules: {
        min_order_amount: '500',
        max_order_amount: '1000',
        customer_risk_category: ['LOW'],
      },
    };

    validateCreateAdvancedSlab.mockImplementation((_, setErrors) => {
      setErrors({
        min_order_amount: 'Min amount is required',
        max_order_amount: '',
        customer_risk_category: '',
        value: '',
      });
      return false;
    });

    render(<AdvancedSlabModal onConfirm={mockOnConfirm} configData={slabData} isOpen={true} />);

    const confirmButton = screen.getByRole('button', { name: 'Confirm' });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(screen.getByText('Min amount is required')).toBeVisible();
      expect(mockOnConfirm).not.toHaveBeenCalled(); // should not call onConfirm if validation fails
    });
  });
});
