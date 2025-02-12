import React from 'react';
import { connect } from 'react-redux';
import DisableReasonAutomatedWithdrawModal from './DisableReasonAutomatedWithdrawModal';
import trackAutomatedCA from './ga/automated';
import { updateAutomatedLOCConfig } from 'merchant/reducers/capital/withdrawals';

const DisableAutomatedWithdrawModal = ({
  onClose,
  openModal,
  userID,
  updateAutomatedLOCConfig,
}) => {
  trackAutomatedCA.clickYesDisabledAutomatedModal();
  const onDisableAutomatedWithdrawClick = () => {
    updateAutomatedLOCConfig({
      owner_id: userID,
      automated_loc: false,
    })
      .then((res) => {
        openModal({
          component: (
            <DisableReasonAutomatedWithdrawModal openModal={openModal} onClose={onClose} />
          ),
          size: 'small',
        });
      })
      .catch((e) => {
        console.log(e);
      });
  };

  const onDontDisableAutomatedWithdrawClick = () => {
    trackAutomatedCA.clickNoDontDisableAutomatedModal();
    onClose();
  };

  return (
    <div className="disable-automated-withdraw-modal">
      <div className="disable-automated-withdraw-modal--heading">
        Are you sure you want to do disable automated withdrawals?
      </div>
      <div>All future automated withdrawals will be disabled.</div>
      <div className="flex disable-automated-withdraw-modal--action">
        <button
          className="btn btn-outline dont-disable-btn"
          onClick={onDontDisableAutomatedWithdrawClick}
        >
          No, don’t
        </button>
        <button className="btn btn-primary yes-disable-btn" onClick={onDisableAutomatedWithdrawClick}>
          Yes, Disable
        </button>
      </div>
    </div>
  );
};

export default connect((state) => ({ userID: state.session.user.current }), {
  updateAutomatedLOCConfig,
})(DisableAutomatedWithdrawModal);
