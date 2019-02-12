import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { Link } from 'react-router-dom';
import Button from 'component/Button';

export default ({ templateLabel, onClose, backToTemplate }) => {
  return (
    <ModalMask
      maskClosable={false}
      class="payment-pages-v2-intro"
      isBlur={true}
    >
      <Button.Transparent class="back-btn" onClick={backToTemplate}>
        <i class="i i-chevron-left" />
        Back to Templates
      </Button.Transparent>
      <Modal showCloseBtn={false}>
        <ModalContent>
          <div class="slide-in">
            <div class="heading">
              Create New {templateLabel || 'Payment'} Page
            </div>
            <p>
              This is how the page will appear to your customers.
              <br />
              You can preview and edit the page at the same time!
            </p>
            <Button.Primary onClick={onClose} autoFocus>
              Let's Go!
            </Button.Primary>
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};
