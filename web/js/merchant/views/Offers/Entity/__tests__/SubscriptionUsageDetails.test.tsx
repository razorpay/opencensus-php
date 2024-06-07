import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { fetchSubscriptionOffersUsage } from 'merchant/reducers/offers/offerDetails';

import SubscriptionUsageDetails from '../SubscriptionUsageDetails';

jest.mock('merchant/reducers/offers/offerDetails', () => ({
  fetchSubscriptionOffersUsage: jest.fn(),
}));

jest.mock('common/ui/PlaceholderLoader', () => () => <div>Loading...</div>);
jest.mock('common/ui/Amount', () => ({ value }) => <div>{value}</div>);

describe('SubscriptionUsageDetails Component', () => {
  const mockData = {
    data: {
      offer_usage: 5,
      active_on: 10,
      total_discount: 1000,
    },
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders loading state correctly', async () => {
    fetchSubscriptionOffersUsage.mockReturnValueOnce(new Promise(() => {}));

    render(<SubscriptionUsageDetails id="1" />);
    await waitFor(() => {
      expect(screen.getAllByText('Loading...').length).toBe(3);
    });
  });

  test('renders data correctly after successful fetch', async () => {
    fetchSubscriptionOffersUsage.mockResolvedValueOnce(mockData);

    render(<SubscriptionUsageDetails id="1" />);

    await waitFor(() => expect(fetchSubscriptionOffersUsage).toHaveBeenCalledWith('1'));

    expect(screen.getByText('Offer Usage')).toBeInTheDocument();
    expect(screen.getByText('5')).toBeInTheDocument();
    expect(screen.getByText('Active On')).toBeInTheDocument();
    expect(screen.getByText('10')).toBeInTheDocument();
    expect(screen.getByText('Total Discounts Applied')).toBeInTheDocument();
    expect(screen.getByText('1000')).toBeInTheDocument();
  });

  test('renders error state correctly', async () => {
    fetchSubscriptionOffersUsage.mockRejectedValueOnce(new Error('Error fetching data'));

    render(<SubscriptionUsageDetails id="1" />);

    await waitFor(() => expect(fetchSubscriptionOffersUsage).toHaveBeenCalledWith('1'));

    expect(
      screen.getByText(/Oops, looks like an unexpected error occured for offer overview/i),
    ).toBeInTheDocument();
  });
});
