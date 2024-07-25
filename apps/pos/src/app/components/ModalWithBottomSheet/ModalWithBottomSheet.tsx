import React from 'react';
import {
  BottomSheet,
  BottomSheetHeader,
  BottomSheetFooter,
  BottomSheetBody,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  BottomSheetProps,
} from '@razorpay/blade/components';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';

interface ModalWithBottomSheetProps extends Pick<BottomSheetProps, 'snapPoints'> {
  headerText?: string;
  isOpen: boolean;
  onDismiss: () => void;
  content: JSX.Element;
  footer?: JSX.Element;
}

const ModalWithBottomSheet = ({
  isOpen,
  headerText,
  onDismiss,
  content,
  footer,
  snapPoints,
}: ModalWithBottomSheetProps): JSX.Element => {
  const { isMobile } = useScreen();

  return isMobile ? (
    <BottomSheet isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints}>
      {headerText ? <BottomSheetHeader title={headerText} /> : null}
      <BottomSheetBody>{content}</BottomSheetBody>
      {footer ? <BottomSheetFooter>{footer}</BottomSheetFooter> : null}
    </BottomSheet>
  ) : (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title={headerText} />
      <ModalBody>{content}</ModalBody>
      <ModalFooter>{footer}</ModalFooter>
    </Modal>
  );
};

export default ModalWithBottomSheet;
