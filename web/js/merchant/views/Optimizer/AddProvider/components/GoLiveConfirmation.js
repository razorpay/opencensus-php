import React from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Text,
  Box,
  Button,
} from '@razorpay/blade/components';

export const GoLiveConfirmation = ({
  isModalOpen,
  closeGoLiveConfirmationModal,
  takeProviderLive,
  isUpdatingProvider,
}) => {
  return (
    <Modal isOpen={isModalOpen} onDismiss={closeGoLiveConfirmationModal} size="small">
      <ModalHeader title="Go Live Confirmation" />
      <ModalBody>
        <Text as="p">
          We discovered issues in your integration audit. Are you sure you want to override and go
          live?
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="secondary"
            onClick={closeGoLiveConfirmationModal}
            isDisabled={isUpdatingProvider}
          >
            Cancel
          </Button>
          <Button onClick={() => takeProviderLive(true)} isLoading={isUpdatingProvider}>
            Yes
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
