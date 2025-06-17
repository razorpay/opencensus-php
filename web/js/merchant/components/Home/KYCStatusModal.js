import React, { useEffect } from 'react';
import ImgNcKyc from 'assets/onboarding/ncKyc.svg';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { ModalMask, Modal } from 'common/new-ui/Modal';
import Image from 'common/ui/Image';
import {
  getActivationState,
  isNewNcActivationStatus,
  redirectToEasyAfter1sec,
} from 'merchant/components/Activation/ActivationUtils';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';
import * as EventsActions from 'merchant/reducers/trackEvents';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import InstantActivationModal from './InstantActivationModal';
import { kycModalContent } from './KycStatusModalContent';
import { isMobileDevice } from './data';
import { useLatestOrder } from 'merchant_common/views/Reports/hooks/useLatestOrder';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';

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
  showProductsModal: showProductsModals,
  hideProductsModal: hideProductsModals,
  showProducts,
  trackEvents,
  isNcEligibile,
}) => {
  const navigate = useNavigate();
  const activationState = getActivationState(user, user.isUnregisteredBusiness, isNcEligibile);
  const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
  const isSignupWithEasyOnboarding = checkIfSignUpViaEasyOnboarding(user);
  const { latestOrder } = useLatestOrder();

  const isNewNc = isNewNcActivationStatus(activationState);

  const generatePage = () => {
    onClose();
    openModal({
      size: 'small',
      component: <GenerateTnCPage onCloseModal={closeModals} openModal={openModals} />,
    });
  };

  const onNcModalClose = () => {
    const sessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');
    if (isNewNc && sessionExpired) {
      window.sessionStorage.setItem('isNewNc', window.session_id);
    }
  };

  const goToNCOnEasy = () => {
    const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
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
    onNcModalClose();
    window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
  };

  const goToActivationForm = () => {
    if (isSignupWithEasyOnboarding) {
      trackEvents({
        objectName: 'redirect to easy-dashboard CTA',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          'CTA Label': 'Complete KYC',
        },
      });
      redirectToEasyAfter1sec();
    } else {
      navigate(activationUrl);
    }
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
    latestOrder,
  };

  const content =
    modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL'
      ? MODAL_CONTENT.KYC_CLARIFICATION_SUBMIT_MODAL
      : kycModalContent(args, navigate);

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
      activationState === 'rejected' ||
      activationState === 'bdd_needs_clarification' ||
      activationState === 'needs_clarification_for_pos';
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
          <Modal className="pan-status-modal nc-modal" showCloseBtn={false}>
            <div className={`modal-header ${content.background}`}>
              <Image src={ImgNcKyc} alt="nc kyc" className="nc-img" />
            </div>
            <div className="modal-body">
              {content.pill && <span className="status-pill">{content.pill}</span>}
              <h1>{content.title}</h1>
              <div className="modal-description">{content.body}</div>
              {content.button}
            </div>
          </Modal>
        </ModalMask>
      ) : !!content && !isNewNc ? (
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
  connect(
    (state) => ({
      showProducts: state.home.instantActivations.showProductsModal,
      isNcEligibile: state.home.isNcEligibile,
    }),
    { openModal, closeModal, showProductsModal, hideProductsModal, ...EventsActions },
  ),
  rTracking(() => window.rzpQ.component('KYCStatusModal')),
)(KYCStatusModal);
