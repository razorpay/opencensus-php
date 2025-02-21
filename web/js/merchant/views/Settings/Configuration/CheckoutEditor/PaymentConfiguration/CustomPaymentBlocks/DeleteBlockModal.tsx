import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import React from 'react';

export function DeleteBlockModal({ isOpen, onClose, blockName, onDelete }) {
  return (
    <Modal isOpen={isOpen} onDismiss={onClose}>
      <ModalHeader title={`Delete “${blockName}” custom block?`} />
      <ModalBody>
        <Text>
          This action will permanently delete “{blockName}” custom block. This custom block will no
          longer be accessible or available to you.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button variant="primary" color="negative" onClick={onDelete}>
            Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
