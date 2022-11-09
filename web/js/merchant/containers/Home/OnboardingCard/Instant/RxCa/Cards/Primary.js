import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';
import CaApplyForm from 'merchant/containers/Home/OnboardingCard/Instant/RxCa/CaApplyForm';
import CaApplyAcknowledge from 'merchant/containers/Home/OnboardingCard/Instant/RxCa/CaApplyAcknowledge';
import { currentAccountStatuses, getTimeLine } from './data';
import rTracking from 'react-tracking';
import { getTimeDiff } from 'merchant/containers/Home/OnboardingCard/Instant/RxCa/helpers';

const Primary = ({
  tracking,
  updateUser,
  settings,
  openModal,
  closeModal,
  caAccount,
  headState,
  hasAppliedCa,
  caStatus,
  activatedAt,
  pillType,
  pillText,
  showNitroRXCAFlow,
}) => {
  // activation time diff applyforCA is difference between kyc approved and current date.
  const activationTimeDiff = {
    applyForCA: getTimeDiff(activatedAt, 16),
    documentSubmission: getTimeDiff(activatedAt, 61),
  };

  const goToXdashboard = () => {
    window.open('https://x.razorpay.com', '_blank');
    tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'apply',
        status: 'account_activated',
      }),
    );
  };

  const handleSuccess = () => {
    const _settings = { ...settings };
    _settings.clicked_ca_apply_request_done = '1';
    merchantFetch({
      url: 'users',
      mode: 'live',
      method: 'patch',
      data: { settings: _settings },
    }).then(() => {
      updateUser({ settings: _settings });
    });

    closeModal();
    openModal({
      component: <CaApplyAcknowledge onClose={closeModal} />,
      size: 'small',
      className: 'CA-apply-acknowledge--modal',
    });
  };

  const sendClickEvents = () => {
    tracking.trackEvent(
      window?.rzpQ?.merchantActions()?.clicked('dashboard.neopricing_tracker', {
        clicked_on: 'apply_now',
        status: 'application_form',
      }),
    );
  };

  const openCaApplyModal = () => {
    sendClickEvents();
    openModal({
      component: (
        <CaApplyForm
          onClose={closeModal}
          onSuccess={handleSuccess}
          showDeadlineExtentionMessage={activationTimeDiff.applyForCA <= 0}
        />
      ),
      size: 'small',
      className: 'CA-apply--modal',
    });
  };

  const getTimelineStatus = (type) => {
    if (
      [
        currentAccountStatuses.processing,
        currentAccountStatuses.initiated,
        currentAccountStatuses.activated,
        currentAccountStatuses.processed,
        currentAccountStatuses.unserviceable,
        currentAccountStatuses.verification_call,
        currentAccountStatuses.doc_collection,
        currentAccountStatuses.api_onboarding,
        currentAccountStatuses.account_opening,
        currentAccountStatuses.account_activation,
      ].includes(caStatus)
    ) {
      return false;
    }

    if (activationTimeDiff[type] > 1) {
      return (
        <div className={activationTimeDiff[type] <= 10 ? 'danger timeline-info' : 'timeline-info'}>
          {activationTimeDiff[type]} days left
        </div>
      );
    } else if (activationTimeDiff[type] == 1) {
      return <div className="timeline-info danger">1 day left</div>;
    } else {
      return <div className="timeline-info cancelled">Application deadline exceeded</div>;
    }
  };
  return (
    <div className="ca-primary-card">
      <div className="left">
        {showNitroRXCAFlow ? (
          <div className="head">Track your account application status</div>
        ) : (
          <>
            <div className="head">Open Your RazorpayX Current Account</div>
            {getTimeLine(hasAppliedCa, caStatus, activatedAt)}
          </>
        )}
      </div>
      <div className="right">
        {showNitroRXCAFlow ? (
          <div className={`ca-pill ${pillType}`}>{pillText}</div>
        ) : (
          <>
            {caAccount && caAccount.status === currentAccountStatuses.activated && (
              <Button.Primary iconAfter="external-link" onClick={goToXdashboard}>
                Explore Account
              </Button.Primary>
            )}
            {hasAppliedCa && headState && <div className="headstate">{headState}</div>}
            {!hasAppliedCa && getTimelineStatus('applyForCA')}
            {hasAppliedCa && getTimelineStatus('documentSubmission')}
            {!hasAppliedCa && (
              <Button.Primary onClick={openCaApplyModal}>
                {activationTimeDiff.applyForCA <= 0 ? 'Request Extension' : 'Apply Now'}
              </Button.Primary>
            )}
          </>
        )}
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => ({
  openModal: bindActionCreators(openModal, dispatch),
  closeModal: bindActionCreators(closeModal, dispatch),
  updateUser: bindActionCreators(updateUser, dispatch),
});

export default rTracking({ page: 'RXNeoCaPrimaryCard' })(
  connect(null, mapDispatchToProps)(Primary),
);
