import React, { useState } from 'react';
import { Button, TextInput, Box, Text, Divider, CheckCircleIcon } from '@razorpay/blade/components';
import { useModalComponents } from '@libs/shared-ui';
import { isMobileDevice } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

const SharePaymentHandleModal = ({
  onDismiss,
  paymentUrl,
  onPaymentShare,
}: {
  onDismiss: () => void;
  paymentUrl: string;
  onPaymentShare: (amount: { amount?: string }) => Promise<void>;
}) => {
  const showNotification = useStore((state) => state.showNotification);

  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  const [isLoading, setIsLoading] = useState(false);
  const [isCopied, setIsCopied] = useState(false);
  const [amount, setAmount] = useState<string>('');

  const handlePaymentShare = async () => {
    try {
      setIsLoading(true);
      await onPaymentShare({ amount });

      // On desktop, the button text changes to "Copied!" and the button is disabled for 2 seconds
      if (!isMobile) {
        setIsCopied(true);
      }
    } catch (error) {
      showNotification({
        type: 'error',
        content:
          error instanceof Error && !!error.message
            ? error.message
            : 'An error occured while sharing payment handle!',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const shareButtonText = isMobile ? 'Share Link' : isCopied ? 'Copied!' : 'Copy Link';

  return (
    <Modal
      isOpen={true}
      onDismiss={onDismiss}
      size="small"
      accessibilityLabel="Share Payment Handle"
      snapPoints={[0.7, 0.7, 0.7]}
      zIndex={zIndicesMap.modal}
    >
      <ModalHeader title="Share Payment Handle" subtitle="Enter amount and share" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.5" width="100%">
          <Box display="flex" flexDirection="column" gap="spacing.3">
            <Text color="surface.text.gray.muted" size="small" weight="semibold">
              Your handle
            </Text>
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
                {paymentUrl}
              </Text>
            </Box>
          </Box>
          <TextInput
            value={amount}
            onChange={({ value }) => setAmount(value || '')}
            placeholder="Enter amount (optional)"
            prefix="₹"
            type="number"
            label="Enter amount (optional)"
            labelPosition="top"
            alignSelf="stretch"
          />
          <Divider width="100%" alignSelf="stretch" />
          <Text size="xsmall" color="surface.text.gray.subtle">
            You can add a specific amount for your customer to pay. This will not affect your
            default link.
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="primary"
            color={isCopied && !isMobile ? 'positive' : 'primary'}
            icon={isCopied && !isMobile ? CheckCircleIcon : undefined}
            onClick={handlePaymentShare}
            isLoading={isLoading}
            isDisabled={!paymentUrl}
            isFullWidth={isMobile ? true : false}
          >
            {shareButtonText}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default SharePaymentHandleModal;
