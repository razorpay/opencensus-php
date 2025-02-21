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

export function PreventDiscardChangesModal({
  isOpen,
  title,
  content,
  onClose,
  onDiscard,
}: {
  isOpen: boolean;
  title: string;
  content: string;
  onClose: () => void;
  onDiscard: () => void;
}) {
  return (
    <Modal isOpen={isOpen} size="small" onDismiss={onClose}>
      <ModalHeader title={title} />
      <ModalBody>
        <Text size="small" weight="regular">
          {content}
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end">
          <Button onClick={onClose} variant="tertiary">
            No
          </Button>
          <Button onClick={onDiscard}>Yes</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
