import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { FileIcon } from '@razorpay/blade/components';
import MerchantOnboardingStep from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingStep';
import { AvailableComponents } from 'apps/pos/src/app/types/common';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
);

describe('MerchantOnboardingStep', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderApp = (props = {}) => {
    return render(<MerchantOnboardingStep {...props} />);
  };

  test('renders merchant onboarding step when modularConfig is present', () => {
    const mockContext = getMockUseOnboardingContext(<FileIcon />);
    const getComponentConfigFromStep = jest.fn();
    const getStepConfigStepSlug = jest.fn();
    const getFirstComponentOfStep = jest.fn();
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
        getFirstComponentOfStep: getFirstComponentOfStep.mockReturnValue(
          AvailableComponents.DEVICE_SELECTION_CATALOG,
        ),
      },
    });
    renderApp();
    expect(
      screen.queryByText(/Invalid step. Could not find step config./i),
    ).not.toBeInTheDocument();
  });
  test('renders Invalid step for invalid step value', () => {
    const mockContext = getMockUseOnboardingContext(<FileIcon />);
    const getComponentConfigFromStep = jest.fn();
    const getStepConfigStepSlug = jest.fn();
    const getFirstComponentOfStep = jest.fn();
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      handlers: {
        ...mockContext.handlers,
        getComponentConfigFromStep: getComponentConfigFromStep.mockReturnValue({
          modularKey: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
        }),
        getStepConfigStepSlug: getStepConfigStepSlug.mockReturnValue(null),
        getFirstComponentOfStep: getFirstComponentOfStep.mockReturnValue(
          AvailableComponents.DEVICE_SELECTION_CATALOG,
        ),
      },
    });
    renderApp();
    expect(screen.getByText(/Invalid step. Could not find step config./i)).toBeInTheDocument();
  });
});
