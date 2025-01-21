// TODO: Need to remove this file once terminals table pr is merged
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

type TerminalStatusChangeAlertModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: () => void;
};

const TerminalStatusChangeAlertModal = (
  props: TerminalStatusChangeAlertModalProps,
): React.ReactElement => {
  const { modalProps, onSubmit } = props;

  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} zIndex={10000}>
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
export default TerminalStatusChangeAlertModal;
