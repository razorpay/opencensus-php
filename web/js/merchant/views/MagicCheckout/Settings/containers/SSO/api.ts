import { merchantFetch } from 'merchant/utils/ajax';
import {
  SSOConfigsPayloadType,
  SSOResponsePayload,
  updateThemeResponseType,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';

export const fetchSSOStatus = (): Promise<SSOResponsePayload> => {
  return merchantFetch({
    url: 'merchants/configs',
    method: 'get',
  });
};

export const saveSSOSettings = (payload: SSOConfigsPayloadType): Promise<SSOResponsePayload> => {
  if (!payload.merchant_id) {
    Promise.reject('Merchant ID is required');
  }
  return merchantFetch({
    url: 'magic/merchant/configs',
    method: 'post',
    data: payload,
  });
};

export const updateMerchantTheme = (
  payload: SSOConfigsPayloadType,
  dashboardView: string,
  mode: string,
): Promise<updateThemeResponseType> => {
  const updateThemePayload = {
    merchant_id: payload.merchant_id,
    app_type: dashboardView === 'sopc' || dashboardView === 'rcod' ? 'sopc' : '',
    mode: mode || 'live',
    sso_enabled: payload.configs?.sso_config?.sso_enabled || false,
  };

  if (!payload.merchant_id) {
    Promise.reject('Merchant ID is required');
  }

  return merchantFetch({
    url: 'magic/sso/snippets/insert',
    method: 'post',
    data: updateThemePayload,
  });
};
