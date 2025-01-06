import React from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  ModalProps,
  Text,
} from '@razorpay/blade/components';

type DeleteStoreModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: () => void;
  isLoading: boolean;
};

const DeleteStoreModal = (props: DeleteStoreModalProps): React.ReactElement => {
  const { modalProps, onSubmit, isLoading } = props;

  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title="Heads Up!" />
      <ModalBody>
        <Box display="flex" gap="spacing.3" flexDirection="column">
          <Text>
            Store cannot be recovered once deleted. Are you sure you want to delete this store?
          </Text>
          <Text>
            This will also impact the attached products, roles and permissions, reporting and
            billing.
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={modalProps.onDismiss} isDisabled={isLoading}>
            Cancel
          </Button>
          <Button onClick={onSubmit} isLoading={isLoading} color="negative">
            Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default DeleteStoreModal;
