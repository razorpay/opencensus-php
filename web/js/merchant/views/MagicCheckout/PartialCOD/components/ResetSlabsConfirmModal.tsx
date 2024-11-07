import React, { useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import { ApiCallbackType } from 'merchant/views/MagicCheckout/PartialCOD/types';

const ResetSlabsConfirmModal = ({
  onConfirm = () => {},
  onClose = () => {},
  isOpen = false,
}: {
  onConfirm: (callbacks: ApiCallbackType) => void;
  onClose: () => void;
  isOpen: boolean;
}) => {
  const [loading, setLoading] = useState(false);

  const handleConfirm = () => {
    setLoading(true);
    onConfirm({ onSuccess: onClose, onEnd: () => setLoading(false) });
  };

  return (
    <Modal onDismiss={onClose} isOpen={isOpen}>
      <ModalHeader title="Delete and reset slabs?" />
      <ModalBody>
        <Text size="medium" color="surface.text.gray.normal">
          This will delete all created slabs and reset to default settings. This action cannot be
          reversed.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" gap="spacing.5">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button variant="primary" onClick={handleConfirm} isLoading={loading}>
            Reset
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ResetSlabsConfirmModal;
