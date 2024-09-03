import React, { useState, useRef } from 'react';
import {
  Box,
  Modal,
  ModalBody,
  Text,
  Button,
  IconButton,
  CloseIcon,
} from '@razorpay/blade/components';

const NO_OP = () => {};

const useConfirmModal = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [confirmMessage, setConfirmMessage] = useState('');
  const confirmCallback = useRef<() => void | Promise<void>>(NO_OP);
  const closeModal = () => {
    setIsOpen(false);
    confirmCallback.current = NO_OP;
  };

  const confirm = ({
    onConfirm,
    message,
  }: {
    onConfirm: () => void | Promise<void>;
    message: string;
  }) => {
    setConfirmMessage(message);
    confirmCallback.current = onConfirm;
    setIsOpen(true);
  };

  const handleConfirmClick = async () => {
    setIsLoading(true);
    await confirmCallback.current();
    setIsLoading(false);
    closeModal();
  };

  const renderConfirmModal = () => (
    <Modal isOpen={isOpen} onDismiss={closeModal}>
      <ModalBody padding="spacing.6">
        <Box>
          <Text size="medium" color="surface.text.gray.muted" marginTop="spacing.3">
            {confirmMessage}
          </Text>
          <Box marginTop="spacing.6" display="flex" gap="spacing.6">
            <Button onClick={handleConfirmClick} isLoading={isLoading} isDisabled={isLoading}>
              Yes
            </Button>
            <Button variant="secondary" onClick={closeModal} isDisabled={isLoading}>
              No
            </Button>
          </Box>
        </Box>
        <Box position="absolute" top="spacing.6" right="spacing.6">
          <IconButton icon={CloseIcon} onClick={closeModal} accessibilityLabel="Close Modal" />
        </Box>
      </ModalBody>
    </Modal>
  );

  return { renderConfirmModal, confirm };
};

export default useConfirmModal;
