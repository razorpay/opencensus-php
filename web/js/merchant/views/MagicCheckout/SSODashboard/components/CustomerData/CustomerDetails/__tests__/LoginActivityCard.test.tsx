import React from 'react';
import { render, screen } from 'test-utils';
import CustomerLoginActivityCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/LoginActivityCard';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';

describe('CustomerLoginActivityCard', () => {
  const mockCustomer: SSOCustomer = {
    customer_id: '123',
    name: 'John Doe',
    phone: '+1234567890',
    email: 'john@example.com',
    first_login: '2024-03-20T10:00:00Z',
    last_login: '2024-03-21T15:30:00Z',
    login_frequency: 5,
  };

  it('renders the card with title', () => {
    render(<CustomerLoginActivityCard customer={mockCustomer} />);
    expect(screen.getByText('Login Activity')).toBeInTheDocument();
  });

  it('displays customer login activity data correctly', () => {
    render(<CustomerLoginActivityCard customer={mockCustomer} />);

    // Check if all list items are present
    expect(screen.getByText(/First Login:/)).toBeInTheDocument();
    expect(screen.getByText(/Last Login:/)).toBeInTheDocument();
    expect(screen.getByText(/Login Frequency:/)).toBeInTheDocument();

    // Check if the data is displayed correctly
    expect(screen.getByText(/5 times/)).toBeInTheDocument();
  });

  it('handles missing customer data gracefully', () => {
    render(<CustomerLoginActivityCard />);

    // Check if N/A is displayed for missing data
    expect(screen.getByText('First Login: N/A')).toBeInTheDocument();
    expect(screen.getByText('Last Login: N/A')).toBeInTheDocument();
    expect(screen.getByText('Login Frequency:')).toBeInTheDocument();
  });
});
