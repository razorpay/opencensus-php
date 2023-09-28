import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { compose } from 'redux';

import { kycModalContent } from './KycStatusModalContent';
import rTracking from 'react-tracking';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';

const KYCStatusModal = ({
  onClose,
  onGoToDashboard,
  activationState,
  modalType,
  activationDuration,
  tracking,
  history,
  isNcEligibile,
}) => {
  const goToActivationForm = () => {
    onClose();
    history.push('/partner/activation');
  };

  const goToMerchantDashboard = () => {
    onClose();
    history.push('/dashboard');
  };

  const onCloseModal = () => {
    const shouldShowModal =
      activationState === 'under_review' ||
      activationState === 'kyc_qualified_unactivated' ||
      activationState === 'needs_clarification' ||
      activationState === 'rejected';
    if (!shouldShowModal) {
      onGoToDashboard();
    } else {
      onClose();
    }
  };

  const args = {
    onGoToDashboard,
    activationDuration,
    activationState,
    goToActivationForm,
    tracking,
    onClose,
    modalType,
    goToMerchantDashboard,
    isNcEligibile,
  };
  const content = kycModalContent(args);

  return !!content ? (
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
  ) : null;
};

export default compose(
  withRouter,
  connect(
    (state) => ({
      isNcEligibile: state.home.isNcEligibile,
    }),
    null,
  ),
  rTracking(() => window.rzpQ.component('PartnerKYCStatusModal')),
)(KYCStatusModal);
