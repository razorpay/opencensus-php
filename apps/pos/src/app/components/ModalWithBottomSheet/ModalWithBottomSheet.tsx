import React, { useEffect } from 'react';
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
  onBottomsheetOpen?: () => void;
}

const ModalWithBottomSheet = ({
  isOpen,
  headerText,
  onDismiss,
  content,
  footer,
  snapPoints,
  onBottomsheetOpen,
}: ModalWithBottomSheetProps): JSX.Element => {
  const { isMobile } = useScreen();

  useEffect(() => {
    onBottomsheetOpen?.();
  }, []);

  const onDismissClick = () => {
    onDismiss();
  };

  return isMobile ? (
    <BottomSheet isOpen={isOpen} onDismiss={onDismissClick} snapPoints={snapPoints}>
      {headerText ? <BottomSheetHeader title={headerText} /> : null}
      <BottomSheetBody>{content}</BottomSheetBody>
      {footer ? <BottomSheetFooter>{footer}</BottomSheetFooter> : null}
    </BottomSheet>
  ) : (
    <Modal isOpen={isOpen} onDismiss={onDismissClick}>
      <ModalHeader title={headerText} />
      <ModalBody>{content}</ModalBody>
      <ModalFooter>{footer}</ModalFooter>
    </Modal>
  );
};

export default ModalWithBottomSheet;
