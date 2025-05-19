import React from 'react';
import { render, screen } from 'test-utils';
import CustomerInfoCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/InfoCard';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';

describe('CustomerInfoCard', () => {
  const mockCustomer: SSOCustomer = {
    name: 'John Doe',
    phone: '+1234567890',
    email: 'john@example.com',
    customer_id: '123',
    first_login: '2024-01-01',
    last_login: '2024-03-20',
    login_frequency: 5,
  };

  it('renders customer information correctly', () => {
    render(<CustomerInfoCard customer={mockCustomer} />);

    // Check if the header is present
    expect(screen.getByText('Customer Info')).toBeInTheDocument();

    // Check if all customer details are rendered
    expect(screen.getByText('John Doe')).toBeInTheDocument();
    expect(screen.getByText('+1234567890')).toBeInTheDocument();
    expect(screen.getByText('john@example.com')).toBeInTheDocument();
  });

  it('renders fallback dash (-) when customer data is missing', () => {
    render(<CustomerInfoCard customer={undefined} />);

    // Check if all fallback dashes are rendered
    const dashes = screen.getAllByText('-');
    expect(dashes).toHaveLength(3);
  });

  it('renders copy button in the header', () => {
    render(<CustomerInfoCard customer={mockCustomer} />);

    // Check if the copy button is present
    const copyButton = screen.getByRole('button');
    expect(copyButton).toBeInTheDocument();
  });
});
