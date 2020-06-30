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
    <ModalContent class="Modal-content--PaymentButton-CreateForm">
      {children}
    </ModalContent>
  );

  return overElement ? (
    <React.Fragment>
      <div class={className} />
      <Modal class={className} showCloseBtn={false} allowScroll={allowScroll}>
        <div class="mimic-expand" />
        {modalContent}
      </Modal>
    </React.Fragment>
  ) : (
    <ModalMask maskClosable={false} class="PaymentButton-CreateForm-editor">
      <Modal
        onClose={onClose}
        class={classList('animate-appear', className)}
        showCloseBtn
        allowScroll={allowScroll}
      >
        {modalContent}
      </Modal>
    </ModalMask>
  );
};

export default CreatorModal;
