import React, { useEffect } from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { compose } from 'redux';
import { kycModalContent } from './KycStatusModalContent';
import rTracking from 'react-tracking';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import {
  getActivationState,
  isNewNcActivationStatus,
} from 'merchant/components/Activation/ActivationUtils';
import InstantActivationModal from './InstantActivationModal';
import * as EventsActions from 'merchant/reducers/trackEvents';
import { isMobileDevice } from './data';

const MODAL_CONTENT = {
  KYC_CLARIFICATION_SUBMIT_MODAL: {
    title: () => 'KYC under review',
    subtitle: () => 'Clarifications successfully submitted',
    body: () => (
      // NOTE OE comms changes part-1
      <div>
        <p>
          Thank you for submitting your updated details. We’ll verify them and share an update soon.
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
  trackEvents,
  isNcEligibile,
}) => {
  const activationState = getActivationState(user, user.isUnregisteredBusiness, isNcEligibile);

  const isNewNc = isNewNcActivationStatus(activationState);

  const generatePage = () => {
    onClose();
    openModal({
      size: 'small',
      component: <GenerateTnCPage onCloseModal={closeModals} openModal={openModals} />,
    });
  };

  const goToNCOnEasy = () => {
    const needsClarificationOnEasyUrl = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
    trackEvents({
      objectName: 'NC Resolve Now',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        funnelStage: 'NC',
        formName: 'We need a few more details to complete KYC verification',
        activationState,
        ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
        clickSource: 'Modal',
        ctaClicked: 'Resolve Now',
        deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
      },
    });
    onClose();
    window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
  };

  const goToActivationForm = () => {
    const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
    onClose();
    history.push(activationUrl);
  };

  const openPaymentAcceptModal = () => {
    showProductsModals();
  };

  const args = {
    isWhitelistFlow: user.instantActivation.isWhitelistFlow,
    isUnregisteredBusiness: user.isUnregisteredBusiness,
    isActivationFormFullView: user.isActivationFormFullView,
    onGoToDashboard,
    isActivated: user.isActivated,
    activationDuration,
    activationData: user,
    generatePage,
    goToActivationForm,
    tracking,
    openPaymentAcceptModal,
    onClose,
    trackEvents,
    isNcEligibile,
    goToNCOnEasy,
  };

  const content =
    modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL'
      ? MODAL_CONTENT.KYC_CLARIFICATION_SUBMIT_MODAL
      : kycModalContent(args);

  const sessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');

  const onCloseModal = () => {
    const shouldShowModal =
      activationState === 'L2_dedupe_blocked' ||
      activationState === 'needs_clarification_mcc_pending' ||
      activationState === 'needs_clarification' ||
      activationState === 'poi_verified' ||
      activationState === 'L1_instantly_activated' ||
      activationState === 'funds_on_hold' ||
      activationState === 'needs_clarification_payments_settlement_enabled' ||
      activationState === 'needs_clarification_with_payments_enabled' ||
      activationState === 'needs_clarification_with_payment_disabled' ||
      activationState === 'rejected';
    if (content) {
      trackEvents({
        objectName: 'Pop Up',
        actionName: 'Closed',
        screen: 'home page',
        properties: {
          'Pop-up Label': content.title,
        },
      });
    }

    if (isNewNc && sessionExpired) {
      window.sessionStorage.setItem('isNewNc', window.session_id);
    }
    if (!shouldShowModal) {
      onGoToDashboard();
    } else {
      onClose();
    }
  };

  const instantActivationModal = () => {
    if (['poi_verified', 'L1_instantly_activated'].includes(activationState)) {
      return (
        <InstantActivationModal
          isActivationFormFullView={user.isActivationFormFullView}
          openPaymentAcceptModal={openPaymentAcceptModal}
          onClose={onClose}
          isInstantActivationVideoEnabled={user.isInstantActivationVideoEnabled}
        />
      );
    }
    return null;
  };

  useEffect(() => {
    if (!!content) {
      trackEvents({
        objectName: 'Pop Up',
        actionName: 'Viewed',
        screen: 'home page',
        properties: {
          'Pop-up Label': content?.title,
        },
      });
      if (isNewNc) {
        trackEvents({
          objectName: 'NC Entry Modal',
          actionName: 'Loaded',
          screen: 'home page',
          properties: {
            activationState,
            funnelStage: 'NC',
            formName: content?.title,
            ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
            deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
          },
          toCleverTap: true,
        });
      }
    }
  }, [content?.title]);

  return (
    <>
      {isNewNc && !!content && sessionExpired ? (
        <ModalMask>
          <Modal className="pan-status-modal nc-modal" onClose={() => onCloseModal()}>
            <div className={`modal-header ${content.background}`}>
              <h1>{content.title}</h1>
              {content.subtitle && <p>{content.subtitle}</p>}
            </div>
            <div className="modal-body">
              {content.pill && <span>{content.pill}</span>}
              <div className="modal-description">{content.body}</div>
              {content.button}
            </div>
          </Modal>
        </ModalMask>
      ) : !!content ? (
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
      isNcEligibile: state.home.isNcEligibile,
    }),
    { openModal, closeModal, showProductsModal, hideProductsModal, ...EventsActions },
  ),
  rTracking(() => window.rzpQ.component('KYCStatusModal')),
)(KYCStatusModal);
