import React from 'react';

import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Button,
  Text,
} from '@razorpay/blade/components';

export const SyncConfirmationModal = ({ isSyncModalOpen, handleSync, handleSyncModalClose }) => {
  return (
    <Modal isOpen={isSyncModalOpen} onDismiss={handleSyncModalClose} size="small">
      <ModalHeader title="Sync Shipping Profiles From Shopify" />
      <ModalBody>
        <Text>Syncing Shipping Profiles from Shopify might take upto 20 seconds.</Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button onClick={handleSyncModalClose} variant="secondary">
            Cancel
          </Button>
          <Button onClick={handleSync} accessibilityLabel="confirm sync">
            Sync
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
