import React from 'react';
import CongratsForm from 'newAuth/signup/components/PartnerSignup/components/CongratsForm';
import { render, screen, userEvent, waitFor } from 'test-utils';
import * as trackEvents from 'newAuth/trackEvents';
import { mockEmailOtpSendError } from './mocks/once-handlers';

const defaultProps = {
  contactEmail: '',
  setContactEmail: () => {},
  setStep: () => {},
  setEmailToken: () => {},
  showNotification: () => {},
  onboardAllAsResellerFlag: true,
};

describe('CongratsForm', () => {
  const renderApp = () => render(<CongratsForm {...defaultProps} />);
  test('should render congrats screen', () => {
    renderApp();
    expect(screen.getByText(/Enter email to get all notifications/i)).toBeInTheDocument();
    expect(screen.getByText(/Go to Dashboard/i)).toBeInTheDocument();
    expect(screen.getByText(/Refer popular domestic payment methods/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Please share your Email with us, so that we can send you all important communication./i,
      ),
    ).toBeInTheDocument();
  });

  test('should fire form validation error tracking event when error on email CTA', async () => {
    const trackWithSegmentSpy = jest.spyOn(trackEvents, 'trackWithSegment');
    mockEmailOtpSendError();
    renderApp();
    const emailInput = screen.getByLabelText('Your email (optional)');
    expect(emailInput).toBeVisible();
    await userEvent.type(emailInput, 'a@b.com');
    const emailSubmitButton = screen.getByRole('button', { name: 'Submit' });
    expect(emailSubmitButton).toBeVisible();
    await userEvent.click(emailSubmitButton);

    await waitFor(() => {
      expect(trackWithSegmentSpy).toHaveBeenCalledWith({
        objectName: 'Form Field Validation',
        actionName: 'Error',
        location: 'Congrats Screen',
        properties: {
          errorMessage: 'That email is already taken.',
          fieldLabel: 'Contact Email',
          funnelStage: 'L1',
        },
      });
    });
  });
});
