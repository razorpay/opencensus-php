import React from 'react';
import { render, screen } from 'test-utils';
import LoginWidget from 'merchant/views/MagicCheckout/SSODashboard/components/LoginWidget';

const defaultProps = {
  isLoading: false,
  totalLogin: 100,
  newLogin: 50,
};

describe('LoginWidget', () => {
  it('renders both cards with correct titles', () => {
    render(<LoginWidget {...defaultProps} />);

    expect(screen.getByText('Total Logged-in Users')).toBeInTheDocument();
    expect(screen.getByText('New Account Creations')).toBeInTheDocument();
  });

  it('displays loading spinner when isLoading is true', () => {
    render(<LoginWidget isLoading={true} totalLogin={100} newLogin={50} />);

    const spinners = screen.getAllByLabelText('loading data');
    expect(spinners).toHaveLength(2);
  });

  it('displays correct values when not loading', () => {
    render(<LoginWidget {...defaultProps} />);

    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('50')).toBeInTheDocument();
  });

  it('renders with large numbers', () => {
    render(<LoginWidget isLoading={false} totalLogin={999999} newLogin={123456} />);

    expect(screen.getByText('999999')).toBeInTheDocument();
    expect(screen.getByText('123456')).toBeInTheDocument();
  });
});
