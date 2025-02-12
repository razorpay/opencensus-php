import React, { useRef } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import CreditsAlertsTable from 'merchant/views/Account/Credits/components/CreditsAlertsTable';
import { analyticsTrack } from 'common/utils/analytics';
import { updateConfig as updateConfigReducer } from 'merchant/reducers/config';
import {
  CLICK_CANCEL_MANAGE_ALERTS,
  CLICK_KNOW_MORE_MANAGE_ALERTS,
  CLICK_SAVE_MANAGE_ALERTS,
} from './ga';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

const items = [{ credit_type: 'Current Balance' }];

function ManageBalanceAlert({
  user,
  closeModal,
  currentBalanceThreshold,
  updateConfig,
  showNotification,
}) {
  const ref = useRef();

  const getAlertTableItems = () => {
    const creditTableRows = [{ ...items[0], credit_alert_threshold: currentBalanceThreshold }];

    return creditTableRows;
  };

  const onClickSave = () => {
    analyticsTrack(CLICK_SAVE_MANAGE_ALERTS);
    const alertValues = ref.current.getAlertValues();

    const currentBalanceAlert = alertValues.find((val) => val.credit_type === 'Current Balance');

    const payload = {
      balance_threshold: currentBalanceAlert ? currentBalanceAlert.credit_alert_threshold : 0,
    };

    return updateConfig(payload)
      .then(() => {
        selfServeTrackSuccess({
          selfServeAction: 'Funds Alert Created',
          page: 'Addfunds',
          screen: 'My Account',
        });
        showNotification({
          type: 'success',
          message: 'Balance threshold updated successfully',
        });
        closeModal();
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  const onClickCancel = () => {
    closeModal();
    analyticsTrack(CLICK_CANCEL_MANAGE_ALERTS);
  };

  return (
    <div className="manage-alert-modal-container">
      <ModalHeader title="Manage Balance Alerts" onCloseClick={closeModal} />
      <div>
        <div className="description">
          <p>
            You will receive an email when your balance goes below the amount set in the alerts.{' '}
            <a
              href="https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/"
              rel="noopener noreferrer"
              target="_blank"
              onClick={() => {
                analyticsTrack(CLICK_KNOW_MORE_MANAGE_ALERTS);
              }}
            >
              Know more
            </a>
          </p>
          <strong>Note: Set amount to 0 if you do not want to receive alerts.</strong>
        </div>
        <div className="alerts-table">
          <CreditsAlertsTable
            ref={(instance) => (ref.current = instance)}
            items={getAlertTableItems()}
            columnNames={['balance_type', 'alert_1', 'alert_2', 'alert_3']}
            onClickCancel={onClickCancel}
            onClickSave={onClickSave}
            user={user}
          />
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    currentBalanceThreshold: state.config.config.balance_threshold,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...NotificationActions,
      updateConfig: updateConfigReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(ManageBalanceAlert);
