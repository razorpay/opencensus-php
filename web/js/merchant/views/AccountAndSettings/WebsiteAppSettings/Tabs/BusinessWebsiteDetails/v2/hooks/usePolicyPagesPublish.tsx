import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

import {
  PolicyPagesConsentPayload,
  PolicyPagesConsentResponse,
  PolicyPagesPreviewResponse,
  PolicyPagesPublishPayload,
  WebsiteUpdateApiData,
} from '../types';
import { WEBSITE_UPDATE_API_BASE_URL } from '../utils';

const getPolicyPagesPreview = async (data): Promise<PolicyPagesPreviewResponse> => {
  try {
    return await fetch<PolicyPagesPreviewResponse>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/GetMerchantWebsitePolicyPreview`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

const publishMerchantPolicyPages = async (data): Promise<WebsiteUpdateApiData> => {
  try {
    return await fetch<WebsiteUpdateApiData>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/PublishMerchantPolicySection`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

const saveMerchantPolicyPagesConsent = async (data): Promise<PolicyPagesConsentResponse> => {
  try {
    return await fetch<PolicyPagesConsentResponse>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/MerchantConsentsSave`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const usePolicyPagesPreview = (payload) => {
  return useQuery(['usePolicyPagesPreview'], () => getPolicyPagesPreview(payload), {
    refetchOnWindowFocus: false,
    refetchOnMount: true,
    retry: 0,
  });
};

export const usePolicyPagesPublish = () => {
  const queryClient = useQueryClient();

  const consentMutation = useMutation<
    PolicyPagesConsentResponse,
    unknown,
    PolicyPagesConsentPayload
  >({
    mutationFn: (payload) => saveMerchantPolicyPagesConsent(payload),
    onSuccess: () => {},
    onError: () => {},
  });
  const publishMutation = useMutation<WebsiteUpdateApiData, unknown, PolicyPagesPublishPayload>({
    mutationFn: (payload) => publishMerchantPolicyPages(payload),
    onSuccess: (response) => {
      queryClient.setQueryData(['getWebsiteUpdate'], response);
    },
    onError: () => {},
  });

  return {
    consentMutation,
    publishMutation,
  };
};
