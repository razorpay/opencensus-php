export type ApiKeys = {
  id: string;
  secret: string;
};

export enum ApiKeyDelay {
  DELAY = 'DELAY',
  NO_DELAY = 'NO_DELAY',
}

export interface ApiKeysModalProps {
  handleRegenerateApiKeys: (keyRollTime: ApiKeyDelay) => Promise<ApiKeys>;
  handleDownloadApiKeys: (apiKeys: ApiKeys) => Promise<void>;
  onDismiss: () => void;
}

export interface RevealApiKeyProps {
  apiKeys: ApiKeys;
  isFetching?: boolean;
  handleDownloadApiKeys: (apiKeys: ApiKeys) => Promise<void>;
  onDismiss: () => void;
}

export interface RegenerateApiKeyProps {
  isLoading?: boolean;
  handleRegenerateApiKeys: (keyRollTime: ApiKeyDelay) => void;
  onDismiss: () => void;
}

export enum RegenerateModalScreens {
  DEACTIVATE = 'DEACTIVATE',
  REVEAL = 'REVEAL',
}
