import { withRouter } from 'react-router-dom';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';

const StoreCreatedModal = ({ hideSuccessModal, history }) => {
  const handleAddProduct = () => {
    hideSuccessModal();
    history.push('/stores/products/new');
  };
  return (
    <ModalMask>
      <Modal maskClosable={false} className="store-success" showCloseBtn={false}>
        <div className="store-succcess--image-container">
          <img
            src="/dist/css/assets/stores/store-success-bg.svg"
            alt="store-success-bg"
            className="store--success-bg"
          />
          <img
            src="/dist/css/assets/stores/store-preview.svg"
            alt="store-preview"
            className="store--preview"
          />
        </div>
        <div className="store-succcess--text-container">
          <h1>Your Store is now Live! 🎉</h1>
          <div className="divider" />
          <div>
            To start selling on your newly created Store, go ahead and add your first product.
          </div>
          <Button.Primary onClick={handleAddProduct}>Add a Product</Button.Primary>
          <Button.Transparent className="Button--Link" onClick={hideSuccessModal}>
            Skip & Go to Dashboard
          </Button.Transparent>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default withRouter(StoreCreatedModal);
