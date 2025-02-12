import { useState } from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  missedOrderPlanDeActivation,
  fetchMerchantMOPLSubscription,
} from 'merchant/reducers/config';

import Alert from 'common/new-ui/Alert';
import Button from 'common/new-ui/Button';

import track from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/track';
import StopButton from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink/components/Button';

const Cancel = ({ closeModal, showNotification, isFreeTrial, fetchMerchantMOPLSubscription }) => {
  const [stopReMarketerloading, setStopReMarketerloading] = useState(false);

  const stopReMarketer = () => {
    setStopReMarketerloading(true);
    return missedOrderPlanDeActivation()
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Failed Payments Recovery deactivated successfully',
        });
        fetchMerchantMOPLSubscription();
        track.cancel.deactivation();
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'An error occurred in stopping Failed Payments Recovery',
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  return (
    <div className="missed-order-cancel-container">
      <div className="modal-header header-wrapper">
        <div className="heading-title">
          <img
            src="https://cdn.razorpay.com/static/assets/instrument-request/alert-triangle.svg"
            alt="alert"
            height="18px"
            width="18px"
          />
          <h3 className="modal-title">Stop Failed Payments Recovery</h3>
        </div>
        {closeModal && (
          <div>
            {' '}
            <button type="button" className="close" onClick={closeModal}>
              <i className="i i-close" />
            </button>
          </div>
        )}
      </div>
      <div className="content">
        <div className="info">
          If you proceed, failed orders will no longer be revived and your plan will be cancelled.
          Are you sure?
        </div>
        {!isFreeTrial && (
          <Alert.Warning iconBefore="i-info-outline" className="info-warning">
            You will still be billed for this month’s usage
          </Alert.Warning>
        )}
        <div className="btn-wrapper">
          <Button.Transparent type="button" className="btn btn-primary" onClick={closeModal}>
            Don't Stop
          </Button.Transparent>
          <StopButton
            hideIcon
            onClick={stopReMarketer}
            loading={stopReMarketerloading}
            buttonText="Stop Payments Recovery"
            pendingState="Processing..."
          />
        </div>
      </div>
    </div>
  );
};

export default connect(null, { showNotification, fetchMerchantMOPLSubscription })(Cancel);
