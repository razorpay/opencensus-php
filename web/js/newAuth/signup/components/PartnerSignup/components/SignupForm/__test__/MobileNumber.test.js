import React from 'react';

import MobileNumber from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/MobileNumber';
import { render, screen } from 'test-utils';
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
    expect(screen.getByText(/Sign up as Partners/i)).toBeInTheDocument();
    expect(screen.getByText(/Get Started/i)).toBeInTheDocument();
  });
});
