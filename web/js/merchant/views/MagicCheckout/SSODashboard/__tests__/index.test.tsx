import React from 'react';
import { render, screen, waitFor, act } from 'test-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import SSODashboard from 'merchant/views/MagicCheckout/SSODashboard';
import { fetchSSOLoginGraphData } from 'merchant/views/MagicCheckout/SSODashboard/api';

// Mock the API call
jest.mock('merchant/views/MagicCheckout/SSODashboard/api', () => ({
  fetchSSOLoginGraphData: jest.fn(),
}));

// Mock the notification action
jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn(),
}));

describe('SSODashboard', () => {
  const mockLoginData = {
    total_logged_in_users: 304,
    new_account_creations: 69,
    aggregate: 'daily',
    metrics: {
      timestamps: [1709510400, 1712102400],
      values: [
        {
          label: 'Total Logins',
          values: [12, 13],
        },
        {
          label: 'New Users',
          values: [4, 2],
        },
      ],
    },
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (fetchSSOLoginGraphData as jest.Mock).mockResolvedValue({ data: mockLoginData });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const renderComponent = async () => {
    return act(async () => {
      render(<SSODashboard />);
    });
  };

  it('renders the dashboard with correct title and description', async () => {
    await renderComponent();

    expect(screen.getByText('Login with Razorpay Analytics')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Track every sign-in, spot growth trends, and export user cohorts, all from one place.',
      ),
    ).toBeInTheDocument();
  });

  it('fetches and displays login data on initial render', async () => {
    await renderComponent();

    await waitFor(() => {
      expect(screen.getByText('304')).toBeInTheDocument(); // totalLoggedIn
      expect(screen.getByText('69')).toBeInTheDocument(); // newLogin
    });
  });

  it('handles API error gracefully', async () => {
    const errorMessage = 'API Error';
    (fetchSSOLoginGraphData as jest.Mock).mockRejectedValue({
      errors: [errorMessage],
    });

    await renderComponent();

    await waitFor(() => {
      expect(screen.getByText('Login with Razorpay Analytics')).toBeInTheDocument();
    });

    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: errorMessage,
    });
  });
});
