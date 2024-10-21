import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import OnboardingModal from 'merchant/views/Transactions/v2/UploadInvoices/components/OnboardingModal/OnboardingModal';
import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';
import {
  ONBOARDING_DATA,
  ONBOARDING_PARTNERS,
} from 'merchant/views/Transactions/v2/UploadInvoices/components/OnboardingModal/constants';

const mockClosePopup = jest.fn();
const mockOpenPopup = jest.fn();
const partner = ONBOARDING_PARTNERS.GST_PORTAL;

const renderComponent = (props) => {
  const contextValue = {
    openPopup: mockOpenPopup,
    closePopup: mockClosePopup,
  };

  return render(
    <PopupContext.Provider value={contextValue}>
      <OnboardingModal partner={partner} {...props} />
    </PopupContext.Provider>,
  );
};

describe('Tests for OnboardingModal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should renders modal with initial content', () => {
    renderComponent();
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Next/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Previous' })).not.toBeInTheDocument();
  });

  test('should render Stepper component with correct data', () => {
    renderComponent();

    const onboardingData = ONBOARDING_DATA[partner];
    expect(screen.getByAltText(onboardingData[0].image)).toBeInTheDocument();
  });

  test('should advance the step on clicking "Next" button', async () => {
    renderComponent();
    const nextButton = screen.getByRole('button', { name: /Next/i });

    await userEvent.click(nextButton);

    // Verify that the next step's content is shown
    const onboardingData = ONBOARDING_DATA[partner];
    expect(screen.getByAltText(onboardingData[1].image)).toBeInTheDocument();
  });

  test('should go back a step on clicking "Previous" button', async () => {
    renderComponent(); // Start at step 1
    const nextButton = screen.getByRole('button', { name: /Next/i });
    await userEvent.click(nextButton);

    const previousButton = screen.getByRole('button', { name: /Previous/i });
    await userEvent.click(previousButton);

    // Verify that the previous step's content is shown
    const onboardingData = ONBOARDING_DATA[partner];
    expect(screen.getByAltText(onboardingData[0].image)).toBeInTheDocument();
  });

  test('should trigger openPopup on clicking "Next" button', async () => {
    const onboardingData = ONBOARDING_DATA[partner];
    renderComponent(); // Render with the last step

    const nextButton = screen.getByRole('button', { name: /Next/i });
    for (let i = 0; i < onboardingData.length; i++) {
      // eslint-disable-next-line no-await-in-loop
      await userEvent.click(nextButton);
    }

    expect(mockOpenPopup).toHaveBeenCalledWith(MODAL_TYPES.LOGIN, {
      partner,
      status: undefined,
    });
  });

  test('should call closePopup on clicking dismiss button', async () => {
    renderComponent();

    // Simulate modal dismiss
    expect(screen.getByLabelText('Close')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Close'));

    expect(mockClosePopup).toHaveBeenCalled();
  });
});
