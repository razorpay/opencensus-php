import React from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Button,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Text,
} from '@razorpay/blade/components';
import {
  CONFIRMATION_HEADER,
  CONFIRMATION_SUBTEXT,
  CONFIRMATION_FOOTER,
} from 'merchant/views/CompanyRegistration/constant';

interface ConfirmationPopUpT {
  isOpen: boolean;
  closeModal: () => void;
  handleUserAction: () => void;
}

interface ConfirmationParentPopUpT extends ConfirmationPopUpT {
  isSmallDevice: boolean;
}
const PopUpButtons = ({ closeModal, handleUserAction }) => (
  <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
    <Button variant="tertiary" onClick={closeModal}>
      Not now
    </Button>
    <Button onClick={handleUserAction}>Yes, let's begin</Button>
  </Box>
);
const PopUpBody = () => (
  <>
    <Text color="surface.text.gray.subtle" marginBottom="spacing.6">
      {CONFIRMATION_SUBTEXT}
    </Text>
    <Text color="surface.text.gray.subtle">{CONFIRMATION_FOOTER}</Text>
  </>
);

export const ConfirmationModal = ({ isOpen, closeModal, handleUserAction }: ConfirmationPopUpT) => {
  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="small">
      <ModalHeader title={CONFIRMATION_HEADER} />
      <ModalBody>
        <PopUpBody />
      </ModalBody>
      <ModalFooter>
        <PopUpButtons closeModal={closeModal} handleUserAction={handleUserAction} />
      </ModalFooter>
    </Modal>
  );
};

export const ConfirmationBottomSheet = ({
  isOpen,
  closeModal,
  handleUserAction,
}: ConfirmationPopUpT) => {
  return (
    <BottomSheet isOpen={isOpen} onDismiss={closeModal} snapPoints={[1, 1, 1]}>
      <BottomSheetHeader title={CONFIRMATION_HEADER} />
      <BottomSheetBody>
        <PopUpBody />
      </BottomSheetBody>
      <BottomSheetFooter>
        <PopUpButtons closeModal={closeModal} handleUserAction={handleUserAction} />
      </BottomSheetFooter>
    </BottomSheet>
  );
};

export const ConfirmationPopUp = (props: ConfirmationParentPopUpT) => {
  const { isOpen, isSmallDevice, ...rest } = props;
  if (!isOpen) return null;
  return (
    <>
      {isSmallDevice ? (
        <ConfirmationBottomSheet isOpen={isOpen} {...rest} />
      ) : (
        <ConfirmationModal isOpen={isOpen} {...rest} />
      )}
    </>
  );
};
