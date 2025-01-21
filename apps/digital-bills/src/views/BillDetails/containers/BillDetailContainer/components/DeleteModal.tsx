import React from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
  ModalProps,
} from '@razorpay/blade/components';

type DeleteModalProps = {
  modalProps: Omit<ModalProps, 'children'>;
  onDelete: () => void;
  isLoading?: boolean;
  showPluralText?: boolean;
};

const DeleteModal = (props: DeleteModalProps): React.ReactElement => {
  const { modalProps, onDelete, isLoading, showPluralText = false } = props;
  return (
    <Modal {...modalProps}>
      <ModalHeader title="Heads Up!" />
      <ModalBody>
        <Text size="large" variant="body">
          {showPluralText ? 'Bills' : 'Bill'} cannot be recovered once deleted. Are you sure you
          want to delete {!showPluralText ? 'this' : null} bill{showPluralText ? '(s)' : null}?
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            testID="dismiss-btn"
            variant="tertiary"
            onClick={modalProps.onDismiss}
            isDisabled={isLoading}
          >
            Cancel
          </Button>
          <Button color="negative" onClick={onDelete} isLoading={isLoading}>
            Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default DeleteModal;
