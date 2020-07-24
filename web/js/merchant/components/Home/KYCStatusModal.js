import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { activationDuration } from 'merchant/helpers/data';

function getKycActivationSubmitBody(args) {
  if (args.isWhitelistFlow) {
    return (
      <div>
        <p>
          KYC review process takes 1-2 working days post your first transaction.
          So go ahead and start accepting payments. As soon as your KYC is
          approved we will process settlements to your bank account.
        </p>
      </div>
    );
  }

  if (args.isUnregisteredBusiness) {
    return (
      <div>
        We will reach out on your contact email for further clarifications if
        needed. The review process usually takes 1-2 working days after your
        first transaction. Your settlements will be enabled post KYC is reviewed
        and approved.
      </div>
    );
  }

  return (
    <div>
      We will reach out on your contact email for further clarifications if
      needed. The review process usually takes 1-2 working days.
    </div>
  );
}

const MODAL_CONTENT = {
  KYC_ACTIVATION_SUBMIT_MODAL: {
    title: args =>
      args.isWhitelistFlow ? 'KYC Submitted' : 'KYC Under Review',
    subtitle: args =>
      args.isWhitelistFlow
        ? 'KYC will be processed post your first transaction'
        : 'Your KYC Form is submitted',
    body: args => <div>{getKycActivationSubmitBody(args)}</div>,
    background: 'pending',
  },
  KYC_CLARIFICATION_SUBMIT_MODAL: {
    title: args => 'KYC under review',
    subtitle: args => 'Clarifications successfully submitted',
    body: args => (
      <div>
        <p>Great, thank you for providing requested clarifications!</p>
        <p>
          We’ll review the form and get back to you in {activationDuration}.{' '}
          {args.isWhitelistFlow
            ? 'Meanwhile, you can continue accepting payments.'
            : ''}
        </p>
      </div>
    ),
    background: 'pending',
  },
};

const ModalButtons = ({ args }) => {
  if (args.isWhitelistFlow) {
    return (
      <>
        <a
          className="btn btn-default KYC__more_details"
          href="https://razorpay.freshdesk.com/a/solutions/articles/11000092582&sa=D&ust=1594198150522000&usg=AFQjCNHDpL3kI_n5NQwp8zP8yPBj7RszJQ"
          target="_blank"
        >
          Know More
        </a>
        <button className="btn btn-primary" onClick={args.onGoToDashboard}>
          Accept Payments
        </button>
      </>
    );
  }

  return (
    <button className="btn btn-primary" onClick={args.onGoToDashboard}>
      Go to Dashboard
    </button>
  );
};

const KYCStatusModal = ({ onClose, onGoToDashboard, user, modalType }) => {
  const content = MODAL_CONTENT[modalType];
  let defaultSubtitle = 'Ready to accept domestic payments';

  if (user.international) {
    defaultSubtitle = 'Ready to accept domestic & international payments';
  }

  const args = {
    isWhitelistFlow: user.instantActivation.isWhitelistFlow,
    isUnregisteredBusiness: user.isUnregisteredBusiness,
    onGoToDashboard: onGoToDashboard,
  };

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className={`modal-header ${content.background}`}>
          <h1>{content.title(args)}</h1>
          <p>{content.subtitle(args) || defaultSubtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">{content.body(args)}</div>
          <ModalButtons args={args} />
        </div>
      </Modal>
    </ModalMask>
  );
};

export default KYCStatusModal;
