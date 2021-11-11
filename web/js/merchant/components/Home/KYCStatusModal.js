import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { compose } from 'redux';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';
import { kycModalContent } from './KycStatusModalContent';
import rTracking from 'react-tracking';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import { isDedupe, getActivationState } from 'merchant/components/Activation/ActivationUtils';
import InstantActivationModal from './InstantActivationModal';

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
  closeModal: closeModals,
  openModal: openModals,
  onGoToDashboard,
  user,
  modalType,
  activationDuration,
  tracking,
  history,
  showProductsModal: showProductsModals,
  hideProductsModal: hideProductsModals,
  showProducts,
}) => {
  const activationState = getActivationState(user, user.isUnregisteredBusiness);
  const generatePage = () => {
    onClose();
    openModal({
      size: 'small',
      component: <GenerateTnCPage onCloseModal={closeModals} openModal={openModals} />,
    });
  };

  const goToActivationForm = () => {
    onClose();
    history.push('/activation');
  };

  const openPaymentAcceptModal = () => {
    showProductsModals();
  };

  const onCloseModal = () => {
    const shouldShowModal =
      isDedupe(user) === 'blocked' ||
      activationState === 'needs_clarification_mcc_pending' ||
      activationState === 'needs_clarification' ||
      activationState === 'poi_verified' ||
      activationState === 'L1_instantly_activated' ||
      activationState === 'funds_on_hold' ||
      activationState === 'rejected';
    if (!shouldShowModal) {
      onGoToDashboard();
    } else {
      onClose();
    }
  };

  const instantActivationModal = () => {
    if (['poi_verified', 'L1_instantly_activated'].includes(activationState)) {
      return (
        <InstantActivationModal openPaymentAcceptModal={openPaymentAcceptModal} onClose={onClose} />
      );
    }
    return null;
  };

  const args = {
    isWhitelistFlow: user.instantActivation.isWhitelistFlow,
    isUnregisteredBusiness: user.isUnregisteredBusiness,
    onGoToDashboard,
    isActivated: user.isActivated,
    activationDuration,
    activationData: user,
    generatePage,
    goToActivationForm,
    tracking,
    openPaymentAcceptModal,
    onClose,
  };

  const content =
    modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL'
      ? MODAL_CONTENT.KYC_CLARIFICATION_SUBMIT_MODAL
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
              {content.button}
            </div>
          </Modal>
        </ModalMask>
      ) : (
        instantActivationModal()
      )}
      {showProducts ? (
        <ProductsModal onClose={hideProductsModals} track={trackProductsModal} />
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
  rTracking(() => window.rzpQ.component('KYCStatusModal')),
)(KYCStatusModal);
