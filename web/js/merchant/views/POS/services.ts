import { PaginationParamsType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { getSubmerchantIdFromPath } from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/utils';

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
} from './types';

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

export const getOrderDetails = async (orderId: string | undefined): Promise<OrderDetailsItem> => {
  const { data } = await merchantFetch({
    url: `merchant/device/${orderId}/order`,
    method: 'get',
  });

  return data;
};

export const getProductPricingMap = (): Promise<
  ApiResponse<Record<'configs', ProductPricingMap>>
> => merchantFetch(`merchant/device_config`);

export const getSubmerchantProductPricingMap = (
  pathname: string,
): Promise<ApiResponse<Record<'configs', ProductPricingMap>>> => {
  const submerchantId = getSubmerchantIdFromPath(pathname);

  return merchantFetch(`submerchants/${submerchantId}/device_config`);
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

export const updateSalePoc = ({ id, pocCode }) =>
  merchantFetch({
    url: `merchant/device/${id}/order`,
    data: { sales_code: pocCode, device_order_id: id },
    method: 'patch',
  });

export const getLatestOrder = (): Promise<ApiResponse<OrderDetailsItem>> =>
  merchantFetch('merchant/device/order/latest');

type CreateActvationCaseResponse = {
  pos_activation_status: PosActivationStatusTypes;
  is_pos_details_submitted: boolean;
};

export const createActvationCase = (): Promise<ApiResponse<CreateActvationCaseResponse>> =>
  merchantFetch({
    url: 'merchant/activation',
    method: 'post',
    data: {
      is_pos_details_submitted: true,
    },
  });

export const getModularOnboardingData = async (
  merchantId: string,
): Promise<ApiResponse<WorkflowConfig> | null> => {
  const data = await merchantFetch({
    url: `onboarding/workflow/merchant/${merchantId}`,
    method: 'get',
    data: {
      merchant_id: merchantId,
    },
  });
  return data;
};

export const getPosPricingTemplate = async (payload = {}) => {
  const htmlTemplate = await merchantFetch({
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
  const response = await merchantFetch({
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
  merchantFetch({
    url: 'pg/onboarding/initiate_pos_onboarding',
    method: 'post',
  });
