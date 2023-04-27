import React from 'react';
import CongratsForm from 'newAuth/signup/components/PartnerSignup/components/CongratsForm';
import { render, screen } from 'test-utils';

const defaultProps = {
  contactEmail: 'some email',
  setContactEmail: () => {},
  setStep: () => {},
  showNotification: () => {},
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
});
