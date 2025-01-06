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

type StatusChangeAlertModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: () => void;
};

const StatusChangeAlertModal = (props: StatusChangeAlertModalProps): React.ReactElement => {
  const { modalProps, onSubmit } = props;
  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title="Alert" />
      <ModalBody>
        <Box display="flex" gap="spacing.3" flexDirection="column">
          <Text>
            Turning off the terminal will stop generating digital bills on this store's terminal.
          </Text>
          <Text>Click OK to continue</Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onDismiss}>
            Cancel
          </Button>
          <Button onClick={onSubmit}>OK</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default StatusChangeAlertModal;
