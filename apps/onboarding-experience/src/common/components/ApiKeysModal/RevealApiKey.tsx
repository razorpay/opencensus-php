import React, { useState } from 'react';
import {
  Button,
  Box,
  Text,
  Popover,
  Link,
  SettlementsIcon,
  CopyIcon,
  TextInput,
  Alert,
  InfoIcon,
  Spinner,
} from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { copyToClipboard, isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { RevealApiKeyProps } from 'apps/onboarding-experience/src/common/types/apiKeys';

/**
 * Component that displays newly created credentials
 *
 * This component implements:
 * - One-time display of secret keys
 * - Copy-to-clipboard functionality
 * - Download option for secure offline storage
 */
const RevealApiKey = ({
  apiKeys,
  isFetching,
  handleDownloadApiKeys,
  onDismiss,
}: RevealApiKeyProps) => {
  const showNotification = useStore((state) => state.showNotification);
  const mode = useStore((state) => state.session.mode);
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  const [isDownloading, setIsDownloading] = useState(false);
  // Dynamic label that reflects current environment context (Test/Live)
  const activeModeLabel = mode === 'test' ? 'Test' : 'Live';

  /**
   * Safely copies API credential to clipboard with visual feedback
   */
  const handleCopy = (text: string) => {
    copyToClipboard(text);
  };

  /**
   * Initiates secure download of API credentials as file
   * With proper error handling for download failures
   */
  const handleDownload = async () => {
    try {
      setIsDownloading(true);
      await handleDownloadApiKeys(apiKeys);
    } catch (error) {
      showNotification({
        type: 'error',
        message:
          error instanceof Error && error.message
            ? error.message
            : 'Unable to Download Api Keys! - Please try again later',
      });
    } finally {
      setIsDownloading(false);
    }
  };

  return (
    <Modal isOpen onDismiss={onDismiss}>
      <ModalHeader title="Key ID & Secret" subtitle="Integration Details" />
      <ModalBody>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.7"
          alignSelf="stretch"
          testID="api-keys-modal-body"
        >
          {/* API Key ID field with copy action */}
          <Box
            display="flex"
            alignItems="center"
            gap="spacing.3"
            alignSelf="stretch"
            flex="1"
            testID="api-keys-modal-key-id"
          >
            <Box flex="1">
              {isFetching ? (
                <Spinner accessibilityLabel="api-keys-spinner" />
              ) : (
                <TextInput
                  label={`${activeModeLabel} Key ID`}
                  value={apiKeys.id}
                  labelPosition="left"
                  isDisabled
                />
              )}
            </Box>
            <Popover
              title="Copied"
              titleLeading={<SettlementsIcon />}
              content={<Text textAlign="center">{apiKeys.id}</Text>}
            >
              <Link
                size="large"
                icon={CopyIcon}
                onClick={() => handleCopy(apiKeys.id)}
                isDisabled={isFetching}
              />
            </Popover>
          </Box>
          {/* API Secret field with copy action - most sensitive data */}
          <Box
            display="flex"
            alignItems="center"
            gap="spacing.3"
            alignSelf="stretch"
            flex="1"
            testID="api-keys-modal-key-secret"
          >
            <Box flex="1">
              {isFetching ? (
                <Spinner accessibilityLabel="api-keys-spinner" />
              ) : (
                <TextInput
                  label={`${activeModeLabel} Key Secret`}
                  value={apiKeys.secret}
                  labelPosition="left"
                  isDisabled
                />
              )}
            </Box>
            <Popover
              title="Copied"
              titleLeading={<SettlementsIcon />}
              content={<Text textAlign="center">{apiKeys.secret}</Text>}
            >
              <Link
                size="large"
                icon={CopyIcon}
                onClick={() => handleCopy(apiKeys.secret)}
                isDisabled={isFetching}
              />
            </Popover>
          </Box>
          {/* Label about one-time visibility */}
          <Alert
            title="Alert"
            description="For security reasons, this key can only be downloaded once. We won't show it again, so keep it safe."
            color="notice"
            isFullWidth
            isDismissible={false}
            icon={InfoIcon}
            testID="api-keys-modal-alert"
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box
          display="flex"
          justifyContent="flex-end"
          alignItems="center"
          alignSelf="stretch"
          testID="api-keys-modal-footer"
        >
          <Button onClick={handleDownload} isLoading={isDownloading}>
            Download
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default RevealApiKey;
