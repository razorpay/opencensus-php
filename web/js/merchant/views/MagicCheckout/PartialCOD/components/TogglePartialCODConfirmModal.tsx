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

const TogglePartialCODConfirmModal = ({
  isOpen = false,
  onClose = () => {},
  onConfirm,
  isEnabled = false,
}: {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  isEnabled: boolean;
}) => {
  const dismissModal = () => {
    onClose();
  };

  const handleConfirm = () => {
    onConfirm();
    dismissModal();
  };

  return (
    <Modal accessibilityLabel="base-confirm-modal" onDismiss={dismissModal} isOpen={isOpen}>
      <ModalHeader title={`${isEnabled ? 'Disable' : 'Enable'} Partial COD?`} />
      <ModalBody>
        <Text size="medium" color="surface.text.gray.normal">
          {isEnabled ? 'Disabling' : 'Enabling'} partial COD would {isEnabled ? 'remove' : 'apply'}{' '}
          these changes to your current COD flow.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" gap="spacing.5">
          <Button variant="tertiary" onClick={dismissModal}>
            Cancel
          </Button>
          <Button variant="primary" onClick={handleConfirm}>
            {isEnabled ? 'Disable' : 'Enable'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default TogglePartialCODConfirmModal;
