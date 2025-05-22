import {
  MerchantApiKeyRegenerateResponse,
  MerchantApiKeysCreateResponse,
  RegenerateKeysApiResponse,
  RevealKeysApiResponse,
} from '@OnboardingExperienceCommons/types/apiKeys';

// Transform API response to GQL equal response for easy migration to GQL when required
export const transformRevealApiKeyResponse = (
  response: RevealKeysApiResponse,
): MerchantApiKeysCreateResponse => {
  return {
    merchantApiKeysCreate: {
      code: response.status_code,
      success: response.success,
      message: response.errors?.[0],
      id: response.data?.id,
      secret: response.data?.secret,
      createdAt: response.data?.created_at,
      updatedAt: response.data?.updated_at,
      expiredAt: response.data?.expired_at,
    },
  };
};

export const transformRegenerateApiKeyResponse = (
  response: RegenerateKeysApiResponse,
): MerchantApiKeyRegenerateResponse => {
  return {
    merchantApiKeyRegenerate: {
      code: response.status_code,
      success: response.success,
      message: response.errors?.[0],
      oldApiKey: {
        id: response.data?.old?.id,
        secret: response.data?.old?.secret,
        updatedAt: response.data?.old?.updated_at,
        createdAt: response.data?.old?.created_at,
        expiredAt: response.data?.old?.expired_at,
      },
      newApiKey: {
        id: response.data?.new?.id,
        secret: response.data?.new?.secret,
        updatedAt: response.data?.new?.updated_at,
        createdAt: response.data?.new?.created_at,
        expiredAt: response.data?.new?.expired_at,
      },
    },
  };
};
