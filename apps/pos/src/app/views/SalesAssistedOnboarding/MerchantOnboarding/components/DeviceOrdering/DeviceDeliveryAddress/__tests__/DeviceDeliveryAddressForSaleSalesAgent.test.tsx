import React from 'react';
import DeviceDeliveryAddressForSaleSalesAgent from '../DeviceDeliveryAddressForSaleSalesAgent';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

const renderApp = () => {
  render(<DeviceDeliveryAddressForSaleSalesAgent />);
};

describe('DeviceDeliveryAddressForSaleSalesAgent', () => {
  const mockContext = getMockUseOnboardingContext();
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
  });

  test('should return null when modular config is absent', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: null,
      },
    });
    renderApp();
    expect(screen.queryByText('Device delivery address')).not.toBeInTheDocument();
  });

  test('should render device delivery address selection component on screen', () => {
    renderApp();
    expect(screen.getByText('Device delivery address')).toBeInTheDocument();
    expect(screen.getByText('Registered Address')).toBeInTheDocument();
    expect(screen.getByText('Operational Address')).toBeInTheDocument();
    expect(screen.getAllByText('Test Merchant').length).toBe(2);
    expect(screen.getAllByText('1234567890').length).toBe(2);
    expect(screen.getAllByText('32, 1st ave').length).toBe(2);
  });

  test('should call update modular with correct payload on confirmation', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Confirm Delivery Address'));
    expect(mockContext.handlers.updateModularConfig).toHaveBeenCalled();
  });
});
