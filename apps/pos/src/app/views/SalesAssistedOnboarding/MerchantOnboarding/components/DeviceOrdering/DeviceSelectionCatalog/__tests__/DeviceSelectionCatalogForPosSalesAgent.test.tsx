import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import DeviceSelectionCatalogForPosSalesAgent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceSelectionCatalog/DeviceSelectionCatalogForPosSalesAgent';
import * as modularConfigUtils from 'apps/pos/src/app/utils/modularConfig';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
);

describe('DeviceSelectionCatalogForPosSalesAgent', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderApp = (props = {}) => {
    return render(<DeviceSelectionCatalogForPosSalesAgent {...props} />);
  };

  test('renders Device selection catalog when modularConfig is present', () => {
    const mockContext = getMockUseOnboardingContext();
    const getComponentConfigFromStep = jest.fn();
    const getStepConfigStepSlug = jest.fn();
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      handlers: {
        ...mockContext.handlers,
        getComponentConfigFromStep: getComponentConfigFromStep.mockReturnValue({
          modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
        }),
        getStepConfigStepSlug: getStepConfigStepSlug.mockReturnValue({
          modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
        }),
      },
    });
    renderApp();
    expect(screen.queryByText(/something went wrong/i)).not.toBeInTheDocument();
  });
  test('renders Device selection container when device step is executed', () => {
    const mockContext = getMockUseOnboardingContext();
    const getComponentConfigFromStep = jest.fn();
    const getStepConfigStepSlug = jest.fn();
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      handlers: {
        ...mockContext.handlers,
        getComponentConfigFromStep: getComponentConfigFromStep.mockReturnValue({
          modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
        }),
        getStepConfigStepSlug: getStepConfigStepSlug.mockReturnValue({
          modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
        }),
      },
    });
    jest.spyOn(modularConfigUtils, 'getProgressFromModularStep').mockReturnValue('completed');
    renderApp();
    expect(screen.queryByText(/something went wrong/i)).not.toBeInTheDocument();
  });
});
