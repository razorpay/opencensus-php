import { merchantFetch } from 'merchant/utils/ajax';

import {
  CommsOrderItem,
  ApiResponse,
  PincodeInfo,
  OrderDetailsItem,
  CreateOrderPayload,
  ProductPricingMap,
} from './types';

export const getOrderList = async (payload) => {
  const { data } = await merchantFetch({
    url: `merchant/device/order`,
    data: payload,
    method: 'get',
  });

  return data;
};

export const getOrderDetails = async (orderId): Promise<OrderDetailsItem> => {
  const { data } = await merchantFetch({
    url: `merchant/device/${orderId}/order`,
    method: 'get',
  });

  return data;
};

export const getProductPricingMap = async (): Promise<
  ApiResponse<Record<'configs', ProductPricingMap>>
> => await merchantFetch(`merchant/device_config`);

export interface DashboardListApiResponse<T> {
  count: number;
  entity: string;
  items: T;
}

type CreateOrderResp = {
  status_code: string;
  success: boolean;
  data: OrderDetailsItem;
  error?: string[];
};

export const createOrder = async (payload: CreateOrderPayload): Promise<CreateOrderResp> =>
  merchantFetch({
    url: `merchant/device/order`,
    data: payload,
    method: 'post',
  });

export const fetchLatestOrder = async (payload): Promise<ApiResponse<CommsOrderItem>> =>
  merchantFetch({
    data: payload,
    url: 'merchant/device/order/latest',
    method: 'get',
  });
type GetPincodeResponse = {
  status_code: number;
  success: boolean;
  data?: PincodeInfo;
  errors?: string[];
};

export const getPincodeInfo = (pincode: string | number): Promise<GetPincodeResponse> =>
  merchantFetch(`pincodes/${pincode}`);

export const updateSalePoc = async ({ id, pocCode }) =>
  await merchantFetch({
    url: `merchant/device/${id}/order`,
    data: { sales_code: pocCode, device_order_id: id },
    method: 'patch',
  });

export const getLatestOrder = (): Promise<ApiResponse<OrderDetailsItem>> =>
  merchantFetch('merchant/device/order/latest');
