import React from 'react';
import { renderHook } from '@testing-library/react-hooks';
import { waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { merchantFetch } from 'merchant/utils/ajax';
import { useLatestOrder, getLatestOrder } from '../useLatestOrder';
import { CHECKOUT_ERRORS } from '../../constants';
import { OrderDetailsItem, ApiResponse } from '../../types/hook';

// Mock the merchantFetch function
jest.mock('merchant/utils/ajax', () => ({
  merchantFetch: jest.fn(),
}));

const mockMerchantFetch = merchantFetch as jest.MockedFunction<typeof merchantFetch>;

// Create a wrapper component with QueryClient
const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        refetchOnWindowFocus: false,
        cacheTime: 0,
      },
    },
  });

  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
};

describe('useLatestOrder', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getLatestOrder', () => {
    it('should call merchantFetch with correct endpoint', async () => {
      const mockResponse: ApiResponse<OrderDetailsItem> = {
        status_code: 200,
        success: true,
        data: {
          id: 'order_123',
          order_id: 'order_123',
          merchant_id: 'merchant_123',
          created_at: 1640995200000,
          arriving_at: 1641081600000,
          delivered_at: 0,
          rejected_at: 0,
          rejection_reasons: null,
          status: 'paid',
          delivery_address: {
            name: 'John Doe',
            address: '123 Main St',
            city: 'Mumbai',
            country: 'India',
            state: 'Maharashtra',
            pin_code: '400001',
            phone_no: '+919876543210',
          },
          items: [
            {
              code: 'POS_DEVICE',
              count: 1,
              period: 'monthly',
            },
          ],
          amount: {
            base: 1000,
            gst: 180,
            total: 1180,
          },
          rental_amount: null,
          order: {
            id: 'order_123',
            amount: 1180,
            amount_paid: 1180,
            amount_due: 0,
            currency: 'INR',
            status: 'paid',
            created_at: 1640995200000,
          },
          sales_code: 'SC123',
          payment: {
            amount: 1180,
            status: 'captured',
            refund_status: null,
            captured: true,
            created_at: 1640995200000,
          },
          refund: null,
        },
      };

      mockMerchantFetch.mockResolvedValueOnce(mockResponse);

      const result = await getLatestOrder();

      expect(mockMerchantFetch).toHaveBeenCalledWith('merchant/device/order/latest');
      expect(result).toEqual(mockResponse);
    });
  });

  describe('useLatestOrder hook', () => {
    it('should return loading state initially', () => {
      mockMerchantFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      expect(result.current.isLatestOrderLoading).toBe(true);
      expect(result.current.latestOrder).toBeUndefined();
      expect(result.current.latestOrderFetchError).toBeNull();
      expect(typeof result.current.refetchLatestOrder).toBe('function');
    });

    it('should return order data on successful fetch', async () => {
      const mockOrderData: OrderDetailsItem = {
        id: 'order_123',
        order_id: 'order_123',
        merchant_id: 'merchant_123',
        created_at: 1640995200000,
        arriving_at: 1641081600000,
        delivered_at: 0,
        rejected_at: 0,
        rejection_reasons: null,
        status: 'paid',
        delivery_address: {
          name: 'John Doe',
          address: '123 Main St',
          city: 'Mumbai',
          country: 'India',
          state: 'Maharashtra',
          pin_code: '400001',
          phone_no: '+919876543210',
        },
        items: [
          {
            code: 'POS_DEVICE',
            count: 1,
            period: 'monthly',
          },
        ],
        amount: {
          base: 1000,
          gst: 180,
          total: 1180,
        },
        rental_amount: null,
        order: {
          id: 'order_123',
          amount: 1180,
          amount_paid: 1180,
          amount_due: 0,
          currency: 'INR',
          status: 'paid',
          created_at: 1640995200000,
        },
        sales_code: 'SC123',
        payment: {
          amount: 1180,
          status: 'captured',
          refund_status: null,
          captured: true,
          created_at: 1640995200000,
        },
        refund: null,
      };

      const mockResponse: ApiResponse<OrderDetailsItem> = {
        status_code: 200,
        success: true,
        data: mockOrderData,
      };

      mockMerchantFetch.mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toEqual(mockOrderData);
      expect(result.current.latestOrderFetchError).toBeNull();
      expect(mockMerchantFetch).toHaveBeenCalledWith('merchant/device/order/latest');
    });

    it('should return error state when fetch fails', async () => {
      mockMerchantFetch.mockRejectedValueOnce(new Error('Network error'));

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toBeUndefined();
      expect(result.current.latestOrderFetchError).toEqual(CHECKOUT_ERRORS['ORDER_CREATE_FAILED']);
    });

    it('should handle null response data', async () => {
      const mockResponse: ApiResponse<OrderDetailsItem> = {
        status_code: 200,
        success: true,
        data: undefined,
      };

      mockMerchantFetch.mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toBeUndefined();
      expect(result.current.latestOrderFetchError).toBeNull();
    });

    it('should handle empty response', async () => {
      mockMerchantFetch.mockResolvedValueOnce(null);

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toBeUndefined();
      expect(result.current.latestOrderFetchError).toBeNull();
    });

    it('should call refetchLatestOrder function', async () => {
      const mockOrderData: OrderDetailsItem = {
        id: 'order_456',
        order_id: 'order_456',
        merchant_id: 'merchant_123',
        created_at: 1640995200000,
        arriving_at: 1641081600000,
        delivered_at: 1641168000000,
        rejected_at: 0,
        rejection_reasons: null,
        status: 'delivered',
        delivery_address: {
          name: 'Jane Doe',
          address: '456 Oak St',
          city: 'Delhi',
          country: 'India',
          state: 'Delhi',
          pin_code: '110001',
          phone_no: '+919876543211',
        },
        items: [
          {
            code: 'POS_DEVICE',
            count: 2,
            period: 'monthly',
          },
        ],
        amount: {
          base: 2000,
          gst: 360,
          total: 2360,
        },
        rental_amount: {
          base: 500,
          gst: 90,
          total: 590,
        },
        order: {
          id: 'order_456',
          amount: 2360,
          amount_paid: 2360,
          amount_due: 0,
          currency: 'INR',
          status: 'paid',
          created_at: 1640995200000,
        },
        sales_code: 'SC456',
        payment: {
          amount: 2360,
          status: 'captured',
          refund_status: null,
          captured: true,
          created_at: 1640995200000,
        },
        refund: null,
      };

      const mockResponse: ApiResponse<OrderDetailsItem> = {
        status_code: 200,
        success: true,
        data: mockOrderData,
      };

      // First call returns empty, second call returns data
      mockMerchantFetch.mockResolvedValueOnce(null).mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      // Wait for initial load
      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toBeUndefined();

      // Call refetch
      result.current.refetchLatestOrder();

      // Wait for refetch to complete
      await waitFor(() => {
        expect(result.current.latestOrder).toEqual(mockOrderData);
      });

      expect(mockMerchantFetch).toHaveBeenCalledTimes(2);
    });

    it('should use correct query configuration', () => {
      mockMerchantFetch.mockImplementation(() => new Promise(() => {}));

      renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      // Verify that the hook was called with correct query key
      expect(mockMerchantFetch).toHaveBeenCalledWith('merchant/device/order/latest');
    });

    it('should handle different order statuses', async () => {
      const rejectedOrderData: OrderDetailsItem = {
        id: 'order_789',
        order_id: 'order_789',
        merchant_id: 'merchant_123',
        created_at: 1640995200000,
        arriving_at: 0,
        delivered_at: 0,
        rejected_at: 1641081600000,
        rejection_reasons: {
          error_code: 'INVALID_ADDRESS',
          error_description: 'Address not serviceable',
          error_reason: 'Location not in delivery zone',
        },
        status: 'rejected',
        delivery_address: {
          name: 'Bob Smith',
          address: '789 Pine St',
          city: 'Bangalore',
          country: 'India',
          state: 'Karnataka',
          pin_code: '560001',
          phone_no: '+919876543212',
        },
        items: [
          {
            code: 'POS_DEVICE',
            count: 1,
            period: 'monthly',
          },
        ],
        amount: {
          base: 1000,
          gst: 180,
          total: 1180,
        },
        rental_amount: null,
        order: null,
        sales_code: 'SC789',
        payment: null,
        refund: null,
      };

      const mockResponse: ApiResponse<OrderDetailsItem> = {
        status_code: 200,
        success: true,
        data: rejectedOrderData,
      };

      mockMerchantFetch.mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useLatestOrder(), {
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(result.current.isLatestOrderLoading).toBe(false);
      });

      expect(result.current.latestOrder).toEqual(rejectedOrderData);
      expect(result.current.latestOrder?.status).toBe('rejected');
      expect(result.current.latestOrder?.rejection_reasons).toBeDefined();
      expect(result.current.latestOrderFetchError).toBeNull();
    });
  });
});
