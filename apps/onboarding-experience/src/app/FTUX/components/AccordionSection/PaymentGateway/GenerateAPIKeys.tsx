import React, { useState } from 'react';
import { Box, Button, EyeIcon, RefreshIcon, Tooltip } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import RevealApiKey from '@OnboardingExperienceCommons/components/ApiKeysModal/RevealApiKey';
import RegenerateApiKey from '@OnboardingExperienceCommons/components/ApiKeysModal/RegenerateApiKey';
import { hasAddedWebsite } from '@OnboardingExperienceCommons/utils/merchant';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { ApiKeysModalScreens } from '@FTUX/types/common';
import { arrayToCsv, downloadFile } from '@libs/shared-utils';
import {
  API_KEYS_CSV_FILENAME,
  API_KEYS_CSV_HEADER,
  API_KEYS_CSV_MIME_TYPE,
} from 'apps/onboarding-experience/src/constants';
import { ApiKeyDelay, ApiKeys } from '@OnboardingExperienceCommons/types/apiKeys';
import { isMobileDevice } from '@libs/shared-utils';

const GenerateAPIKeys = () => {
  const { mode } = useStore((state) => state.session);
  const showNotification = useStore((state) => state.showNotification);
  const isMobile = isMobileDevice();
  const { merchantData, initiateTwoFaAuth, regenerateApiKey, generateApiKey } =
    useMerchantContext();

  const [activeModal, setActiveModal] = useState<ApiKeysModalScreens | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [apiKeys, setApiKeys] = useState<ApiKeys>({
    id: '',
    secret: '',
  });

  const activeMode = mode === 'test' ? 'Test' : 'Live';
  // Determine if merchant already has API keys generated
  const hasGeneratedApiKeys = Boolean(merchantData?.merchantById?.apiKeys?.[0]?.id);
  const isKeylessActivation = !Boolean(merchantData?.merchantById?.hasApiKeyAccess);
  // Website verification is a prerequisite for API key generation
  const isWebsiteVerified = hasAddedWebsite(
    merchantData?.merchantById?.business?.paymentAcceptanceChannels,
  );

  /**
   * API keys access is disabled if:
   * 1. We're in live mode AND
   * 2. Either the website is not verified OR keyless activation is enabled
   *
   * This prevents generation of live API keys without proper verification
   * or when the merchant doesn't have API key access privileges.
   */
  const isApiKeysAccessDisabled = mode === 'live' && (!isWebsiteVerified || isKeylessActivation);

  // Formats API keys into CSV and triggers browser download
  const handleDownloadApiKeys = (apiKeys: ApiKeys) => {
    return new Promise<void>((resolve) => {
      const csvData = [apiKeys.id, apiKeys.secret];
      const csvDataUrl = arrayToCsv([API_KEYS_CSV_HEADER, csvData]);
      downloadFile(csvDataUrl, API_KEYS_CSV_FILENAME, API_KEYS_CSV_MIME_TYPE);
      resolve();
    });
  };

  // Regenerates API keys with specified roll delay (time before old keys expire)
  const handleRegenerateApiKeys = async (keyRollDelay: ApiKeyDelay) => {
    try {
      const response = await regenerateApiKey({
        keyRollDelay,
        oldApiKeyId: merchantData?.merchantById?.apiKeys?.[0]?.id ?? '',
      });

      if (!response.merchantApiKeyRegenerate?.newApiKey?.id) {
        throw new Error(response.merchantApiKeyRegenerate.message);
      }

      return response.merchantApiKeyRegenerate?.newApiKey;
    } catch (error: any) {
      throw new Error(error?.errors?.[0] || error.message || 'Failed to regenerate API keys!');
    }
  };

  // Generates new API keys and opens the reveal modal on success
  const handleGenerateApiKeys = async () => {
    try {
      const response = await generateApiKey();

      if (!response.merchantApiKeysCreate?.id) {
        throw new Error(response.merchantApiKeysCreate.message);
      }

      setApiKeys({
        id: response.merchantApiKeysCreate.id,
        secret: response.merchantApiKeysCreate.secret,
      });
      setActiveModal(ApiKeysModalScreens.REVEAL);
    } catch (error: any) {
      const errorMessage =
        error?.errors?.[0] || error.message || 'An error occurred while generating API keys!';

      if (error?.errors && error?.errors?.length > 0) {
        showNotification({
          type: 'error',
          message: errorMessage,
        });
      }
      setActiveModal(null);
    }
  };

  // Opens modal after successful 2FA authentication
  const openModal = async (modalType: ApiKeysModalScreens) => {
    setIsLoading(true);
    // 2FA is required for security before performing sensitive API key operations
    const twoFaSuccess = await initiateTwoFaAuth?.();
    if (!twoFaSuccess) {
      setIsLoading(false);
      return;
    }

    if (modalType === ApiKeysModalScreens.REVEAL) {
      await handleGenerateApiKeys();
    } else {
      setActiveModal(modalType);
    }

    setIsLoading(false);
  };

  const closeModal = () => {
    setActiveModal(null);
    setIsLoading(false);
  };

  return (
    <Box>
      {isApiKeysAccessDisabled ? (
        <Tooltip
          content={
            isKeylessActivation
              ? 'Your key access is restricted'
              : 'API keys can be generated after website verification is complete'
          }
          placement="top"
        >
          <Button variant="primary" size="medium" isDisabled isFullWidth={isMobile}>
            Reveal/Regenerate API Keys
          </Button>
        </Tooltip>
      ) : hasGeneratedApiKeys ? (
        <Button
          variant="secondary"
          size="medium"
          icon={RefreshIcon}
          isLoading={isLoading}
          onClick={() => openModal(ApiKeysModalScreens.REGEN)}
          isFullWidth={isMobile}
        >
          Regenerate {activeMode} API Keys
        </Button>
      ) : (
        <Button
          color="primary"
          size="medium"
          icon={EyeIcon}
          isLoading={isLoading}
          onClick={() => openModal(ApiKeysModalScreens.REVEAL)}
          isFullWidth={isMobile}
        >
          Reveal {activeMode} API Keys
        </Button>
      )}

      {activeModal === ApiKeysModalScreens.REVEAL && (
        <RevealApiKey
          apiKeys={apiKeys}
          handleDownloadApiKeys={handleDownloadApiKeys}
          onDismiss={closeModal}
        />
      )}

      {activeModal === ApiKeysModalScreens.REGEN && (
        <RegenerateApiKey
          handleDownloadApiKeys={handleDownloadApiKeys}
          handleRegenerateApiKeys={handleRegenerateApiKeys}
          onDismiss={closeModal}
        />
      )}
    </Box>
  );
};

export default GenerateAPIKeys;
