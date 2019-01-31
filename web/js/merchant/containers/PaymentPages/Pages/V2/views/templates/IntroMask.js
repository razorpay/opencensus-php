import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { Link } from 'react-router-dom';
import Button from 'component/Button';

export default ({ onClose }) => {
  return (
    <ModalMask
      maskClosable={false}
      class="payment-pages-v2-intro"
      isBlur={true}
    >
      <Link class="back-btn" to="/paymentpages/">
        <i class="i i-chevron-left" />
        Back to Dashboard
      </Link>
      <Modal showCloseBtn={false}>
        <ModalContent>
          <div class="heading">Create New Payment Page</div>
          <p>
            This is how the page will appear to your customers.
            <br />
            You can preview and edit the page at the same time!
          </p>
          <Button.Primary onClick={onClose} autoFocus>
            Let's Go!
          </Button.Primary>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};
