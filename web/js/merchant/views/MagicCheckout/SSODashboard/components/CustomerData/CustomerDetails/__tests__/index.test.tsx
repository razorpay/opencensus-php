import React from 'react';
import moment from 'moment';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import CustomerDetails from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerDetails';
import { fetchCustomerDetails } from 'merchant/views/MagicCheckout/SSODashboard/api';
import { showNotification } from 'merchant_common/reducers/notifications';

// Mock the dependencies
jest.mock('merchant/views/MagicCheckout/SSODashboard/api');
jest.mock('merchant_common/reducers/notifications');

const mockCustomer = {
  customer_id: 'cust_123',
  name: 'John Doe',
  email: 'john@example.com',
  phone: '+1234567890',
  first_login: '2024-01-01',
  last_login: '2024-03-20',
  login_frequency: 5,
  utm_source: 'google',
  utm_medium: 'cpc',
  utm_campaign: 'summer_sale',
};

const mockTimeRange = {
  start: moment('2024-01-01'), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  end: moment('2024-01-31'), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
};

const mockLoginData = {
  customer_all_logins: [],
};

describe('CustomerDetails', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (fetchCustomerDetails as jest.Mock).mockResolvedValue({
      data: {
        customer_all_logins: mockLoginData,
      },
    });
  });

  it('renders the customer name as a link', () => {
    render(<CustomerDetails value="John Doe" customer={mockCustomer} timeRange={mockTimeRange} />);

    expect(screen.getByText('John Doe')).toBeInTheDocument();
  });

  it('opens modal when link is clicked', () => {
    render(<CustomerDetails value="John Doe" customer={mockCustomer} timeRange={mockTimeRange} />);

    fireEvent.click(screen.getByText('John Doe'));
    expect(screen.getByText('Contact Details')).toBeInTheDocument();
  });

  it('fetches customer data when modal is opened', async () => {
    render(<CustomerDetails value="John Doe" customer={mockCustomer} timeRange={mockTimeRange} />);

    fireEvent.click(screen.getByText('John Doe'));

    await waitFor(() => {
      expect(fetchCustomerDetails).toHaveBeenCalledWith({
        customer_id: 'cust_123',
        from: mockTimeRange.start.valueOf(),
        to: mockTimeRange.end.valueOf(),
      });
    });
  });

  it('shows error notification when data fetch fails', async () => {
    const errorMessage = 'Failed to fetch data';
    (fetchCustomerDetails as jest.Mock).mockRejectedValue({
      errors: [errorMessage],
    });

    render(<CustomerDetails value="John Doe" customer={mockCustomer} timeRange={mockTimeRange} />);

    fireEvent.click(screen.getByText('John Doe'));

    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: errorMessage,
      });
    });
  });

  it('closes modal when dismiss is clicked', () => {
    render(<CustomerDetails value="John Doe" customer={mockCustomer} timeRange={mockTimeRange} />);

    // Open modal
    fireEvent.click(screen.getByText('John Doe'));
    expect(screen.getByText('Contact Details')).toBeInTheDocument();

    // Close modal
    const closeButton = screen.getByRole('button', { name: /close/i });
    fireEvent.click(closeButton);

    // Modal should be closed
    expect(screen.queryByText('Contact Details')).not.toBeInTheDocument();
  });
});
