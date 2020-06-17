import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

export default ({
  onClose,
  onGoToDashboard,
  onCompleteKYC,
  title,
  subtitle,
  content,
  user,
}) => {
  let defaultSubtitle = 'Ready to accept domestic payments';

  if (user.international) {
    defaultSubtitle = 'Ready to accept domestic & international payments';
  }

  return (
    <ModalMask>
      <Modal className="instant-activations-success" onClose={onClose}>
        <modal-header>
          <h1>{title || 'Account Activated'}</h1>
          <p>{subtitle || defaultSubtitle}</p>
        </modal-header>
        <modal-body>
          {content ? (
            <p>{content}</p>
          ) : (
            <>
              <p>You can start accepting payments for your account now.</p>
              <p>
                Settlements are subject to account approval based on KYC and
                risk review. If we need any clarification we will reach out to
                you on your registered email ID.
              </p>
              <p>
                For more information refer to our{' '}
                <a
                  href="https://razorpay.com/terms/"
                  target="_blank"
                  rel="noopener"
                >
                  terms and conditions
                </a>.
              </p>
            </>
          )}
          <button
            onClick={onCompleteKYC}
            className="btn btn-default ias__complete_kyc"
          >
            Complete KYC
          </button>
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Start Accepting Payments
          </button>
        </modal-body>
      </Modal>
    </ModalMask>
  );
};
