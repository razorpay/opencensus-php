import React from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
} from '@razorpay/blade/components';

type MissingShopImagesModalProps = {
  isOpen: boolean;
  externalUrl: string | null;
  onClose: () => void;
};

const MissingShopImagesModal = ({
  isOpen,
  externalUrl,
  onClose,
}: MissingShopImagesModalProps): JSX.Element => {
  const handleOnAddDetailsClick = () => {
    if (externalUrl) {
      window.location.assign(externalUrl);
    }
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="small">
      <ModalHeader title="Add your POS Details" />
      <ModalBody>
        <Text size="medium">
          Please add few more details in order to complete your order. Without these, we won’t be
          able to process your POS order.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Button onClick={handleOnAddDetailsClick} isLoading={status === 'loading'}>
          Add Details
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default MissingShopImagesModal;
