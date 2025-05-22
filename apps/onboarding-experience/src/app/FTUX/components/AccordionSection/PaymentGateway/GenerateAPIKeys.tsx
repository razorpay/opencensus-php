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

const GenerateAPIKeys = () => {
  const { mode, user: activeUser } = useStore((state) => state.session);
  const showNotification = useStore((state) => state.showNotification);
  const { merchantData, initiateTwoFaAuth, regenerateApiKey, generateApiKey } =
    useMerchantContext();

  const [activeModal, setActiveModal] = useState<ApiKeysModalScreens | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [apiKeys, setApiKeys] = useState<ApiKeys>({
    id: '',
    secret: '',
  });

  const areApiKeysGenerated = Boolean(merchantData?.merchantById?.apiKeys?.[0]?.id);
  const isKeylessActivation = !Boolean(merchantData?.merchantById?.hasApiKeyAccess);
  const isWebsiteVerified = hasAddedWebsite(
    merchantData?.merchantById?.business?.paymentAcceptanceChannels,
  );
  const activeMode = mode === 'test' ? 'Test' : 'Live';

  const handleDownloadApiKeys = (apiKeys: ApiKeys) => {
    return new Promise<void>((resolve) => {
      const csvData = [apiKeys.id, apiKeys.secret];
      const csvDataUrl = arrayToCsv([API_KEYS_CSV_HEADER, csvData]);
      downloadFile(csvDataUrl, API_KEYS_CSV_FILENAME, API_KEYS_CSV_MIME_TYPE);
      resolve();
    });
  };

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

  const openModal = async (modalType: ApiKeysModalScreens) => {
    setIsLoading(true);
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
      {isKeylessActivation || !isWebsiteVerified ? (
        <Tooltip
          content={
            isKeylessActivation
              ? 'Your key access is restricted'
              : 'API keys can be generated after website verification is complete'
          }
          placement="top"
        >
          <Button variant="primary" size="medium" isDisabled>
            Reveal/Regenerate API Keys
          </Button>
        </Tooltip>
      ) : areApiKeysGenerated ? (
        <Button
          variant="secondary"
          size="medium"
          icon={RefreshIcon}
          isLoading={isLoading}
          onClick={() => openModal(ApiKeysModalScreens.REGEN)}
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
