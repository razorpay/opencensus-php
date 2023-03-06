import React from 'react';

import EmailVerification from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/EmailVerification';
import { render, screen } from 'test-utils';
// TODO: detailed tests to be covered later, only basic ones added for now.

const defaultProps = {
  contactEmail: 'some email',
  setStep: () => {},
  setShowHeader: () => {},
};

describe('EmailVerification', () => {
  const renderApp = (props) => render(<EmailVerification {...defaultProps} {...props} />);
  test('should render email verification screen', () => {
    renderApp();
    expect(screen.getByText(/Verification Email Successfully Sent/i)).toBeInTheDocument();
    expect(screen.getByText(/some email/i)).toBeInTheDocument();
  });
});
