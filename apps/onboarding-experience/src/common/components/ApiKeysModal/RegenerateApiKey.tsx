import React, { useState } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import {
  ApiKeyDelay,
  ApiKeys,
  RegenerateKeysModalProps,
  RegenerateModalScreens,
} from '@OnboardingExperienceCommons/types/apiKeys';
import DeactivateKeys from './DeactivateKeys';
import RevealApiKey from './RevealApiKey';

/**
 * Handles the API key regeneration flow between multiple screens
 *
 * Acts as a controller component that:
 * 1. Manages state transitions between deactivation and reveal screens
 * 2. Handles API key regeneration with appropriate error handling
 */
const RegenerateApiKey = ({
  handleDownloadApiKeys,
  handleRegenerateApiKeys,
  onDismiss,
}: RegenerateKeysModalProps) => {
  const showNotification = useStore((state) => state.showNotification);

  // Track loading state during API calls
  const [isFetching, setIsFetching] = useState(false);
  const [generatedApiKeys, setGeneratedApiKeys] = useState<ApiKeys | null>(null);
  // Control which modal screen is displayed in the regeneration flow
  const [activeScreen, setActiveScreen] = useState<RegenerateModalScreens>(
    RegenerateModalScreens.DEACTIVATE,
  );

  /**
   * Wraps the key regeneration process with proper error handling and state updates
   *
   * @param keyrollTime - Controls whether old keys are deactivated immediately or after delay
   */
  const handleRegenerateApiKeysHelper = async (keyrollTime: ApiKeyDelay) => {
    try {
      setIsFetching(true);
      const newApiKey = await handleRegenerateApiKeys(keyrollTime);
      if (!newApiKey?.id || !newApiKey?.secret) {
        throw new Error('Unable to generate new API keys!');
      }
      setGeneratedApiKeys(newApiKey);
      // Transition to reveal screen only after successful key generation
      setActiveScreen(RegenerateModalScreens.REVEAL);
    } catch (error) {
      showNotification({
        type: 'error',
        message:
          error instanceof Error && error.message
            ? error.message
            : 'An error occurred while regenerating API keys - Please try again later',
      });
    } finally {
      setIsFetching(false);
    }
  };

  // Conditional rendering based on the current step in the regeneration flow
  return (
    <>
      {activeScreen === RegenerateModalScreens.DEACTIVATE && (
        <DeactivateKeys
          isLoading={isFetching}
          handleRegenerateApiKeys={handleRegenerateApiKeysHelper}
          onDismiss={onDismiss}
        />
      )}

      {activeScreen === RegenerateModalScreens.REVEAL && generatedApiKeys && (
        <RevealApiKey
          apiKeys={generatedApiKeys}
          handleDownloadApiKeys={handleDownloadApiKeys}
          onDismiss={onDismiss}
        />
      )}
    </>
  );
};

export default RegenerateApiKey;
