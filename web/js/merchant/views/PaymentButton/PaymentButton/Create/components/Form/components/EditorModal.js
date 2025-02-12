import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';

/* Position-awared Modal which opens over the field being edited / in the middle of screen */
const CreatorModal = ({
  children,
  overElement,
  className,
  onClose,
  allowScroll,
}) => {
  // If not available, then opens modal in center of screen

  const modalContent = (
    <ModalContent className="Modal-content--PaymentButton-CreateForm">
      {children}
    </ModalContent>
  );

  return overElement ? (
    <React.Fragment>
      <div className={className} />
      <Modal className={className} showCloseBtn={false} allowScroll={allowScroll}>
        <div className="mimic-expand" />
        {modalContent}
      </Modal>
    </React.Fragment>
  ) : (
    <ModalMask maskClosable={false} className="PaymentButton-CreateForm-editor">
      <Modal
        onClose={onClose}
        className={classList('animate-appear', className)}
        showCloseBtn
        allowScroll={allowScroll}
      >
        {modalContent}
      </Modal>
    </ModalMask>
  );
};

export default CreatorModal;
