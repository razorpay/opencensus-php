import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import PartialCODEditConfigs from 'merchant/views/MagicCheckout/PartialCOD/components/PartialCODEditConfigs';
import { PartialCODContext } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';
import { PARTIAL_COD_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';

const mockPartialCODContext = {
  configsToShow: [
    {
      rules: {
        min_order_amount: 100000,
        customer_risk_category: ['high'],
      },
      type: 'flat',
      value: 9900,
    },
  ],
  configsLocal: { type: PARTIAL_COD_TYPE.BASIC },
  isPartialCODEnabledLocal: false,
  handleRemoveSlab: jest.fn(),
  handleAddSlab: jest.fn(),
  handleUpdateSlabType: jest.fn(),
  handleUpdateSlab: jest.fn(),
};

describe('PartialCODEditConfigs', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderComponent = (contextValue = null) => {
    const partialCODContext = contextValue ? contextValue : mockPartialCODContext;
    return render(
      <PartialCODContext.Provider
        value={{
          ...partialCODContext,
        }}
      >
        <PartialCODEditConfigs />
      </PartialCODContext.Provider>,
    );
  };

  it('should render the heading, icon, and switch correctly', () => {
    renderComponent();
    expect(screen.getByText('Enable Partial COD')).toBeInTheDocument();
    expect(screen.getByLabelText('Toggle Partial COD')).toBeInTheDocument();
    expect(screen.getByRole('switch')).toBeInTheDocument();
    expect(
      screen.getByText('Important action, if you use Shiprocket and want to enable Partial COD'),
    ).toBeInTheDocument();
  });

  it('should open the confirmation modal when the switch is toggled', async () => {
    renderComponent();
    const switchInput = screen.getByLabelText('Toggle Partial COD');
    userEvent.click(switchInput);

    await waitFor(() => {
      expect(screen.getByLabelText('base-confirm-modal')).toBeInTheDocument();
    });
  });

  it('should render AdvancedSlabConfig when configsLocal.type is ADVANCED', () => {
    const advancedContextValue = {
      ...mockPartialCODContext,
      configsLocal: { type: PARTIAL_COD_TYPE.ADVANCED },
    };
    renderComponent(advancedContextValue);
    expect(screen.getByText('Advanced partial COD slabs')).toBeInTheDocument();
  });

  it('should render Shiprocket Notice Modal when viewed', async () => {
    renderComponent();
    const viewBtn = screen.getByText('View');
    userEvent.click(viewBtn);
    await waitFor(() => {
      expect(screen.getByText('If you use Shiprocket for shipping')).toBeInTheDocument();
    });
  });
});
