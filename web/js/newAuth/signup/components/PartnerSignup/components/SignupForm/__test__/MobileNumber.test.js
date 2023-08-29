import React from 'react';

import { STEPS } from 'newAuth/signup/Constants';
import { mockUserRegisterOtpError } from 'newAuth/signup/components/PartnerSignup/__test__/mocks/once-handlers';
import MobileNumber from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/MobileNumber';
import * as trackWithSegment from 'newAuth/trackEvents';
import { render, screen, userEvent, waitFor } from 'test-utils';

// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  setMobileNumber: () => {},
  isSendWhatsapp: false,
  setStep: () => {},
  setIsSendWhatsapp: () => {},
  setOtpVerifyToken: () => {},
  openModal: () => {},
  closeModal: () => {},
  showNotification: () => {},
};

describe('MobileNumber', () => {
  const renderApp = (props) => render(<MobileNumber {...defaultProps} {...props} />);

  test('should render mobile number screen', () => {
    renderApp();
    expect(screen.getByText(/Sign up as a Partner/i)).toBeInTheDocument();
    expect(screen.getByText(/Get Started/i)).toBeInTheDocument();
  });

  test('should call register otp api correctly', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    await userEvent.type(screen.getByPlaceholderText('Enter mobile number'), '8888888888');
    await userEvent.click(screen.getByText(/Get Started/i));
    await waitFor(() => {
      expect(setStep).toHaveBeenCalled();
      expect(setStep).toHaveBeenCalledWith(expect.any(Function));
    });

    const setStepFunction = setStep.mock.calls[0][0];
    await waitFor(() => {
      // 3 is basically the verify mobile number screen
      expect(setStepFunction(STEPS.MOBILE_NUMBER)).toBe(STEPS.MOBILE_VERIFICATION);
    });
  });

  test('should call form validation error on register otp api error', async () => {
    mockUserRegisterOtpError();
    const trackWithSegmentMock = jest.spyOn(trackWithSegment, 'trackWithSegment');
    renderApp();
    await userEvent.type(screen.getByPlaceholderText('Enter mobile number'), '8888888888');
    await userEvent.click(screen.getByText(/Get Started/i));
    await waitFor(() => {
      expect(trackWithSegmentMock).toHaveBeenCalledWith({
        objectName: 'Form Field Validation',
        actionName: 'Error',
        location: 'Mobile Number',
        properties: {
          errorMessage: 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
          fieldLabel: 'Phone Number',
          funnelStage: 'L1',
        },
      });
    });
  });

  test('should resend otp api correctly', async () => {
    const setStep = jest.fn();
    const setMerchantID = jest.fn();
    const trackWithSegmentMock = jest.spyOn(trackWithSegment, 'trackWithSegment');
    renderApp({ setStep, setMerchantID });

    await waitFor(() => {
      expect(trackWithSegmentMock).toHaveBeenCalledWith({
        objectName: 'Signup',
        actionName: 'Displayed',
        location: 'Mobile Number',
      });
    });
  });
});
