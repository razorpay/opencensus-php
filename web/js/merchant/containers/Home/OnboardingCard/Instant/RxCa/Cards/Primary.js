import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';
import CaApplyForm from '../CaApplyForm';
import CaApplyAcknowledge from '../CaApplyAcknowledge';
import { currentAccountStatuses } from './data';
import RTracking from 'react-tracking';

const Primary = ({
  tracking,
  updateUser,
  settings,
  openModal,
  closeModal,
  caAccount,
  headState,
  hasAppliedCa,
}) => {
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
    _settings['clicked_ca_apply_request_done'] = '1';
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

  const openCaApplyModal = () => {
    sendClickEvents();
    openModal({
      component: <CaApplyForm onClose={closeModal} onSuccess={handleSuccess} />,
      size: 'small',
      className: 'CA-apply--modal',
    });
  };
  const sendClickEvents = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'apply_now',
        status: 'application_form',
      }),
    );
  };
  return (
    <div className="ca-primary-card">
      <div className="left">
        <div className="head">Open Your RazorpayX Current Account</div>
        <div className="subhead">Finish your account opening process to stay on NEO pricing </div>
      </div>
      <div className="right">
        {caAccount && caAccount.status === currentAccountStatuses.activated && (
          <Button.Primary iconAfter="external-link" onClick={goToXdashboard}>
            Explore Account
          </Button.Primary>
        )}
        {hasAppliedCa && headState && <div className="headstate">{headState}</div>}
        {!hasAppliedCa && <Button.Primary onClick={openCaApplyModal}>Apply Now</Button.Primary>}
      </div>
    </div>
  );
};

//TODO
// will be using this in V2
const getTimeline = () => {
  // getClass = () => {
  //     return 'highlight';
  // }
  return (
    <div className="ca-apply-timeline">
      <img src="/img/inactive-circle.svg" alt="Clients" />
      <div className="active">Apply by 12 Dec</div>
      <div className="dash-separator">- - - - - - </div>
      <img src="/img/active-circle.svg" alt="Clients" />
      <div className="inactive">Submit documents by 21 Dec</div>
      <img src="/img/done-circle.svg" alt="Clients" />
    </div>
  );
};

const mapDispatchToProps = (dispatch) => ({
  openModal: bindActionCreators(openModal, dispatch),
  closeModal: bindActionCreators(closeModal, dispatch),
  updateUser: bindActionCreators(updateUser, dispatch),
});

export default RTracking({ page: 'RXNeoCaPrimaryCard' })(
  connect(null, mapDispatchToProps)(Primary),
);
