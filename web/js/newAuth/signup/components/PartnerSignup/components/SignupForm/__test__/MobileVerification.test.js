import React from 'react';
import MobileVerification from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/MobileVerification';
import { STEPS } from 'newAuth/signup/Constants';
import { render, screen, userEvent, waitFor } from 'test-utils';
import * as trackWithSegment from 'newAuth/trackEvents';

// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  mobileNumber: '8888888888',
  otpVerifyToken: 'verify token',
  setOtpVerifyToken: () => {},
  isSendWhatsapp: true,
  setShowHeader: () => {},
  setStep: () => {},
  setMerchantID: () => {},
};

describe('MobileVerification', () => {
  const renderApp = (props) => render(<MobileVerification {...defaultProps} {...props} />);

  test('should render mobile verification screen', async () => {
    const setStep = jest.fn();
    renderApp({ setStep });
    expect(screen.getByText(/OTP Successfully Sent/i)).toBeInTheDocument();

    await userEvent.click(screen.getByText('Change'));
    expect(setStep).toHaveBeenCalledWith(STEPS.MOBILE_NUMBER);
  });

  test('should verify otp api correctly', async () => {
    const setStep = jest.fn();
    const setMerchantID = jest.fn();
    const trackWithSegmentMock = jest.spyOn(trackWithSegment, 'trackWithSegment');
    renderApp({ setStep, setMerchantID });
    await userEvent.type(screen.getByPlaceholderText('••••••'), '000007');
    await userEvent.click(screen.getByRole('button', { name: 'Verify' }));

    await waitFor(() => {
      expect(trackWithSegmentMock).toHaveBeenCalledWith({
        objectName: 'Sign up Create Account',
        actionName: 'Result',
        location: 'Mobile Number OTP Verify',
        properties: {
          status: true,
          mid: '123',
        },
      });
      expect(setStep).toHaveBeenCalled();
    });
  });
});
