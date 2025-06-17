import { useQuery } from '@tanstack/react-query';

import { CHECKOUT_ERRORS } from '../constants';
import {
  OrderDetailsItem,
  ApiResponse,
  UseLatestOrder,
  CheckoutValidationError,
} from '../types/hook';
import { merchantFetch } from 'merchant/utils/ajax';

export const getLatestOrder = (): Promise<ApiResponse<OrderDetailsItem>> =>
  merchantFetch('merchant/device/order/latest');

export const useLatestOrder = (): UseLatestOrder => {
  const {
    data: latestOrderFetched,
    isLoading: isLatestOrderLoading,
    isError,
    refetch: refetchLatestOrder,
  } = useQuery(['pos', 'latest-order'], {
    queryFn: getLatestOrder,
    retry: false,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
  });

  const latestOrder = latestOrderFetched ?? null;

  return {
    latestOrder: latestOrder?.data,
    isLatestOrderLoading,
    latestOrderFetchError: !!isError
      ? (CHECKOUT_ERRORS as { ORDER_CREATE_FAILED: CheckoutValidationError }).ORDER_CREATE_FAILED
      : null,
    refetchLatestOrder,
  };
};
