import React from 'react';
import { render, screen } from 'test-utils';
import CustomerMarketInfoCard from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails/MarketingInfoCard';
import type { SSOCustomer } from 'merchant/views/MagicCheckout/SSODashboard/types';

describe('CustomerMarketInfoCard', () => {
  const mockCustomer: SSOCustomer = {
    customer_id: '123',
    name: 'John Doe',
    phone: '+1234567890',
    email: 'john@example.com',
    first_login: '2024-01-01',
    last_login: '2024-03-20',
    login_frequency: 5,
    utm_source: 'google',
    utm_medium: 'cpc',
    utm_campaign: 'summer_sale',
  };

  it('renders the card with correct title', () => {
    render(<CustomerMarketInfoCard customer={mockCustomer} />);
    expect(screen.getByText('Marketing Info')).toBeInTheDocument();
  });

  it('displays UTM source correctly', () => {
    render(<CustomerMarketInfoCard customer={mockCustomer} />);
    expect(screen.getByText('UTM Source: google')).toBeInTheDocument();
  });

  it('displays UTM medium correctly', () => {
    render(<CustomerMarketInfoCard customer={mockCustomer} />);
    expect(screen.getByText('UTM Medium: cpc')).toBeInTheDocument();
  });

  it('displays UTM campaign correctly', () => {
    render(<CustomerMarketInfoCard customer={mockCustomer} />);
    expect(screen.getByText('UTM Campaign: summer_sale')).toBeInTheDocument();
  });

  it('displays dash (-) when UTM values are not provided', () => {
    const mockCustomerWithoutUtm = {
      ...mockCustomer,
      utm_source: undefined,
      utm_medium: undefined,
      utm_campaign: undefined,
    };
    render(<CustomerMarketInfoCard customer={mockCustomerWithoutUtm} />);
    expect(screen.getByText('UTM Source: -')).toBeInTheDocument();
    expect(screen.getByText('UTM Medium: -')).toBeInTheDocument();
    expect(screen.getByText('UTM Campaign: -')).toBeInTheDocument();
  });

  it('renders the globe icon button', () => {
    render(<CustomerMarketInfoCard customer={mockCustomer} />);
    const button = screen.getByRole('button');
    expect(button).toBeInTheDocument();
  });
});
