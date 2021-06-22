import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { compose } from 'redux';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';
import { kycModalContent } from './KycStatusModalContent';
import RTracking from 'react-tracking';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import { isDedupe, getActivationState } from 'merchant/components/Activation/ActivationUtils';

const MODAL_CONTENT = {
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
    background: 'warning',
    button: (args) => (
      <button className="btn btn-primary" onClick={args.onGoToDashboard}>
        Go to Dashboard
      </button>
    ),
  },
};

const KYCStatusModal = ({
  onClose,
  closeModal,
  openModal,
  onGoToDashboard,
  user,
  modalType,
  activationDuration,
  tracking,
  history,
  showProductsModal,
  hideProductsModal,
  showProducts,
}) => {
  const generatePage = () => {
    onClose();
    openModal({
      size: 'small',
      component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
    });
  };

  const goToActivationForm = () => {
    onClose();
    history.push('/activation');
  };

  const openPaymentAcceptModal = () => {
    showProductsModal();
  };

  const onCloseModal = () => {
    const activationState = getActivationState(user, user.isUnregisteredBusiness);
    const shouldShowModal =
      isDedupe(user) === 'blocked' ||
      activationState === 'needs_clarification_mcc_pending' ||
      activationState === 'needs_clarification' ||
      activationState === 'rejected';
    if (!shouldShowModal) {
      onGoToDashboard();
    } else {
      onClose();
    }
  };

  const args = {
    isWhitelistFlow: user.instantActivation.isWhitelistFlow,
    isUnregisteredBusiness: user.isUnregisteredBusiness,
    onGoToDashboard: onGoToDashboard,
    isActivated: user.isActivated,
    activationDuration: activationDuration,
    activationData: user,
    generatePage: generatePage,
    goToActivationForm: goToActivationForm,
    tracking: tracking,
    openPaymentAcceptModal: openPaymentAcceptModal,
    onClose: onClose,
  };

  const content =
    modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL'
      ? MODAL_CONTENT['KYC_CLARIFICATION_SUBMIT_MODAL']
      : kycModalContent(args);

  return (
    <>
      {!!content ? (
        <ModalMask>
          <Modal className="pan-status-modal" onClose={() => onCloseModal()}>
            <div className={`modal-header ${content.background}`}>
              <h1>{content.title}</h1>
              {content.subtitle && <p>{content.subtitle}</p>}
            </div>
            <div className="modal-body">
              <div className="modal-description">{content.body}</div>
              <>{content.button}</>
            </div>
          </Modal>
        </ModalMask>
      ) : null}
      {showProducts ? (
        <ProductsModal onClose={hideProductsModal} track={trackProductsModal} />
      ) : null}
    </>
  );
};

export default compose(
  withRouter,
  connect(
    (state) => ({
      showProducts: state.home.instantActivations.showProductsModal,
    }),
    { openModal, closeModal, showProductsModal, hideProductsModal },
  ),
  RTracking(() => window.rzpQ.component('KYCStatusModal')),
)(KYCStatusModal);
