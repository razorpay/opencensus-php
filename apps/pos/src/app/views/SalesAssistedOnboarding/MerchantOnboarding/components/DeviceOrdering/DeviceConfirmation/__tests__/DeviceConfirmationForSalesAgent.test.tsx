import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import DeviceConfirmationForSalesAgent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceConfirmation/DeviceConfirmationForSalesAgent';
import { getMockUseOnboardingContext } from './mocks/fixtures';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
);

describe('DeviceConfirmationForSalesAgent', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const mockContext = getMockUseOnboardingContext();
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();
  const getComponentConfigFromStep = jest.fn().mockReturnValue({ title: 'Order Confirmation' });
  const getStepConfigStepSlug = jest.fn().mockReturnValue({ modularKey: 'device_selection_step' });
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
    handlers: {
      ...mockContext.handlers,
      updateModularConfig,
      handleProceedToNextComponent,
      getComponentConfigFromStep,
      getStepConfigStepSlug,
    },
  });

  const renderComponent = () => render(<DeviceConfirmationForSalesAgent />);

  describe('DeviceConfirmationForSalesAgent', () => {
    test('should render the component when necessary config is present', () => {
      renderComponent();
      expect(screen.getByText(/Order Confirmation/i)).toBeInTheDocument();
    });

    test('should disable the component if device selection is completed', () => {
      renderComponent();
      const saveButton = screen.getByRole('button', { name: /confirm order/i });
      expect(saveButton).toBeDisabled();
    });
  });
});
