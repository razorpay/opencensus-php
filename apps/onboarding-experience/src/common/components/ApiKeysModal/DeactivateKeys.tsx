import React, { useState } from 'react';
import { Button, Radio, RadioGroup, Box, Text } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { useModalComponents } from '@libs/shared-ui';
import { ApiKeyDelay, DeactivateApiKeyProps } from '@OnboardingExperienceCommons/types/apiKeys';
import { zIndicesMap } from '@OnboardingExperienceCommons/constants/config';

/**
 * Confirmation screen for API key regeneration with deactivation options
 *
 * This component presents users with deactivation timing options before regenerating keys
 */
const DeactivateKeys = ({
  isLoading = false,
  handleRegenerateApiKeys,
  onDismiss,
}: DeactivateApiKeyProps) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  // Track user's selection - undefined means no selection made yet
  const [keyRollDelay, setKeyRollDelay] = useState<ApiKeyDelay | undefined>(undefined);

  return (
    <Modal isOpen onDismiss={onDismiss} snapPoints={[1, 1, 1]} zIndex={zIndicesMap.modal}>
      <ModalHeader
        title="Confirm and deactivate keys?"
        subtitle="Your current keys will be deactivated since you are generating new ones"
      />
      <ModalBody>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.4"
          alignSelf="stretch"
          testID="regenerate-api-key-modal-body"
        >
          <Text color="surface.text.gray.muted" size="medium" weight="semibold">
            Select only one
          </Text>
          <RadioGroup
            value={keyRollDelay}
            onChange={(e) => setKeyRollDelay(e.value as ApiKeyDelay)}
            name="deactivate-time"
            size="large"
            isDisabled={isLoading}
          >
            <Box display="flex" flexDirection="column" gap="spacing.3">
              <Radio value={ApiKeyDelay.NO_DELAY} size="large">
                Deactivate old key immediately
              </Radio>
              <Radio value={ApiKeyDelay.DELAY} size="large">
                Deactivate old key in 24 hours
              </Radio>
            </Box>
          </RadioGroup>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box
          display="flex"
          gap="spacing.3"
          justifyContent="flex-end"
          testID="regenerate-api-key-modal-footer"
        >
          {!isMobile && (
            <Button variant="tertiary" onClick={onDismiss} isDisabled={isLoading}>
              Cancel
            </Button>
          )}
          {/* Submit button is disabled until user explicitly chooses a deactivation option */}
          <Button
            variant="primary"
            onClick={() => keyRollDelay && handleRegenerateApiKeys(keyRollDelay)}
            isLoading={isLoading}
            isDisabled={keyRollDelay === undefined}
            isFullWidth={isMobile}
          >
            Confirm
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default DeactivateKeys;
