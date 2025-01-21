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

type DeleteTerminalModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: () => void;
  isLoading: boolean;
};

const DeleteTerminalModal = (props: DeleteTerminalModalProps): React.ReactElement => {
  const { modalProps, onSubmit, isLoading } = props;

  const handleCancel = () => {
    modalProps.onDismiss();
  };

  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title="Alert" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Text>Terminals once deleted cannot be recovered.</Text>
          <Text>
            The information regarding this terminal will still reflect in report and analytics.
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button
            variant="tertiary"
            onClick={handleCancel}
            isDisabled={isLoading}
            accessibilityLabel="Cancel"
          >
            Cancel
          </Button>
          <Button
            onClick={onSubmit}
            isLoading={isLoading}
            color="negative"
            accessibilityLabel="Delete"
          >
            Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default DeleteTerminalModal;
