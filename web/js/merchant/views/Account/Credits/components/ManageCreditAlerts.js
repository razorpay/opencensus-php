import { useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { updateConfig } from 'merchant/reducers/config';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';
import CreditsAlertsTable from './CreditsAlertsTable';
import {
  CLICK_KNOW_MORE_MANAGE_ALERTS,
  CLICK_CANCEL_MANAGE_ALERTS,
  CLICK_SAVE_MANAGE_ALERTS,
} from '../ga';
import { analyticsTrack } from 'common/utils/analytics';

const items = [
  { credit_type: 'Amount Credit' },
  { credit_type: 'Fee Credit' },
  { credit_type: 'Refund Credit' },
];

function ManageCreditAlerts(props) {
  const ref = useRef();

  const onClickSave = () => {
    analyticsTrack(CLICK_SAVE_MANAGE_ALERTS);
    const alertValues = ref.current.getAlertValues();

    const amountCreditsAlert = alertValues.find((item) => item.credit_type === 'Amount Credit');
    const feeCreditsAlert = alertValues.find((item) => item.credit_type === 'Fee Credit');
    const refundCreditsAlert = alertValues.find((item) => item.credit_type === 'Refund Credit');

    const payload = {
      amount_credits_threshold: amountCreditsAlert ? amountCreditsAlert.credit_alert_threshold : 0,
      fee_credits_threshold: feeCreditsAlert ? feeCreditsAlert.credit_alert_threshold : 0,
      refund_credits_threshold: refundCreditsAlert ? refundCreditsAlert.credit_alert_threshold : 0,
    };

    return props
      .updateConfig(payload)
      .then(() => {
        props.showNotification({
          type: 'success',
          message: 'Credits threshold updated successfully',
        });
        props.closeModal();
      })
      .catch(({ errors }) => {
        props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  const onClickCancel = () => {
    props.closeModal();
    analyticsTrack(CLICK_CANCEL_MANAGE_ALERTS);
  };

  const getAlertTableItems = () => {
    const creditTableRows = [{ ...items[1], credit_alert_threshold: props.feeCreditsThreshold }];

    creditTableRows.unshift({
      ...items[0],
      credit_alert_threshold: props.amountCreditsThreshold,
    });
    creditTableRows.push({
      ...items[2],
      credit_alert_threshold: props.refundCreditsThreshold,
    });

    return creditTableRows;
  };

  return (
    <div class="manage-alert-modal-container">
      <ModalHeader title="Manage Credit Alerts" onCloseClick={props.closeModal} />
      <div>
        <div class="description">
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
        <div class="alerts-table">
          <CreditsAlertsTable
            ref={(instance) => (ref.current = instance)}
            items={getAlertTableItems()}
            columnNames={['credit_type', 'alert_1', 'alert_2', 'alert_3']}
            onClickCancel={onClickCancel}
            onClickSave={onClickSave}
            user={props.user}
          />
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    amountCreditsThreshold: state.config.config.amount_credits_threshold,
    feeCreditsThreshold: state.config.config.fee_credits_threshold,
    refundCreditsThreshold: state.config.config.refund_credits_threshold,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...NotificationActions,
      updateConfig,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(ManageCreditAlerts);
