import React from 'react';
import { useQuery } from '@tanstack/react-query';

import App from '@apps/digital-bills/src/app/App';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { waitFor } from '@apps/digital-bills/src/services/test/test-utils';

jest.mock('react-chartjs-2', () => ({
  Doughnut: jest.fn(() => <span>Doughnut Chart</span>),
}));

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

const QUERY_RESPONSE = { loading: false, isError: false, data: null };

describe('App', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render Landing page if Merchant is not onboarded to BillMe', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      ...QUERY_RESPONSE,
      data: { merchantOnboardingStatus: 'NEW' },
    });
    const { getByText } = renderWithWrappers(<App />);
    await waitFor(() => {
      expect(
        getByText('Say goodbye to paper bills and unlock business growth.'),
      ).toBeInTheDocument();
    });
  });

  test('should render Waitlist page if Merchant is in onboarding process', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      ...QUERY_RESPONSE,
      data: { merchantOnboardingStatus: 'PENDING' },
    });
    const { getByText } = renderWithWrappers(<App />);
    await waitFor(() => {
      expect(getByText('What makes BillMe great?')).toBeInTheDocument();
    });
  });

  test('should render BillMe dashboard if Merchant is onboarded to BillMe', async () => {
    (useQuery as jest.Mock).mockReturnValue({
      ...QUERY_RESPONSE,
      data: { merchantOnboardingStatus: 'ACTIVATED' },
    });
    const { getByText } = renderWithWrappers(<App />);
    await waitFor(() => expect(getByText('Dashboard')).toBeInTheDocument());
  });

  test("should render Error screen if 'getMerchantOnboardingStatus' API fails", async () => {
    (useQuery as jest.Mock).mockReturnValue({
      ...QUERY_RESPONSE,
      isError: true,
      refetch: jest.fn(),
    });
    const { getByText } = renderWithWrappers(<App />);
    await waitFor(() =>
      expect(getByText('Error in fetching Merchant onboarding status')).toBeInTheDocument(),
    );
  });
});
