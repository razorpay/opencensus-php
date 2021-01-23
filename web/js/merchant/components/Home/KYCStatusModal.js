import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';

function getKycActivationSubmitBody(args) {
  return (
    <div>
      <div>KYC review process usually takes 8-10 working days. </div>

      <div>We will notify you if we require any clarifications on your KYC. </div>

      <div>Until then you can try out our products in the test mode </div>
    </div>
  );
}

const MODAL_CONTENT = {
  KYC_ACTIVATION_SUBMIT_MODAL: {
    title: () => 'KYC Submitted',
    subtitle: () => <>We are reviewing your KYC details</>,
    body: (args) => <div>{getKycActivationSubmitBody(args)}</div>,
    background: 'pending',
  },
  KYC_CLARIFICATION_SUBMIT_MODAL: {
    title: (args) => 'KYC under review',
    subtitle: (args) => 'Clarifications successfully submitted',
    body: (args) => (
      <div>
        <p>Great, thank you for providing requested clarifications!</p>
        <p>
          We’ll review the form and get back to you in{' '}
          {args.activationDuration || predefinedActivationDuration}.{' '}
          {args.isWhitelistFlow ? 'Meanwhile, you can continue accepting payments.' : ''}
        </p>
      </div>
    ),
    background: 'pending',
  },
};

const ModalButtons = ({ args, modalType }) => {
  if (modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL') {
    return (
      <>
        <a
          className="btn btn-default KYC__more_details"
          href="https://razorpay.com/support/"
          target="_blank"
          onClick={args.onGoToDashboard}
        >
          Contact Support
        </a>
        <button className="btn btn-primary" onClick={args.onGoToDashboard}>
          Go to Dashboard
        </button>
      </>
    );
  }
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
      Try our products
    </button>
  );
};

const KYCStatusModal = ({ onClose, onGoToDashboard, user, modalType, activationDuration }) => {
  const content = MODAL_CONTENT[modalType];
  let defaultSubtitle = 'Ready to accept domestic payments';

  if (user.international) {
    defaultSubtitle = 'Ready to accept domestic & international payments';
  }

  const args = {
    isWhitelistFlow: user.instantActivation.isWhitelistFlow,
    isUnregisteredBusiness: user.isUnregisteredBusiness,
    onGoToDashboard: onGoToDashboard,
    activationDuration: activationDuration,
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
          <ModalButtons args={args} modalType={modalType} />
        </div>
      </Modal>
    </ModalMask>
  );
};

export default KYCStatusModal;
