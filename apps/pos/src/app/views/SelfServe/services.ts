import { getSubmerchantIdFromPath } from 'apps/pos/src/app/views/SelfServe/utils/path';
import { posFetch } from 'apps/pos/src/app/utils/posFetch';

import {
  CommsOrderItem,
  ApiResponse,
  PincodeInfo,
  OrderDetailsItem,
  CreateOrderPayload,
  ProductPricingMap,
  PosActivationStatusTypes,
  WorkflowConfig,
  PosAgreementSignPayload,
  PosAgreementSignIds,
  PaginationParamsType,
} from './types';

export const getOrderList = async (payload: PaginationParamsType) => {
  const { data } = await posFetch({
    url: `merchant/device/order`,
    data: payload,
    method: 'get',
  });

  return data;
};

export const getSubmerchantOrderList = async (payload: PaginationParamsType, pathname: string) => {
  const submerchantId = getSubmerchantIdFromPath(pathname);
  const { data } = await posFetch({
    url: `submerchants/${submerchantId}/device/order`,
    data: payload,
    method: 'get',
  });

  return data;
};

export const getOrderDetails = async (orderId: string | undefined): Promise<OrderDetailsItem> => {
  const { data } = await posFetch({
    url: `merchant/device/${orderId}/order`,
    method: 'get',
  });

  return data;
};

export const getProductPricingMap = (): Promise<
  ApiResponse<Record<'configs', ProductPricingMap>>
> => posFetch(`merchant/device_config`);

export const getSubmerchantProductPricingMap = (
  pathname: string,
): Promise<ApiResponse<Record<'configs', ProductPricingMap>>> => {
  const submerchantId = getSubmerchantIdFromPath(pathname);

  return posFetch(`submerchants/${submerchantId}/device_config`);
};
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
  posFetch({
    url: `merchant/device/order`,
    data: payload,
    method: 'post',
  });

export const fetchLatestOrder = async (payload): Promise<ApiResponse<CommsOrderItem>> =>
  posFetch({
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
  posFetch(`pincodes/${pincode}`);

export const updateSalePoc = ({ id, pocCode }) =>
  posFetch({
    url: `merchant/device/${id}/order`,
    data: { sales_code: pocCode, device_order_id: id },
    method: 'patch',
  });

export const getLatestOrder = (): Promise<ApiResponse<OrderDetailsItem>> =>
  posFetch('merchant/device/order/latest');

type CreateActvationCaseResponse = {
  pos_activation_status: PosActivationStatusTypes;
  is_pos_details_submitted: boolean;
};

export const createActvationCase = (): Promise<ApiResponse<CreateActvationCaseResponse>> =>
  posFetch({
    url: 'merchant/activation',
    method: 'post',
    data: {
      is_pos_details_submitted: true,
    },
  });

export const getModularOnboardingData = async (
  merchantId: string,
): Promise<ApiResponse<WorkflowConfig> | null> => {
  const data = await posFetch({
    url: `onboarding/workflow/merchant/${merchantId}`,
    method: 'get',
    data: {
      merchant_id: merchantId,
    },
  });
  return data;
};

export const getPosPricingTemplate = async (payload = {}) => {
  const htmlTemplate = await posFetch({
    url: `templating/template_configs/render`,
    method: 'post',
    data: {
      namespace: 'payments',
      name: 'POS_pricing_agreement',
      orgId: '',
      merchantId: '',
      channel: 'email',
      placeholderData: payload,
    },
  });
  return htmlTemplate;
};

export const agreeToPosMerchantAgreement = async ({
  merchantId,
  tncId,
  pricingId,
  privacyId,
  agreement_consented_at_field,
}: PosAgreementSignIds) => {
  const payload: PosAgreementSignPayload = {
    terms_and_conditions_consent_field: {
      type: 'Terms of Service',
      templateId: tncId,
    },
    privacy_consent_field: {
      type: 'Privacy Policy',
      templateId: privacyId,
    },
    agreement_consented_at_field,
  };
  if (pricingId) {
    payload.pricing_consent_field = {
      type: 'Pricing Agreement',
      templateId: pricingId,
    };
  }
  const response = await posFetch({
    url: `onboarding/workflow/merchant/${merchantId}`,
    method: 'post',
    data: {
      merchant_id: merchantId,
      field_data: payload,
    },
  });
  return response;
};

interface PosOnboardingInitiateResponse {
  status_code: number;
  success: boolean;
  data: {
    workflow_id: string;
    downstream_status_code: number;
  };
}

export const initiatePosOnboarding = async (): Promise<PosOnboardingInitiateResponse> =>
  posFetch({
    url: 'pg/onboarding/initiate_pos_onboarding',
    method: 'post',
  });
