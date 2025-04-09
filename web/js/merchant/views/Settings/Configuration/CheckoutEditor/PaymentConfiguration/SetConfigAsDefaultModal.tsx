import React from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

export type SetConfigAsDefaultModalProps = {
  isOpen: boolean;
  onClose: () => void;
  onSave: (config: MerchantCheckoutPaymentConfig) => void;
  config: MerchantCheckoutPaymentConfig;
};

export function SetConfigAsDefaultModal({
  isOpen,
  onClose,
  onSave,
  config,
}: SetConfigAsDefaultModalProps) {
  function handleSetConfigAsDefaultSave() {
    onSave(config);
    onClose();
  }

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="small">
      <ModalHeader title="Save this Configuration as Default" />
      <ModalBody>
        <Text size="small" weight="regular" color="surface.text.gray.normal">
          Set this configuration as the default to be shown to users who don’t fall under any active
          or assigned configuration groups
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={handleSetConfigAsDefaultSave}>Continue</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
