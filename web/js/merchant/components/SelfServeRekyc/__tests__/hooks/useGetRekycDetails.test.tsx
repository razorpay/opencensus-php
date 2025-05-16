import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useGetRekycDetails } from '../../hooks/useGetRekycDetails';
import { merchantFetch } from '@dashboards/payments/utils/merchantFetch';
import { getMode } from '@federated/apps/shell/commonStore';

jest.mock('@dashboards/payments/utils/merchantFetch');
jest.mock('@federated/apps/shell/commonStore', () => ({
  getMode: jest.fn().mockReturnValue('live')
}));

const TestComponent = ({ merchantId }: { merchantId: string }) => {
  const { data, isLoading, isError } = useGetRekycDetails({
    merchantId,
    showCurrentData: true
  });

  if (isLoading) return <div data-testid="loading">Loading...</div>;
  if (isError) return <div data-testid="error">Error occurred</div>;
  if (!data) return <div data-testid="no-data">No data</div>;

  return (
    <div data-testid="rekyc-data">
      {JSON.stringify(data)}
    </div>
  );
};

describe('useGetRekycDetails', () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        refetchOnWindowFocus: false,
        cacheTime: 0
      },
    },
  });

  const renderWithQuery = (merchantId: string = 'test-merchant') => {
    return render(
      <QueryClientProvider client={queryClient}>
        <TestComponent merchantId={merchantId} />
      </QueryClientProvider>
    );
  };

  beforeEach(() => {
    queryClient.clear();
    jest.clearAllMocks();
  });

  it('should fetch rekyc details successfully', async () => {
    const mockResponse = {
      success: true,
      data: [{
        status: 'needs_clarification',
        deadline: Date.now() + 7 * 24 * 60 * 60 * 1000,
        kyc_type: 'rekyc',
        merchant_id: 'test-merchant'
      }]
    };

    (merchantFetch as jest.Mock).mockResolvedValueOnce(mockResponse);

    renderWithQuery();

    expect(screen.getByTestId('loading')).toBeInTheDocument();

    await waitFor(() => {
      expect(merchantFetch).toHaveBeenCalledWith({
        method: 'POST',
        absUrl: '/mes/rzp.merchant_experience_service.kyc_details.v1.KycDetailsService/GetKycDetails',
        headers: {
          'X-Razorpay-Mode': 'live'
        },
        data: {
          kyc_type: 'rekyc',
          merchant_id: 'test-merchant',
          is_current: true
        }
      });
    });

    await waitFor(() => {
      expect(screen.getByTestId('rekyc-data')).toBeInTheDocument();
    });
  });
});