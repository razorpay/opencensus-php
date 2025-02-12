import React from 'react';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { Link } from 'react-router-dom';

const SuccessModal = ({ closeModal }) => {
  return (
    <Modal className="success" showCloseBtn={false}>
      <ModalContent>
        <div className="header">
          <h4>Application Under Review</h4>
          <p>We are reviewing your business details</p>
        </div>
        <main>
          <p>
            <strong>This takes around 3-5 working days</strong>
          </p>

          <p>We will update the status on the dashboard once the review is completed</p>
          <p>
            In the meantime, you can accept international payments through PayPal Wallet. Click{' '}
            <a
              href="https://razorpay.com/docs/payment-gateway/payment-methods/paypal"
              target="_blank"
              rel="noreferrer noopener"
            >
              here
            </a>{' '}
            to learn more
          </p>
          <Link to="/" className="btn btn-primary" onClick={closeModal}>
            Go to Dashboard
          </Link>
        </main>
      </ModalContent>
    </Modal>
  );
};

export default SuccessModal;
