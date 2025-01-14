import React, { Suspense } from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';

import AdvancedSlabConfig from 'merchant/views/MagicCheckout/PartialCOD/components/AdvancedSlabConfig';
import { PartialCODContext } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';

const mockPartialCODContext = {
  configsToShow: [
    {
      rules: {
        min_order_amount: 10000,
        max_order_amount: 20000,
        customer_risk_category: ['high'],
      },
      type: 'flat',
      value: 1500,
    },
  ],
  handleRemoveSlab: jest.fn(),
  handleAddSlab: jest.fn(),
  handleUpdateSlabType: jest.fn(),
  handleUpdateSlab: jest.fn(),
};

jest.setTimeout(30000);

describe('AdvancedSlabConfig', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    render(
      <PartialCODContext.Provider
        value={{
          ...mockPartialCODContext,
        }}
      >
        <Suspense fallback={null}>
          <AdvancedSlabConfig />
        </Suspense>
      </PartialCODContext.Provider>,
    );
  });
  it('should render the component with data', () => {
    expect(screen.getByText('Advanced partial COD slabs')).toBeInTheDocument();
    expect(screen.getByText('If amount is between ₹100 and ₹200')).toBeInTheDocument();
    expect(screen.getByText('High')).toBeInTheDocument();
  });

  it('should open the new slab modal', async () => {
    const newSlabButton = screen.getByRole('button', { name: /New Slab/i });
    userEvent.click(newSlabButton);

    await waitFor(() => {
      expect(screen.getByText(/New Partial COD Slab/i)).toBeInTheDocument();
    });
  });

  it('should trigger add slab handler on new slab creation', async () => {
    userEvent.click(screen.getByRole('button', { name: /New Slab/i }));
    await waitFor(() => {
      expect(screen.getByText(/New Partial COD Slab/i)).toBeInTheDocument();
    });
  });

  it('should open and confirms the delete and reset slabs modal', async () => {
    userEvent.click(screen.getByText(/Delete and reset slabs/i));
    await waitFor(() => {
      expect(screen.getByText('Delete and reset slabs?')).toBeInTheDocument();
      expect(
        screen.getByText(
          'This will delete all created slabs and reset to default settings. This action cannot be reversed.',
        ),
      ).toBeInTheDocument();
    });
  });
});
