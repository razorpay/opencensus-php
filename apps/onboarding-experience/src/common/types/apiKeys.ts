import { MutationOptions } from '@tanstack/query-core';

export type ApiKeys = {
  id: string;
  secret?: string;
  createdAt?: string;
  updatedAt?: string;
  expiredAt?: string | null;
};

export enum ApiKeyDelay {
  DELAY = 'DELAY',
  NO_DELAY = 'NO_DELAY',
}

export interface RegenerateKeysModalProps {
  handleRegenerateApiKeys: (keyRollTime: ApiKeyDelay) => Promise<ApiKeys>;
  handleDownloadApiKeys: (apiKeys: ApiKeys) => Promise<void>;
  onDismiss: () => void;
}

export interface RevealKeysModalProps {
  apiKeys: ApiKeys;
  handleDownloadApiKeys: (apiKeys: ApiKeys) => Promise<void>;
  onDismiss: () => void;
  isFetching?: boolean;
}

export interface DeactivateApiKeyProps {
  isLoading?: boolean;
  handleRegenerateApiKeys: (keyRollTime: ApiKeyDelay) => void;
  onDismiss: () => void;
}

export enum RegenerateModalScreens {
  DEACTIVATE = 'DEACTIVATE',
  REVEAL = 'REVEAL',
}

export type RevealKeysApiResponse = {
  status_code: number;
  success: boolean;
  data: {
    id: string;
    secret: string;
    created_at: string;
    updated_at: string;
    expired_at: string | null;
  };
  errors?: string[];
};

export type RegenerateKeysApiResponse = {
  status_code: number;
  success: boolean;
  data: {
    old: {
      id: string;
      secret: string;
      created_at: string;
      updated_at: string;
      expired_at: string | null;
    };
    new: {
      id: string;
      secret: string;
      created_at: string;
      updated_at: string;
      expired_at: string | null;
    };
  };
  errors?: string[];
};

export type MerchantApiKeysCreateResponse = {
  merchantApiKeysCreate: {
    code: number;
    success: boolean;
    message?: string;
    id: string;
    secret?: string;
    createdAt?: string;
    updatedAt?: string;
    expiredAt?: string | null;
  };
};

export type MerchantApiKeyRegenerateResponse = {
  merchantApiKeyRegenerate: {
    code: number;
    success: boolean;
    message?: string;
    oldApiKey: ApiKeys;
    newApiKey: ApiKeys;
  };
};

// Type for the hook return value
export type UseMerchantApiKeysReturnType = {
  // Mutations
  generateApiKeyMutation: (
    params: undefined,
    mutationOptions?: { onSuccess: (response: MerchantApiKeysCreateResponse) => void },
  ) => Promise<MerchantApiKeysCreateResponse>;
  regenerateApiKeyMutation: (
    params: {
      keyRollDelay: ApiKeyDelay;
      oldApiKeyId: string;
    },
    mutationOptions?: { onSuccess: (response: MerchantApiKeyRegenerateResponse) => void },
  ) => Promise<MerchantApiKeyRegenerateResponse>;
};
