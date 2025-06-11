import React, { useState } from 'react';
import {
  Button,
  Box,
  Text,
  Link,
  CopyIcon,
  Alert,
  InfoIcon,
  Spinner,
  DownloadIcon,
  CheckCircleIcon,
  SettlementsIcon,
  Tooltip,
} from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import { copyToClipboard, isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { RevealKeysModalProps } from '@OnboardingExperienceCommons/types/apiKeys';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

/**
 * Enum representing possible download states
 */
enum DownloadState {
  INITIAL = 'initial',
  IN_PROGRESS = 'in_progress',
  SUCCESS = 'success',
}

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
}: RevealKeysModalProps) => {
  const showNotification = useStore((state) => state.showNotification);
  const mode = useStore((state) => state.session.mode);
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  const [downloadStatus, setDownloadStatus] = useState<DownloadState>(DownloadState.INITIAL);
  const [copiedFields, setCopiedFields] = useState<Record<string, boolean>>({});

  // Dynamic label that reflects current environment context (Test/Live)
  const activeModeLabel = mode === 'test' ? 'Test' : 'Live';

  /**
   * Safely copies API credential to clipboard with visual feedback
   */
  const handleCopy = (key: string, text: string) => {
    copyToClipboard(text);
    setCopiedFields({ ...copiedFields, [key]: true });
  };

  /**
   * Initiates secure download of API credentials as file
   */
  const handleDownload = async () => {
    try {
      setDownloadStatus(DownloadState.IN_PROGRESS);
      await handleDownloadApiKeys(apiKeys);
      setDownloadStatus(DownloadState.SUCCESS);
    } catch (error) {
      showNotification({
        type: 'error',
        message:
          error instanceof Error && error.message
            ? error.message
            : 'Unable to Download Api Keys! - Please try again later',
      });
      setDownloadStatus(DownloadState.INITIAL);
    }
  };

  const dataListWithCopy = [
    {
      testId: 'api-keys-modal-key-id',
      label: `${activeModeLabel} Key ID`,
      value: apiKeys.id,
    },
    {
      testId: 'api-keys-modal-key-secret',
      label: `${activeModeLabel} Key Secret`,
      value: apiKeys.secret,
    },
  ];

  return (
    <Modal
      isOpen
      onDismiss={onDismiss}
      snapPoints={[1, 1, 1]}
      zIndex={zIndicesMap.modal}
      data-analytics-name="reveal-api-modal"
    >
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
          {/* API Key ID and secret fields with copy action */}
          {dataListWithCopy.map(({ testId, label, value }) => (
            <Box
              display="flex"
              alignItems="center"
              gap="spacing.3"
              alignSelf="stretch"
              flex="1"
              testID={testId}
              key={testId}
            >
              <Box minWidth="130px">
                <Text color="surface.text.gray.subtle" size="medium" weight="semibold">
                  {label}
                </Text>
              </Box>
              {isFetching ? (
                <Box flex="1">
                  <Spinner accessibilityLabel="api-keys-spinner" />
                </Box>
              ) : (
                <Box
                  paddingX="spacing.4"
                  paddingY="spacing.3"
                  borderColor="surface.border.gray.muted"
                  borderWidth="thick"
                  borderRadius="medium"
                  flex="1"
                  display="grid"
                >
                  <Text color="surface.text.gray.normal" size="medium" truncateAfterLines={1}>
                    {value}
                  </Text>
                </Box>
              )}
              <Tooltip
                content={copiedFields[testId] ? 'Copied!' : 'Click to copy'}
                zIndex={zIndicesMap.dropdown}
              >
                <Link
                  variant="button"
                  size="large"
                  icon={copiedFields[testId] ? SettlementsIcon : CopyIcon}
                  color={copiedFields[testId] ? 'positive' : 'primary'}
                  onClick={() => handleCopy(testId, value || '')}
                  isDisabled={isFetching}
                  data-analytics-name={testId}
                />
              </Tooltip>
            </Box>
          ))}
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
          width="100%"
        >
          <Button
            onClick={handleDownload}
            isLoading={downloadStatus === DownloadState.IN_PROGRESS}
            isFullWidth={isMobile ? true : false}
            color={downloadStatus === DownloadState.SUCCESS ? 'positive' : 'primary'}
            icon={downloadStatus === DownloadState.SUCCESS ? CheckCircleIcon : DownloadIcon}
            isDisabled={isFetching}
            data-analytics-name="download-api-key"
          >
            {downloadStatus === DownloadState.SUCCESS ? 'Downloaded' : 'Download'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default RevealApiKey;
