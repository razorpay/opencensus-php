import { merchantFetch } from 'merchant/utils/ajax';
import { PaginationParamsType } from 'common/typings';
import { ApiResponse, ProductPricingMap } from './types';
import { getSubmerchantIdFromPath } from './utils';

export const getProductPricingMap = (): Promise<
  ApiResponse<Record<'configs', ProductPricingMap>>
> => merchantFetch(`merchant/device_config`);

export const getSubmerchantProductPricingMap = (
  pathname: string,
): Promise<ApiResponse<Record<'configs', ProductPricingMap>>> => {
  const submerchantId = getSubmerchantIdFromPath(pathname);

  return merchantFetch(`submerchants/${submerchantId}/device_config`);
};

export const getOrderList = async (payload: PaginationParamsType) => {
  const { data } = await merchantFetch({
    url: `merchant/device/order`,
    data: payload,
    method: 'get',
  });

  return data;
};

export const getSubmerchantOrderList = async (payload: PaginationParamsType, pathname: string) => {
  const submerchantId = getSubmerchantIdFromPath(pathname);
  const { data } = await merchantFetch({
    url: `submerchants/${submerchantId}/device/order`,
    data: payload,
    method: 'get',
  });

  return data;
};
