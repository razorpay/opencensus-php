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
    expect(
      screen.getByText(/Most partners earn ₹ 7500 in commissions every month!/i),
    ).toBeInTheDocument();
    expect(screen.getByText(/Vikas Baruna/i)).toBeInTheDocument();
    expect(screen.getByText(/Sign up as Partners/i)).toBeInTheDocument();
    expect(screen.getByText(/privacy policy/i)).toBeInTheDocument();
  });
});
