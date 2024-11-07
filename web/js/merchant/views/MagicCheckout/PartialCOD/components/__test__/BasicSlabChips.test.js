import React, { Suspense } from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import BasicSlabChips from 'merchant/views/MagicCheckout/PartialCOD/components/BasicSlabChips';
import { PREPAID_PAYMENY_AMOUNT_ITEM_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';

describe('BasicSlabChips', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const initialSlab = {
    type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
    value: 9900,
    rules: {
      min_order_amount: 5000,
      max_order_amount: 10000,
      customer_risk_category: [],
    },
  };

  const renderComponent = (slab = initialSlab, setActiveSlab = jest.fn()) =>
    render(
      <Suspense fallback={null}>
        <BasicSlabChips activeBasicSlab={slab} setActiveBasicSlab={setActiveSlab} />
      </Suspense>,
    );

  it('should render the chip options correctly', () => {
    renderComponent();
    expect(screen.getByText('₹ 99')).toBeInTheDocument();
    expect(screen.getByText('₹ 199')).toBeInTheDocument();
    expect(screen.getByText('5%')).toBeInTheDocument();
    expect(screen.getByText('10%')).toBeInTheDocument();
    expect(screen.getByText('Custom Value')).toBeInTheDocument();
  });

  it('should open the modal for custom value selection', async () => {
    renderComponent();

    const customValueChip = screen.getByText('Custom Value');
    userEvent.click(customValueChip);

    await waitFor(() => {
      expect(screen.getByText('Set a custom value for partial COD')).toBeInTheDocument();
    });
  });

  it('should display custom value in the chip when a custom value is selected', async () => {
    const customSlab = {
      type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
      value: 5000, // Custom value
      rules: {
        min_order_amount: 5000,
        max_order_amount: 10000,
        customer_risk_category: [],
      },
    };

    renderComponent(customSlab);

    expect(screen.getByText(/(₹50)/)).toBeInTheDocument(); // Custom value displayed
  });
});
