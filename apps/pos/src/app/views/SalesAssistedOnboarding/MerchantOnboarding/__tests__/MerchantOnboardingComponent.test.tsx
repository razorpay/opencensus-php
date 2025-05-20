import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import MerchantOnboardingComponent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

const renderApp = () => {
  return render(<MerchantOnboardingComponent />);
};

describe('MerchantOnboardingComponent', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const mockContext = getMockUseOnboardingContext({});
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();
  const getStepConfigStepSlug = jest.fn();
  const getComponentConfigFromStep = jest.fn();
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
    handlers: {
      ...mockContext.handlers,
      updateModularConfig,
      handleProceedToNextComponent,
      getStepConfigStepSlug,
      getComponentConfigFromStep,
    },
  });

  test('should return error msg when either stepConfig or componentConfig or component is absent', () => {
    renderApp();
    expect(
      screen.getByText('Invalid step/component. Could not find correspoding configurations.'),
    ).toBeInTheDocument();
  });

  test('should return error msg when modular fetch api fails', () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        isModularFetchError: true,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
        getStepConfigStepSlug: jest.fn().mockReturnValueOnce({
          slug: 'deviceDeployment',
          title: 'Device Deployment',
        }),
        getComponentConfigFromStep: jest.fn().mockReturnValueOnce({
          view: <div>Some Component</div>,
          isFullScreenLayout: false,
        }),
      },
    });

    renderApp();
    expect(screen.getByText('Something went wrong. Please try again.')).toBeInTheDocument();
  });

  test('should return header with title', () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        isModularFetchError: false,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
        getStepConfigStepSlug: jest.fn().mockReturnValueOnce({
          slug: 'deviceDeployment',
          title: 'Device Deployment',
        }),
        getComponentConfigFromStep: jest.fn().mockReturnValueOnce({
          view: <div>Some Component</div>,
          isFullScreenLayout: false,
        }),
      },
    });
    renderApp();
    expect(screen.getByText('DEVICE DEPLOYMENT')).toBeInTheDocument();
  });
});
