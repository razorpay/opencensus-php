import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';

function getKycActivationSubmitBody(args) {
  if (
    (args.isActivated && args.isWhitelistFlow) ||
    (args.isUnregisteredBusiness && args.isActivated)
  ) {
    return (
      <div>
        We will reach out on your contact email for further clarifications if needed. The review
        process usually takes 1-2 working days <strong>after your first transaction</strong>. Your
        settlements will be enabled post KYC is reviewed and approved.
      </div>
    );
  }
  return (
    <div>
      <div>
        Your documents and KYC detail are under review. It's now our responsibility to make sure
        your documents are processed.
      </div>
      <div>
        It usually takes 3-4 working days for our team to review your documents. We will reach out
        to you if we need any clarification.
      </div>
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
    title: () => 'KYC under review',
    subtitle: () => 'Clarifications successfully submitted',
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
        {/* <a
          className="btn btn-default KYC__more_details"
          href="https://razorpay.com/support/"
          target="_blank"
          onClick={args.onGoToDashboard}
        >
          Contact Support
        </a> */}
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
          rel="noreferrer noopener"
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
      Back to Dashboard
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
    onGoToDashboard,
    isActivated: user.isActivated,
    activationDuration,
  };

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className={`modal-header ${content?.background}`}>
          <h1>{content?.title(args)}</h1>
          <p>{content?.subtitle(args) || defaultSubtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">{content?.body(args)}</div>
          <ModalButtons args={args} modalType={modalType} />
        </div>
      </Modal>
    </ModalMask>
  );
};

export default KYCStatusModal;
