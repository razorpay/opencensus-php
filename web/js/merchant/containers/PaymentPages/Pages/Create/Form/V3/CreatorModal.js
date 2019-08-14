import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { classList } from 'common/util';

/* Position-awared Modal which opens over the field being edited */
const PositionAwaredCreator = ({ children, overWhatElement, className }) => {
  // If not available, then opens modal in center of screen

  const modalContent = (
    <ModalContent class="paymentlinks-creator">{children}</ModalContent>
  );

  return overWhatElement ? (
    <React.Fragment>
      <div class={className} />
      <Modal class={className} showCloseBtn={false}>
        {modalContent}
      </Modal>
    </React.Fragment>
  ) : (
    <ModalMask maskClosable={false} class="payment-pages-v3-creator">
      <Modal class={classList('animate-appear', className)} showCloseBtn>
        {modalContent}
      </Modal>
    </ModalMask>
  );
};

export default PositionAwaredCreator;
