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
  });
});
