import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { activationDuration } from 'merchant/helpers/data';

const MODAL_CONTENT = {
  KYC_ACTIVATION_SUBMIT_MODAL: {
    title: 'KYC under review',
    subtitle: 'Your KYC Form has been submitted',
    body: args => (
      <div>
        {args.isWhitelistFlow && (
          <p>Meanwhile, you can continue to accept payments using Razorpay.</p>
        )}
        <div>
          We will reach out on your contact email for further clarifications if
          needed. The review process usually takes {activationDuration}.
        </div>
      </div>
    ),
    background: 'pending',
  },
  KYC_CLARIFICATION_SUBMIT_MODAL: {
    title: 'KYC under review',
    subtitle: 'Clarifications successfully submitted',
    body: args => (
      <div>
        <p>Great, thank you for providing requested clarifications!</p>
        <p>
          We’ll review the form and get back to you in 4-5 days. Meanwhile, you
          can continue accepting payments.
        </p>
      </div>
    ),
    background: 'pending',
  },
};

const KYCStatusModal = ({
  onClose,
  onGoToDashboard,
  isWhitelistFlow,
  user,
  modalType,
}) => {
  const content = MODAL_CONTENT[modalType];
  let defaultSubtitle = 'Ready to accept domestic payments';

  if (user.international) {
    defaultSubtitle = 'Ready to accept domestic & international payments';
  }

  const args = {
    isWhitelistFlow,
    user,
  };

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className={`modal-header ${content.background}`}>
          <h1>{content.title}</h1>
          <p>{content.subtitle || defaultSubtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">{content.body(args)}</div>
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Go to Dashboard
          </button>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default KYCStatusModal;
