import React from 'react';
import PartnerSignup from 'newAuth/signup/components/PartnerSignup';
import { render, screen } from 'test-utils';

const defaultProps = {
  openModal: () => {},
  closeModal: () => {},
  showNotification: () => {},
};

describe('PartnerSignup', () => {
  const renderApp = () => render(<PartnerSignup {...defaultProps} />);

  test('should render signup start screen', () => {
    renderApp();
    expect(screen.getByText(/Already a user\?/i)).toBeInTheDocument();
    expect(screen.getByText(/Most of our partners earn more than/i)).toBeInTheDocument();
    expect(screen.getByText(/Sankalp Goel/i)).toBeInTheDocument();
    expect(screen.getByText(/Sign up as a Partner/i)).toBeInTheDocument();
    expect(screen.getByText(/privacy policy/i)).toBeInTheDocument();
  });
});
