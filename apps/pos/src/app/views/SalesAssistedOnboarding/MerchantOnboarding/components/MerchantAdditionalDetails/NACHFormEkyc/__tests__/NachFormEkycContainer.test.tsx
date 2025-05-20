import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import NACHFormEkycContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/NACHFormEkyc/NACHFormEkycContainer';
import { getMockUseOnboardingContext } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/MerchantAdditionalDetails/mocks/fixtures';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
);

describe('NACHFormEkycContainer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const mockContext = getMockUseOnboardingContext();
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
    handlers: {
      ...mockContext.handlers,
      updateModularConfig,
      handleProceedToNextComponent,
    },
  });

  test('renders the NACH form', () => {
    render(<NACHFormEkycContainer />);
    expect(screen.getByText(/Upload NACH Form/i)).toBeInTheDocument();
  });

  test('handles text area changes', async () => {
    render(<NACHFormEkycContainer />);
    const textarea = screen.getByRole('textbox');
    await userEvent.type(textarea, 'Test comment');
    expect(textarea).toHaveValue('Test comment');
  });

  test('handles skip button click', async () => {
    render(<NACHFormEkycContainer />);
    const skipButton = screen.getByRole('button', { name: /skip/i });
    await userEvent.click(skipButton);
    expect(handleProceedToNextComponent).toHaveBeenCalled();
  });

  test.each(['ACTIVATED', 'REJECTED', 'KYC_QUALIFIED'])(
    'disables submit button when posActivationStatus is %s',
    (status) => {
      (useOnboardingContext as jest.Mock).mockReturnValue({
        ...mockContext,
        states: {
          ...mockContext.states,
          merchantDetails: {
            ...mockContext.states.merchantDetails,
            activation: {
              posActivationStatus: status,
            },
          },
        },
        handlers: {
          ...mockContext.handlers,
          updateModularConfig,
          handleProceedToNextComponent,
        },
      });
      render(<NACHFormEkycContainer />);
      const submitButton = screen.getByRole('button', { name: /Save & Continue/i });
      expect(submitButton).toBeDisabled();
    },
  );
});
