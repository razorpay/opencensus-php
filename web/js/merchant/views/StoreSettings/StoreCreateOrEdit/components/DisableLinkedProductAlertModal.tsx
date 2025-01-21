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

type DisableLinkedProductAlertModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onSubmit: () => void;
};

const DisableLinkedProductAlertModal = (
  props: DisableLinkedProductAlertModalProps,
): React.ReactElement => {
  const { modalProps, onSubmit } = props;

  const { isOpen, onDismiss } = modalProps;

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title="Alert" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Text>
            Disabling the current product will also disable the terminals related to it. Are you
            sure you want to continue?
          </Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onDismiss}>
            Cancel
          </Button>
          <Button onClick={onSubmit} color="negative">
            Disable
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default DisableLinkedProductAlertModal;
